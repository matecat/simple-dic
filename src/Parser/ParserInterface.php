<?php

namespace Matecat\SimpleDIC\Parser;

interface ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array|false
     */
    public function parse($filename);
}
