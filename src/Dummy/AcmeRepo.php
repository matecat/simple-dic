<?php

namespace SimpleDIC\Dummy;

class AcmeRepo
{
    /**
     * @var Acme
     */
    private $acme;

    /**
     * AcmeRepo constructor.
     *
     * @param Acme $acme
     */
    public function __construct(Acme $acme)
    {
        $this->acme = $acme;
    }

    /**
     * @return Acme
     */
    public function getAcme()
    {
        return $this->acme;
    }
}
