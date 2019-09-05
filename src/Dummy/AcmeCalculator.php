<?php

namespace Matecat\SimpleDIC\Dummy;

class AcmeCalculator
{
    private $a;
    private $b;

    /**
     * AcmeCalculator constructor.
     *
     * @param int $a
     * @param int $b
     */
    private function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @return int
     */
    public function calculate()
    {
        return (int)($this->a + $this->b);
    }

    /**
     * @param int $a
     * @param int $b
     *
     * @return AcmeCalculator
     */
    public function init($a, $b)
    {
        return new self($a, $b);
    }
}
