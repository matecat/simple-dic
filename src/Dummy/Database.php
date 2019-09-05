<?php

namespace Matecat\SimpleDIC\Dummy;

class Database
{
    /**
     * @var string
     */
    private $db;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * Database constructor.
     *
     * @param string $db
     * @param string $username
     * @param string $password
     */
    public function __construct($db, $username, $password)
    {
        $this->db = $db;
        $this->username = $username;
        $this->password = $password;
    }
}
