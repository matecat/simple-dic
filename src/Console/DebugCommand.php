<?php

namespace SimpleDIC\Console;

use SimpleDIC\DIC;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugCommand extends Command
{
    protected function configure()
    {
        $this
                ->setName('dic:debug')
                ->setDescription('Dumps the entry list in the DIC.')
                ->setHelp('This command shows you to complete entry list in the DIC from a valid config array.')
                ->addArgument('config_file', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $input->getArgument('config_file');

        if (false === file_exists($configFile)) {
            throw new \InvalidArgumentException($configFile . ' is not a valid file');
        }

        DIC::initFromFile($configFile);

        $keys = DIC::keys();
        asort($keys);

        $table = new Table($output);
        $table->setHeaders(['#', 'Alias', 'Content']);

        $i = 1;
        foreach ($keys as $key) {
            $table->setRow($i, [$i, $key, $this->getValue($key)]);
            $i++;
        }

        $table->render();
    }

    /**
     * @param string $key
     *
     * @return mixed|string
     */
    private function getValue($key)
    {
        $dicKey = DIC::get($key);

        if (false === $dicKey) {
            return  '<fg=red>Invalid Entry</>';
        }

        if (is_object($dicKey)) {
            return '<fg=cyan>' . get_class($dicKey) . '</>';
        }

        if (is_array($dicKey)) {
            return '<fg=green>' . implode("|", $dicKey) . '</>';
        }

        return '<fg=yellow>' . $dicKey . '</>';
    }
}
