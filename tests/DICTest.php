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
use Symfony\Component\Yaml\Yaml;

class DICTest extends TestCase
{
    /**
     * @test
     */
    public function return_null_a_not_existing_entry()
    {
        $dic = DIC::init([]);

        $this->assertNull($dic::get('key'));
    }

    /**
     * @test
     */
    public function return_false_for_a_wrong_configurated_entries()
    {
        $config = Yaml::parseFile(__DIR__ . '/../config/yaml/wrong.yaml');
        $dic = DIC::init($config);

        $this->assertFalse($dic::get('acme-repo'));
        $this->assertFalse($dic::get('acme-calculator'));
        $this->assertFalse($dic::get('not-existing'));
    }

    /**
     * @test
     */
    public function init()
    {
        $config = Yaml::parseFile(__DIR__ . '/../config/yaml/config.yaml');
        $dic = DIC::init($config);

        // 1) simple entries
        $this->assertTrue($dic::has('dummy-key'));
        $this->assertTrue($dic::has('dummy-array'));
        $this->assertEquals($dic::get('dummy-key'), 'dummy-value');
        $this->assertEquals($dic::get('dummy-array'), [43243,2432,4324,445667]);

        // 2) simple class
        $this->assertTrue($dic::has('acme'));
        $this->assertInstanceOf(Acme::class, $dic::get('acme'));

        // 3) class with arguments
        $this->assertTrue($dic::has('acme-parser'));
        $this->assertInstanceOf(AcmeParser::class, $dic::get('acme-parser'));
        $this->assertEquals('string', $dic::get('acme-parser')->parse());

        // 4) class with dependencies
        $this->assertTrue($dic::has('acme-repo'));
        $this->assertInstanceOf(AcmeRepo::class, $dic::get('acme-repo'));
        $this->assertInstanceOf(Acme::class, $dic::get('acme-repo')->getAcme());

        // 5) class with method and method arguments
        $this->assertTrue($dic::has('acme-calculator'));
        $this->assertInstanceOf(AcmeCalculator::class, $dic::get('acme-calculator'));
        $this->assertEquals(5, $dic::get('acme-calculator')->calculate());

        $this->assertEquals([
            'dummy-key',
            'dummy-array',
            'three',
            'two',
            'acme',
            'acme-calculator',
            'acme-parser',
            'acme-repo',
        ], $dic::keys());
        $this->assertEquals(8, $dic::count());
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
        } catch (\Exception $e){
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

        $this->assertTrue($dic::has('logger'));
        $this->assertInstanceOf(Logger::class, $dic::get('logger'));

        // JSON
        $dic = DIC::initFromFile(__DIR__ . '/../config/json/controller.json');

        $this->assertTrue($dic::has('controller'));
        $this->assertInstanceOf(Controller::class, $dic::get('controller'));

        // XML
        $dic = DIC::initFromFile(__DIR__ . '/../config/xml/router.xml');

        $this->assertTrue($dic::has('router'));
        $this->assertInstanceOf(Router::class, $dic::get('router'));

        // YAML
        $dic = DIC::initFromFile(__DIR__ . '/../config/yaml/database.yaml');

        $this->assertTrue($dic::has('db'));
        $this->assertInstanceOf(Database::class, $dic::get('db'));
    }

    /**
     * @test
     */
    public function init_after_DICParams()
    {
        DICParams::initFromFile(__DIR__.'/../config/ini/parameters.ini');
        $dic = DIC::initFromFile(__DIR__ . '/../config/ini/client.ini');

        $this->assertTrue($dic::has('client'));
        $this->assertInstanceOf(Client::class, $dic::get('client'));
        $this->assertEquals('mauretto78', $dic::get('client')->getUsername());
    }

    /**
     * @test
     */
    public function init_with_env_variables()
    {
        putenv("FOO=bar");

        $dic = DIC::initFromFile(__DIR__ . '/../config/ini/logger.ini');

        $this->assertTrue($dic::has('logger'));
        $this->assertInstanceOf(Logger::class, $dic::get('logger'));
        $this->assertEquals('bar', $dic::get('logger')->getFoo());
    }
}
