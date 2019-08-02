<?php

namespace SimpleDIC\Dummy;

class Router
{
    /**
     * @var string
     */
    private $route;

    /**
     * @var string
     */
    private $name;

    /**
     * Router constructor.
     *
     * @param string $route
     * @param string $name
     */
    public function __construct($route, $name)
    {
        $this->route = $route;
        $this->name = $name;
    }
}
