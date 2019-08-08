<?php
use SimpleDIC\DIC;

include __DIR__.'/../vendor/autoload.php';

DIC::initFromFile(__DIR__ . '/../config/ini/redis.ini');

$max = 1000000;
$start = microtime(true);
$memoryUsage = memory_get_usage();
for ($i=0;$i<$max;$i++){
    DIC::get('redis');
}

$stringval = microtime(true) - $start;
$numericval = sscanf((string)$stringval, "%f")[0];
$seconds = number_format($numericval, 8);

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