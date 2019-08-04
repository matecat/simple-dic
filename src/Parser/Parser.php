<?php

namespace SimpleDIC\Parser;

use SimpleDIC\Exceptions\ParserException;

class Parser
{
    /**
     * @var array
     */
    private static $allowedExtensions = ['json', 'ini', 'xml', 'yaml', 'yml'];

    /**
     * @param $filename
     *
     * @return array
     * @throws ParserException
     */
    public static function parse($filename)
    {
        $ext = self::getExt($filename);

        if (false === in_array($ext, self::$allowedExtensions)) {
            throw new ParserException($ext . ' is not a valid extension [json, ini, xml, yaml, yml are supported].');
        }

        $parser = self::getParser($ext);

        try{
            return $parser->parse($filename);
        } catch (\Exception $e){
            throw new ParserException($filename . ' cannot be parsed [' . $ext . ' driver used]' );
        }
    }

    /**
     * @param string $filename
     *
     * @return string
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
