<?php

use Matecat\SimpleDIC\DIC;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__.'/../vendor/autoload.php';

$config = __DIR__ . '/../config/yaml/config.yaml';
DIC::initFromFile($config);

$max = 100000;

$start = microtime(true);
$memoryUsage = memory_get_usage();
for ($i=0;$i<$max;$i++) {
    DIC::get('acme-calculator');
}

$stringval = microtime(true) - $start;
$numericval = sscanf((string)$stringval, "%f")[0];
$seconds = number_format($numericval, 2);

echo PHP_EOL;
echo '----------------------------------';
echo PHP_EOL;
echo 'LOOPING '.$max.' ITEMS';
echo PHP_EOL;
echo '----------------------------------';
echo PHP_EOL;
echo 'TIME ELAPSED(millisec): ' . (float)$seconds * 1000;
echo PHP_EOL;
echo '----------------------------------';
echo PHP_EOL;
echo 'MEMORY USAGE: '. (memory_get_usage() - $memoryUsage);
echo PHP_EOL;
echo '----------------------------------';
echo PHP_EOL;
