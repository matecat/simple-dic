<?php

namespace SimpleDIC\Dummy;

class RedisHandler
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $dns;

    /**
     * RedisHandler constructor.
     *
     * @param string $dns
     */
    public function __construct($dns)
    {
        $this->dns = $dns;
    }

    /**
     * @return Client
     */
    public function getConnection()
    {
        if ($this->client === null) {
            $this->client = new Client('dummy', '12432');
        }

        return $this->client;
    }
}
