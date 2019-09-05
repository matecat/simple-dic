<?php

namespace Matecat\SimpleDIC\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{
    /**
     * @param string $filename
     *
     * @return array
     * @throws \Exception
     */
    public function parse($filename)
    {
        if (false === class_exists(Yaml::class)) {
            throw new \Exception('YAML class was not found, you must install it. Run "composer require symfony/yaml"');
        }

        return Yaml::parseFile($filename);
    }
}
