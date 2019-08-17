<?php

namespace SimpleDIC\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheDestroyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('dic:cache-destroy')
            ->setDescription('Destroy the cache created by DIC.')
            ->setHelp('This command try to destroy cache files created by DIC and clear apcu cache.')
            ->addArgument('cache_dir', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheDir = !empty($input->getArgument('cache_dir')) ? $input->getArgument('cache_dir') : __DIR__.'/../../_cache';

        if (false === is_dir($cacheDir)) {
            throw new \InvalidArgumentException($cacheDir . ' is not a valid dir');
        }

        foreach (scandir($cacheDir) as $file) {
            if (!in_array($file, ['.', '..'])) {

                // destroy apcu
                if (extension_loaded('apc') && ini_get('apc.enabled')) {
                    $filePath = $cacheDir . DIRECTORY_SEPARATOR . $file;
                    $array = include($filePath);

                    foreach ($array as $id => $entry) {
                        apcu_delete(md5(sha1_file($filePath). DIRECTORY_SEPARATOR .$id));
                    }
                }

                // delete file
                unlink($filePath);
            }
        }

        $output->writeln('<fg=green>Cache was successfully cleared.</>');
    }
}
