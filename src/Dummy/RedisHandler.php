<?php

namespace SimpleDIC\Dummy;

class RedisHandler
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @return Client
     */
    public function getConnection()
    {
        if ($this->client === null) {
            $this->client = new Client('mauretto78', '12432');
        }

        return $this->client;
    }
}
