<?php

namespace SimpleDIC;

use Pimple\Container;
use SimpleDIC\Parser\Parser;

class DIC
{
    /**
     * @var Container
     */
    private static $container;

    /**
     * DIC constructor.
     *
     * @param array $config
     */
    private function __construct(array $config = [])
    {
        self::$container = new Container();

        $this->resolveDependencies($config);
    }

    /**
     * @return int
     */
    public static function count()
    {
        return count(self::keys());
    }

    /**
     * Initialise the DIC
     *
     * @param array $config
     *
     * @return DIC
     */
    public static function init(array $config = [])
    {
        return new self($config);
    }

    /**
     * @param string $filename
     *
     * @return DIC
     * @throws \Exception
     */
    public static function initFromFile($filename)
    {
        return new self(Parser::parse($filename));
    }

    /**
     * @return array
     */
    public static function keys()
    {
        return self::$container->keys();
    }

    /**
     * Resolve the dependencies and register into the DIC
     *
     * @param array $config
     */
    private function resolveDependencies(array $config = [])
    {
        foreach ($config as $key => $content) {
            self::set($key, $content);
        }
    }

    /**
     *
     * Check for an entry existence within the container
     *
     * @param string $dependency
     *
     * @return bool
     */
    public static function has($dependency)
    {
        return isset(self::$container[$dependency]);
    }

    /**
     * Get an entry from the container.
     *
     * The method returns:
     * - false if the entry has a wrong configuration
     * - NULL if the entry does not exists
     *
     * @param string $dependency
     *
     * @return mixed
     */
    public static function get($dependency)
    {
        if (self::has($dependency)) {
            return self::$container[$dependency];
        }
    }

    /**
     * Set an entry in the container.
     *
     * @param string $key
     * @param array|mixed $content
     *
     * @return mixed|bool|null
     */
    private static function set($key, $content)
    {
        if (false === self::has($key)) {
            self::$container[$key] = function ($c) use ($content) {

                // if is not a class set the entry value in DIC
                if (false === isset($content['class'])) {
                    return self::getFromEnvOrDICParams($content);
                }

                // otherwise it's a class, so extract variables
                $class           = $content['class'];
                $classArguments  = isset($content['arguments']) ? $content['arguments'] : null;
                $method          = isset($content['method']) ? $content['method'] : null;
                $methodArguments = isset($content['method_arguments']) ? $content['method_arguments'] : null;

                $methodArgsToInject = self::getArgumentsToInject($c, $methodArguments);
                $classArgsToInject = self::getArgumentsToInject($c, $classArguments);

                try {
                    return self::instantiateTheClass($class, $classArgsToInject, $method, $methodArgsToInject);
                } catch (\Error $error) {
                    return false;
                } catch (\Exception $exception) {
                    return false;
                }
            };

            return null;
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
        if ($reflected->getConstructor()->isPrivate()) {
            return call_user_func_array([$class, $method], $methodArguments);
        }

        // 4. the class has a public constructor
        return (new $class(...$classArguments))->$method(...$methodArguments);
    }

    /**
     * Get the arguments to inject into the class to instantiate within DIC.
     *
     * @param Container $c
     * @param null      $providedArguments
     *
     * @return array
     */
    private static function getArgumentsToInject(Container $c, $providedArguments = null)
    {
        $returnArguments = [];

        if (null != $providedArguments) {
            foreach ($providedArguments as $argument) {
                $returnArguments[] = self::getArgumentToInject($argument, $c);
            }
        }

        return $returnArguments;
    }

    /**
     * @param   string        $argument
     * @param Container $c
     *
     * @return mixed|string|null
     */
    private static function getArgumentToInject($argument, Container $c)
    {
        if (isset($c[ltrim($argument, '@')])) {
            return $c[ltrim($argument, '@')];
        }

        return self::getFromEnvOrDICParams($argument);
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
