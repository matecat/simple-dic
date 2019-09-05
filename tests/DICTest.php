<?php

namespace Matecat\SimpleDIC\Tests;

use PHPUnit\Framework\TestCase;
use Matecat\SimpleDIC\DIC;
use Matecat\SimpleDIC\DICParams;
use Matecat\SimpleDIC\Dummy\Acme;
use Matecat\SimpleDIC\Dummy\AcmeCalculator;
use Matecat\SimpleDIC\Dummy\AcmeParser;
use Matecat\SimpleDIC\Dummy\AcmeRepo;
use Matecat\SimpleDIC\Dummy\Client;
use Matecat\SimpleDIC\Dummy\Controller;
use Matecat\SimpleDIC\Dummy\Database;
use Matecat\SimpleDIC\Dummy\Logger;
use Matecat\SimpleDIC\Dummy\NoSerialize;
use Matecat\SimpleDIC\Dummy\Router;
use Matecat\SimpleDIC\Exceptions\ConfigException;
use Matecat\SimpleDIC\Exceptions\ParserException;

class DIC_Test extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        putenv("FOO=bar");
    }

    /**
     * @test
     */
    public function throws_ConfigException_if_no_config_was_provided()
    {
        try {
            DIC::count();
        } catch (\Exception $e){
            $this->assertInstanceOf(ConfigException::class, $e);
            $this->assertEquals($e->getMessage(), 'No config file was provided. You MUST use before initFromFile() method.');
        }

        try {
            DIC::get('acme');
        } catch (\Exception $e){
            $this->assertInstanceOf(ConfigException::class, $e);
            $this->assertEquals($e->getMessage(), 'No config file was provided. You MUST use before initFromFile() method.');
        }

        try {
            DIC::has('acme');
        } catch (\Exception $e){
            $this->assertInstanceOf(ConfigException::class, $e);
            $this->assertEquals($e->getMessage(), 'No config file was provided. You MUST use before initFromFile() method.');
        }

        try {
            DIC::keys();
        } catch (\Exception $e){
            $this->assertInstanceOf(ConfigException::class, $e);
            $this->assertEquals($e->getMessage(), 'No config file was provided. You MUST use before initFromFile() method.');
        }
    }

    /**
     * @test
     */
    public function return_false_for_a_wrong_configurated_entries()
    {
        DIC::initFromFile(__DIR__ . '/../config/yaml/wrong.yaml');

        $this->assertFalse(DIC::has('acme-repo'));
        $this->assertFalse(DIC::has('acme-calculator'));
        $this->assertFalse(DIC::get('not-existing'));
    }

    /**
     * @test
     */
    public function init()
    {
        DIC::initFromFile(__DIR__ . '/../config/yaml/config.yaml');

        // 1) simple entries
        $this->assertEquals(DIC::get('dummy-key'), 'dummy-value');
        $this->assertEquals(DIC::get('dummy-array'), [43243,2432,4324,445667]);
        $this->assertTrue(DIC::has('dummy-key'));
        $this->assertTrue(DIC::has('dummy-array'));

        // 2) simple class
        $this->assertInstanceOf(Acme::class, DIC::get('acme'));
        $this->assertTrue(DIC::has('acme'));

        // 3) class with arguments
        $this->assertInstanceOf(AcmeParser::class, DIC::get('acme-parser'));
        $this->assertTrue(DIC::has('acme-parser'));
        $this->assertEquals('string', DIC::get('acme-parser')->parse());

        // 4) class with dependencies
        $this->assertInstanceOf(AcmeRepo::class, DIC::get('acme-repo'));
        $this->assertTrue(DIC::has('acme-repo'));
        $this->assertInstanceOf(Acme::class, DIC::get('acme-repo')->getAcme());

        // 5) class with method and method arguments
        $this->assertInstanceOf(AcmeCalculator::class, DIC::get('acme-calculator'));
        $this->assertTrue(DIC::has('acme-calculator'));
        $this->assertEquals(5, DIC::get('acme-calculator')->calculate());

        $this->assertEquals(8, DIC::count());
    }

    /**
     * @test
     */
    public function init_from_file_exception()
    {
        try {
            DIC::initFromFile(__DIR__ . '/../config/txt/file.txt');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ParserException::class, $e);
            $this->assertEquals('txt is not a valid extension [json, ini, xml, yaml, yml are supported].', $e->getMessage());
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function throw_ParserException()
    {
        $file = __DIR__ . '/../config/ini/invalid.ini';

        try {
            DIC::initFromFile($file);
        } catch (\Exception $e) {
            $this->assertInstanceOf(ParserException::class, $e);
            $this->assertEquals($e->getMessage(), $file . ' cannot be parsed [ini driver used]');
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function init_from_file()
    {
        // INI
        $dic = DIC::initFromFile(__DIR__ . '/../config/ini/logger.ini');

        $this->assertInstanceOf(Logger::class, DIC::get('logger'));
        $this->assertTrue(DIC::has('logger'));

        // JSON
        $dic = DIC::initFromFile(__DIR__ . '/../config/json/controller.json');

        $this->assertInstanceOf(Controller::class, DIC::get('controller'));
        $this->assertTrue(DIC::has('controller'));

        // XML
        $dic = DIC::initFromFile(__DIR__ . '/../config/xml/router.xml');

        $this->assertInstanceOf(Router::class, DIC::get('router'));
        $this->assertTrue(DIC::has('router'));

        // YAML
        $dic = DIC::initFromFile(__DIR__ . '/../config/yaml/database.yaml');

        $this->assertInstanceOf(Database::class, DIC::get('db'));
        $this->assertTrue(DIC::has('db'));
    }

    /**
     * @test
     */
    public function init_after_DICParams()
    {
        DICParams::initFromFile(__DIR__.'/../config/ini/parameters.ini');
        $dic = DIC::initFromFile(__DIR__ . '/../config/ini/client.ini');

        $this->assertInstanceOf(Client::class, DIC::get('client'));
        $this->assertTrue(DIC::has('client'));
        $this->assertEquals('mauretto78', DIC::get('client')->getUsername());
    }

    /**
     * @test
     */
    public function init_with_env_variables()
    {
        DIC::initFromFile(__DIR__ . '/../config/ini/logger.ini');

        $this->assertInstanceOf(Logger::class, DIC::get('logger'));
        $this->assertTrue(DIC::has('logger'));
        $this->assertEquals('bar', DIC::get('logger')->getFoo());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function init_with_redis()
    {
        DIC::setCacheDir(__DIR__.'/../_cache_custom');
        DIC::initFromFile(__DIR__ . '/../config/ini/redis.ini');

        $this->assertInstanceOf(Client::class, DIC::get('redis'));
        $this->assertTrue(DIC::has('redis'));

        $i1 = DIC::get('redis');
        $i2 = DIC::get('redis');

        $this->assertSame($i1, $i2);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function init_with_not_serializable_class()
    {
        DIC::initFromFile(__DIR__ . '/../config/ini/no-serialize.ini');

        $this->assertInstanceOf(NoSerialize::class, DIC::get('ser'));
        $this->assertTrue(DIC::has('ser'));
    }
}
