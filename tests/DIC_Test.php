<?php

namespace SimpleDIC\Tests;

use PHPUnit\Framework\TestCase;
use SimpleDIC\DIC;
use SimpleDIC\DICParams;
use SimpleDIC\Dummy\Acme;
use SimpleDIC\Dummy\AcmeCalculator;
use SimpleDIC\Dummy\AcmeParser;
use SimpleDIC\Dummy\AcmeRepo;
use SimpleDIC\Dummy\Client;
use SimpleDIC\Dummy\Controller;
use SimpleDIC\Dummy\Database;
use SimpleDIC\Dummy\Logger;
use SimpleDIC\Dummy\Router;
use SimpleDIC\Exceptions\ParserException;

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
    public function return_false_for_a_wrong_configurated_entries()
    {
        DIC::initFromFile(__DIR__ . '/../config/yaml/wrong.yaml');

        $this->assertTrue(DIC::has('acme-repo'));
        $this->assertTrue(DIC::has('acme-calculator'));
        $this->assertTrue(DIC::has('not-existing'));
        $this->assertFalse(DIC::get('acme-repo'));
        $this->assertFalse(DIC::get('acme-calculator'));
        $this->assertFalse(DIC::get('not-existing'));
    }

    /**
     * @test
     */
    public function init()
    {
        DIC::initFromFile(__DIR__ . '/../config/yaml/config.yaml');

        // 1) simple entries
        $this->assertTrue(DIC::has('dummy-key'));
        $this->assertTrue(DIC::has('dummy-array'));
        $this->assertEquals(DIC::get('dummy-key'), 'dummy-value');
        $this->assertEquals(DIC::get('dummy-array'), [43243,2432,4324,445667]);

        // 2) simple class
        $this->assertTrue(DIC::has('acme'));
        $this->assertInstanceOf(Acme::class, DIC::get('acme'));

        // 3) class with arguments
        $this->assertTrue(DIC::has('acme-parser'));
        $this->assertInstanceOf(AcmeParser::class, DIC::get('acme-parser'));
        $this->assertEquals('string', DIC::get('acme-parser')->parse());

        // 4) class with dependencies
        $this->assertTrue(DIC::has('acme-repo'));
        $this->assertInstanceOf(AcmeRepo::class, DIC::get('acme-repo'));
        $this->assertInstanceOf(Acme::class, DIC::get('acme-repo')->getAcme());

        // 5) class with method and method arguments
        $this->assertTrue(DIC::has('acme-calculator'));
        $this->assertInstanceOf(AcmeCalculator::class, DIC::get('acme-calculator'));
        $this->assertEquals(5, DIC::get('acme-calculator')->calculate());

        $this->assertEquals([
            'dummy-key',
            'dummy-array',
            'three',
            'two',
            'acme',
            'acme-calculator',
            'acme-parser',
            'acme-repo',
        ], DIC::keys());
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

        $this->assertTrue(DIC::has('logger'));
        $this->assertInstanceOf(Logger::class, DIC::get('logger'));

        // JSON
        $dic = DIC::initFromFile(__DIR__ . '/../config/json/controller.json');

        $this->assertTrue(DIC::has('controller'));
        $this->assertInstanceOf(Controller::class, DIC::get('controller'));

        // XML
        $dic = DIC::initFromFile(__DIR__ . '/../config/xml/router.xml');

        $this->assertTrue(DIC::has('router'));
        $this->assertInstanceOf(Router::class, DIC::get('router'));

        // YAML
        $dic = DIC::initFromFile(__DIR__ . '/../config/yaml/database.yaml');

        $this->assertTrue(DIC::has('db'));
        $this->assertInstanceOf(Database::class, DIC::get('db'));
    }

    /**
     * @test
     */
    public function init_after_DICParams()
    {
        DICParams::initFromFile(__DIR__.'/../config/ini/parameters.ini');
        $dic = DIC::initFromFile(__DIR__ . '/../config/ini/client.ini');

        $this->assertTrue(DIC::has('client'));
        $this->assertInstanceOf(Client::class, DIC::get('client'));
        $this->assertEquals('mauretto78', DIC::get('client')->getUsername());
    }

    /**
     * @test
     */
    public function init_with_env_variables()
    {
        DIC::initFromFile(__DIR__ . '/../config/ini/logger.ini');

        $this->assertTrue(DIC::has('logger'));
        $this->assertInstanceOf(Logger::class, DIC::get('logger'));
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

        $this->assertTrue(DIC::has('redis'));
        $this->assertInstanceOf(Client::class, DIC::get('redis'));
        $this->assertEquals('object', DIC::getMetadata('redis')['type']);

        $i1 = DIC::get('redis');
        $i2 = DIC::get('redis');

        $this->assertSame($i1, $i2);
    }
}
