<?php

namespace SimpleDIC\Parser;

interface ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array
     */
    public function parse($filename);
}
