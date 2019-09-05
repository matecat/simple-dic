<?php

namespace Matecat\SimpleDIC\Dummy;

class Logger
{
    /**
     * @var null
     */
    private $foo;

    /**
     * Logger constructor.
     *
     * @param null $foo
     */
    public function __construct($foo = null)
    {
        $this->foo = $foo;
    }

    /**
     * @return null
     */
    public function getFoo()
    {
        return $this->foo;
    }
}
