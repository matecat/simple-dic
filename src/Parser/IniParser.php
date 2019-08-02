<?php

namespace SimpleDIC\Parser;

class IniParser implements ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array
     */
    public function parse($filename)
    {
        return parse_ini_file($filename, true);
    }
}
