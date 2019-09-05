<?php

namespace Matecat\SimpleDIC\Dummy;

class AcmeParser
{
    /**
     * @var string
     */
    private $string;

    /**
     * AcmeParser constructor.
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * @return string
     */
    public function parse()
    {
        // just for test
        return $this->string;
    }
}
