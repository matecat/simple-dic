<?php

namespace SimpleDIC\Tests;

use PHPUnit\Framework\TestCase;
use SimpleDIC\DIC;
use SimpleDIC\Dummy\Acme;
use SimpleDIC\Dummy\AcmeCalculator;
use SimpleDIC\Dummy\AcmeParser;
use SimpleDIC\Dummy\AcmeRepo;
use SimpleDIC\Dummy\Database;
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
        $config = Yaml::parseFile(__DIR__.'/../config/wrong.yaml');
        $dic = DIC::init($config);

        $this->assertFalse($dic::get('acme-repo'));
        $this->assertFalse($dic::get('acme-calculator'));
    }

    /**
     * @test
     */
    public function return_entries()
    {
        $config = Yaml::parseFile(__DIR__.'/../config/config.yaml');
        $dic = DIC::init($config);

        // 1) simple entry
        $this->assertTrue($dic::has('dummy-key'));
        $this->assertEquals($dic::get('dummy-key'), 'dummy-value');

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
    public function return_entriesfdsfsdfds()
    {
        $config = Yaml::parseFile(__DIR__.'/../config/database.yaml');
        $dic = DIC::init($config);

        $this->assertTrue($dic::has('db'));
        $this->assertInstanceOf(Database::class, $dic::get('db'));
    }
}
