<?php

namespace Matecat\SimpleDIC\Console;

use Matecat\SimpleDIC\DIC;
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

        DIC::destroyCacheDir($cacheDir);

        $output->writeln('<fg=green>Cache was successfully cleared.</>');
    }
}
