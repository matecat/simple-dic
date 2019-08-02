<?php

namespace SimpleDIC\Tests;

use PHPUnit\Framework\TestCase;
use SimpleDIC\DICParams;

class DICParamsTest extends TestCase
{
    /**
     * @test
     */
    public function return_null_a_not_existing_entry()
    {
        $dicParams = DICParams::init([]);

        $this->assertNull($dicParams::get('key'));
    }

    /**
     * @test
     */
    public function init_from_file()
    {
        $dicParams = DICParams::initFromFile(__DIR__.'/../config/ini/parameters.ini');

        $this->assertTrue($dicParams::has('client_account'));
        $this->assertEquals($dicParams::get('client_account'), 'mauretto78');
        $this->assertTrue($dicParams::has('client_password'));
        $this->assertEquals($dicParams::get('client_password'), 'xcvbrvdsfdsfdsfsd');
    }
}
