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
    public static function set($key, $content)
    {
        if (false === self::has($key)) {
            self::$container[$key] = function ($c) use ($content) {

                // if is not a class set the entry value in DIC
                if (false === isset($content['class'])) {
                    return self::getFromDICParams($content);
                }

                // otherwise it's a class, so extract variables
                $class           = isset($content['class']) ? $content['class'] : null;
                $classArguments  = isset($content['arguments']) ? $content['arguments'] : null;
                $method          = isset($content['method']) ? $content['method'] : null;
                $methodArguments = isset($content['method_arguments']) ? $content['method_arguments'] : null;

                // if specified, call a method
                if ($method) {

                    // if specified, call the method with provided arguments
                    if ($methodArguments) {
                        try {
                            return call_user_func_array([$class, $method], self::getArgumentsToInject($c, $methodArguments));
                        } catch (\Error $error) {
                            return false;
                        } catch (\Exception $exception) {
                            return false;
                        }
                    }

                    // if not, call the method with no arguments
                    try {
                        return call_user_func([$class, $method]);
                    } catch (\Error $error) {
                        return false;
                    } catch (\Exception $exception) {
                        return false;
                    }
                }

                // if the method is not specified, call the constructor
                try {
                    return new $class(...self::getArgumentsToInject($c, $classArguments));
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

        return self::getFromDICParams($argument);
    }

    /**
     * @param string $parameter
     *
     * @return mixed|string|null
     */
    private static function getFromDICParams($parameter)
    {
        $key = trim($parameter, '%');

        return (DICParams::has($key)) ? DICParams::get($key) : $key;
    }
}
