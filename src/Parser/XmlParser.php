<?php

namespace SimpleDIC\Parser;

class XmlParser implements ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array
     * @throws \Exception
     */
    public function parse($filename)
    {
        if (false === extension_loaded('simplexml')) {
            throw new \Exception('SimpleXML extension is not loaded. Add "ext-simplexml": "*" to your composer.json.');
        }

        return json_decode(json_encode(simplexml_load_file($filename)),true);
    }
}
