<?php

namespace SimpleDIC\Dummy;

class Controller
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * Controller constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function render()
    {
        // do someting
    }
}
