<?php

namespace SimpleDIC\Parser;

class JsonParser implements ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array|false
     * @throws \Exception
     */
    public function parse($filename)
    {
        if (false === extension_loaded('json')) {
            throw new \Exception('Json extension is not loaded. Add "ext-json": "*" to your composer.json.');
        }

        return json_decode(file_get_contents($filename), true);
    }
}
