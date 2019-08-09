<?php

namespace SimpleDIC;

use SimpleDIC\Parser\Parser;

class DIC
{
    /**
     * @var array
     */
    private static $cache;

    /**
     * @var string
     */
    private static $cacheDir;

    /**
     * @param string $filename
     *
     * @throws Exceptions\ParserException
     */
    public static function initFromFile($filename)
    {
        $cacheFile = self::getCacheFilePath($filename);

        // save cache file if does not exists
        if (false === file_exists($cacheFile)) {
            self::saveCacheFile($filename);
        }

        self::$cache = include($cacheFile);
    }

    /**
     * @param string $filename
     *
     * @throws Exceptions\ParserException
     */
    private static function saveCacheFile($filename)
    {
        $cachedMap = [];

        foreach (Parser::parse($filename) as $key => $content) {
            $start = microtime(true);
            $memoryUsage = memory_get_usage();
            $value = self::setInCache($cachedMap, $content);
            $allocatedSize = (memory_get_usage() - $memoryUsage);

            $cachedMap[$key] = [
                'value' => $value,
                '@metadata' => [
                    'type' => gettype(self::setInCache($cachedMap, $content)),
                    'create_time' => self::calculateCreatingTimeInMilliseconds($start),
                    'memory_usage' => $allocatedSize,
                ],
            ];
        }

        if (false === is_dir(self::getCacheDir())) {
            mkdir(self::getCacheDir(), 0755, true);
        }

        file_put_contents(self::getCacheFilePath($filename), '<?php return unserialize(\'' . serialize($cachedMap) . '\');' . PHP_EOL);
    }

    /**
     * @param float $start
     *
     * @return float
     */
    private static function calculateCreatingTimeInMilliseconds($start)
    {
        $stringval = microtime(true) - $start;
        $numericval = sscanf((string)$stringval, "%f")[0];
        $seconds = number_format($numericval, 2);

        return (float)$seconds * 1000;
    }

    /**
     * @param string $cacheDir
     */
    public static function setCacheDir($cacheDir)
    {
        self::$cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    private static function getCacheDir()
    {
        return (self::$cacheDir) ? self::$cacheDir : __DIR__ . '/../_cache/';
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private static function getCacheFilePath($filename)
    {
        return self::getCacheDir() . DIRECTORY_SEPARATOR . sha1_file($filename) . '.php';
    }

    /**
     * @return int
     */
    public static function count()
    {
        return count(self::$cache);
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    public static function get($id)
    {
        return isset(self::$cache[$id]) ? self::$cache[$id]['value'] : null;
    }

    /**
     * @param string $id
     *
     * @return array|null
     */
    public static function getMetadata($id)
    {
        return isset(self::$cache[$id]) ? self::$cache[$id]['@metadata'] : null;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public static function has($id)
    {
        return isset(self::$cache[$id]);
    }

    /**
     * @return array
     */
    public static function keys()
    {
        return array_keys(self::$cache);
    }

    /**
     * Set an entry in the container.
     *
     * @param array $cachedMap
     * @param mixed $content
     *
     * @return mixed|bool|null
     */
    private static function setInCache($cachedMap, $content)
    {
        // if is not a class set the entry value in DIC
        if (false === isset($content['class'])) {
            return self::getFromEnvOrDICParams($content);
        }

        // otherwise it's a class, so extract variables
        extract($content);

        $methodArgsToInject = self::getArgumentsToInject($cachedMap, isset($method_arguments) ? $method_arguments : null);
        $classArgsToInject = self::getArgumentsToInject($cachedMap, isset($arguments) ? $arguments : null);

        try {
            return self::instantiateTheClass($class, $classArgsToInject, isset($method) ? $method : null, $methodArgsToInject);
        } catch (\Error $error) {
            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $class
     * @param array $classArguments
     * @param null $method
     * @param array $methodArguments
     *
     * @return mixed|bool
     *
     * @throws \ReflectionException
     */
    private static function instantiateTheClass($class, array $classArguments = [], $method = null, array $methodArguments = [])
    {
        if (false === class_exists($class)) {
            return false;
        }

        $reflected = new \ReflectionClass($class);

        // 1. the class has no method to call
        if (null == $method) {
            return new $class(...$classArguments);
        }

        if (false === $reflected->hasMethod($method)) {
            return false;
        }

        // 2. the method to call is static
        if ($reflected->getMethod($method)->isStatic()) {
            return $class::$method(...$methodArguments);
        }

        // 3. the class has a private constructor
        if ($reflected->hasMethod('__construct') and $reflected->getConstructor()->isPrivate()) {
            return call_user_func_array([$class, $method], $methodArguments);
        }

        // 4. the class has a public constructor
        return (new $class(...$classArguments))->$method(...$methodArguments);
    }

    /**
     * Get the arguments to inject into the class to instantiate within DIC.
     *
     * @param array $cachedMap
     * @param null $providedArguments
     *
     * @return array
     */
    private static function getArgumentsToInject(array $cachedMap = [], $providedArguments = null)
    {
        $returnArguments = [];

        if (null != $providedArguments) {
            foreach ($providedArguments as $argument) {
                $returnArguments[] = self::getArgumentToInject($cachedMap, $argument);
            }
        }

        return $returnArguments;
    }

    /**
     * @param array $cachedMap
     * @param string $argument
     *
     * @return mixed|string|null
     */
    private static function getArgumentToInject(array $cachedMap = [], $argument)
    {
        $id = ltrim($argument, '@');

        return (isset($cachedMap[$id])) ? $cachedMap[$id]['value'] : self::getFromEnvOrDICParams($argument);
    }

    /**
     * @param string $parameter
     *
     * @return mixed|string|null
     */
    private static function getFromEnvOrDICParams($parameter)
    {
        if (is_string($parameter)) {
            if (null !== self::getEnvKey($parameter)) {
                return (getenv(self::getEnvKey($parameter))) ? getenv(self::getEnvKey($parameter)) : $parameter;
            }

            return (DICParams::has(self::getParamKey($parameter))) ? DICParams::get(self::getParamKey($parameter)) : $parameter;
        }

        return $parameter;
    }

    /**
     * Extract from a string like %env(FOO)%
     *
     * @param string $string
     *
     * @return mixed|null
     */
    private static function getEnvKey($string)
    {
        preg_match('~%env\((.*?)\)%~', $string, $matches);

        return (count($matches) > 0) ? $matches[1] : null;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function getParamKey($string)
    {
        return trim($string, '%');
    }
}
