<?php

namespace Matecat\SimpleDIC\Dummy;

class Client
{
    /**
     * @var string $username
     */
    private $username;

    /**
     * @var string $password
     */
    private $password;

    /**
     * Client constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function __sleep()
    {
        throw new \RuntimeException('This class cannot be serialized.');
    }
}
