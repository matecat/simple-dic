<?php

namespace Matecat\SimpleDIC;

use Matecat\SimpleDIC\Exceptions\ConfigException;
use Matecat\SimpleDIC\Parser\Parser;

class DIC
{
    /**
     * @var array
     */
    private static $values;

    /**
     * @var array
     */
    private static $cache;

    /**
     * @var string
     */
    private static $cacheDir;

    /**
     * @var string
     */
    private static $sha;

    /**
     * @param string $filename
     *
     * @return DIC
     * @throws Exceptions\ParserException
     */
    public static function initFromFile($filename)
    {
        self::$values = [];
        self::$sha = sha1_file($filename);

        $cacheFile = self::getCacheDir(). DIRECTORY_SEPARATOR .self::$sha.'.php';

        if (false === file_exists($cacheFile)) {
            if (false === is_dir(self::getCacheDir()) and false === mkdir(self::getCacheDir(), 0755, true)) {
                throw new \Exception(self::getCacheDir() . ' is not a writable directory.');
            }

            if (false === file_put_contents($cacheFile, '<?php return unserialize(\'' . serialize(Parser::parse($filename)) . '\');' . PHP_EOL)) {
                throw new \Exception(' Can\'t write cache file.');
            }
        }

        self::$cache = include($cacheFile);

        return new self;
    }

    /**
     * @param string $dir
     *
     * @return mixed
     */
    public static function setCacheDir($dir)
    {
        return self::$cacheDir = $dir;
    }

    /**
     * @return string
     */
    private static function getCacheDir()
    {
        return (!empty(self::$cacheDir)) ? self::$cacheDir : __DIR__.'/../_cache';
    }

    /**
     * @return int
     * @throws ConfigException
     */
    public static function count()
    {
        self::checkForCache();

        return count(self::$values);
    }

    /**
     * @throws ConfigException
     */
    private static function checkForCache()
    {
        if(empty(self::$cache)){
            throw new ConfigException('No config file was provided. You MUST use before initFromFile() method.');
        }
    }

    /**
     * @param string $cacheDir
     */
    public static function destroyCacheDir($cacheDir)
    {
        if (false === is_dir($cacheDir)) {
            throw new \InvalidArgumentException($cacheDir . ' is not a valid dir');
        }

        foreach (scandir($cacheDir) as $file) {
            if (!in_array($file, ['.', '..'])) {

                // destroy apcu
                if (self::isApcuEnabled()) {
                    $filePath = $cacheDir . DIRECTORY_SEPARATOR . $file;
                    $array = include($filePath);

                    foreach ($array as $id => $entry) {
                        apcu_delete(self::getApcuKey($id));
                    }
                }

                // delete file
                unlink($filePath);
            }
        }
    }

    /**
     * @param string $id
     *
     * @return mixed
     * @throws ConfigException
     */
    public static function get($id)
    {
        self::checkForCache();

        // if APCU is enabled return the entry from APCU store
        if (self::isApcuEnabled() and apcu_exists(self::getApcuKey($id))) {
            return apcu_fetch(self::getApcuKey($id));
        }

        // otherwise set the value in memory if it is not present
        if (false === isset(self::$values[$id])) {
            self::setValue($id);
        }

        // return the entry from memory
        return self::$values[$id];
    }

    /**
     * Set an entry in the container.
     *
     * @param string $id
     *
     * @return bool
     * @throws ConfigException
     */
    private static function setValue($id)
    {
        $content = self::$cache[$id];

        // if is not a class set the entry value in DIC
        if (false === isset($content['class'])) {
            self::$values[$id] = self::getFromEnvOrDICParams($content);

            if (self::isApcuEnabled()) {
                self::tryToStoreInApcu($id);
            }

            return true;
        }

        // otherwise it's a class, so extract variables
        extract($content);

        $methodArgsToInject = self::getArgumentsToInject(isset($method_arguments) ? $method_arguments : null);
        $classArgsToInject = self::getArgumentsToInject(isset($arguments) ? $arguments : null);

        try {
            self::$values[$id] = self::instantiateTheClass($class, $classArgsToInject, isset($method) ? $method : null, $methodArgsToInject);

            if (self::isApcuEnabled()) {
                self::tryToStoreInApcu($id);
            }

            return true;
        } catch (\Error $error) {
            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private static function isApcuEnabled()
    {
        return (extension_loaded('apc') && ini_get('apc.enabled'));
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private static function getApcuKey($id)
    {
        return md5(self::$sha . DIRECTORY_SEPARATOR . $id);
    }

    /**
     * @param string $id
     */
    private static function tryToStoreInApcu($id)
    {
        try {
            apcu_add(self::getApcuKey($id), self::$values[$id]);
        } catch (\Exception $e) {
            // nothing to do, continue
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     * @throws ConfigException
     */
    public static function has($id)
    {
        self::checkForCache();

        if (self::isApcuEnabled() and apcu_exists(self::getApcuKey($id))) {
            return true;
        }

        return isset(self::$values[$id]);
    }

    /**
     * @return array
     * @throws ConfigException
     */
    public static function keys()
    {
        self::checkForCache();

        return array_keys(self::$cache);
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
     * @param null $providedArguments
     *
     * @return array
     * @throws ConfigException
     */
    private static function getArgumentsToInject($providedArguments = null)
    {
        $returnArguments = [];

        if (null != $providedArguments) {
            foreach ($providedArguments as $argument) {
                $returnArguments[] = self::getArgumentToInject($argument);
            }
        }

        return $returnArguments;
    }

    /**
     * @param string $argument
     *
     * @return mixed|string|null
     * @throws ConfigException
     */
    private static function getArgumentToInject($argument)
    {
        $id = ltrim($argument, '@');

        return (isset(self::$cache[$id])) ? self::get($id) : self::getFromEnvOrDICParams($argument);
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
