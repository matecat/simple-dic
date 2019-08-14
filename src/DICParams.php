<?php

namespace SimpleDIC;

use SimpleDIC\Parser\Parser;

class DICParams
{
    private static $params = [];

    /**
     * DICParams constructor.
     *
     * @param array $params
     */
    private function __construct(array $params = [])
    {
        foreach ($params as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * @param string $filename
     *
     * @return DICParams
     * @throws \Exception
     */
    public static function initFromFile($filename)
    {
        return new self(Parser::parse($filename));
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public static function get($key)
    {
        if (self::has($key)) {
            return self::$params[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public static function set($key, $value)
    {
        if (false === self::has($key)) {
            self::$params[$key] = $value;
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        return isset(self::$params[$key]);
    }
}
