<?php

namespace Matecat\SimpleDIC\Dummy;

class NoSerialize
{
    private $param1;
    private $param2;

    public function __construct($param1, $param2)
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }

    public function __sleep()
    {
        throw new \RuntimeException('This class cannot be serialized.');
    }
}
