<?php

namespace SimpleDIC\Parser;

class Parser
{
    /**
     * @var array
     */
    private static $allowedExtensions = ['json', 'ini', 'xml', 'yaml', 'yml'];

    /**
     * @param string $filename
     *
     * @return array
     * @throws \Exception
     */
    public static function parse($filename)
    {
        $ext = self::getExt($filename);

        if (false === in_array($ext, self::$allowedExtensions)) {
            throw new \InvalidArgumentException($ext . ' is not a valid configuration file.');
        }

        $parser = self::getParser($ext);

        return $parser->parse($filename);
    }

    /**
     * @param string $filename
     *
     * @return mixed
     */
    private static function getExt($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * @param string $ext
     *
     * @return ParserInterface
     */
    private static function getParser($ext)
    {
        switch ($ext) {
            case 'ini':
                return new IniParser();

            case 'json':
                return new JsonParser();

            case 'xml':
                return new XmlParser();

            case 'yaml':
            case 'yml':
                return new YamlParser();
        }
    }
}
