<?php

namespace Drachenkatze\ZoneChecker\Commands;

use DirectoryIterator;
use Drachenkatze\ZoneChecker\GlobalConfig;
use Drachenkatze\ZoneChecker\ZoneChecker;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'execute';

    protected function configure()
    {
        // ...
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $zoneDirectory = GlobalConfig::getInstance()->getZoneDirectory();
        $logger = new ConsoleLogger($output);
        $logger->info("Reading zones from <comment>{$zoneDirectory}</comment>");

        $zones = [];
        foreach (new DirectoryIterator($zoneDirectory) as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            if ($fileInfo->getExtension() == "zone") {
                $zoneName = str_replace(".zone", "", $fileInfo->getFilename());
                $configFile = $zoneDirectory . "/" . str_replace(".zone", ".config.yml", $fileInfo->getFilename());

                if (!file_exists($configFile)) {
                    $logger->warning("Zone <comment>{$fileInfo->getFilename()}</comment> has no config file (Expected file: <comment>{$configFile}</comment>)");
                } else {
                    $logger->info("Zone <comment>{$fileInfo->getFilename()}</comment> found.");
                    $zones[] = [
                        "zoneName" => $zoneName,
                        "zoneFile" => $zoneDirectory . "/" . $fileInfo->getFilename(),
                        "zoneConfig" => $configFile
                    ];
                }
            }
        }

        /**
         * @var $output ConsoleOutputInterface
         */
        //$totalProgressSection = $output->section();
        // creates a new progress bar (50 units)
        //$progressBar = new ProgressBar($totalProgressSection, count($zones));
        //$progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');

        /*$progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
        $progressBar->setProgressCharacter("<fg=green>➤</>");

        $progressBar->start();*/

        foreach ($zones as $zone) {
            $logger->info("Processing zone <info>{$zone["zoneName"]}</info>");
            $checker = new ZoneChecker($input, $output, $zone["zoneName"], $zone["zoneFile"], $zone["zoneConfig"]);
            $checker->check();
        }


        return 0;
    }
}