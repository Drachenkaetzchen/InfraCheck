<?php
namespace Drachenkatze\ZoneChecker;

use Badcow\DNS\Parser\Parser;
use Drachenkatze\ZoneChecker\Checkers\AbstractChecker;
use Drachenkatze\ZoneChecker\Checkers\CheckNull;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ZoneChecker {
    /**
     * @var string
     */
    private $zoneFile;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var string
     */
    private $zoneName;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Badcow\DNS\Zone
     */
    private $zone;

    private static $checkers = ['Drachenkatze\ZoneChecker\Checkers\CheckHttp',
        'Drachenkatze\ZoneChecker\Checkers\CheckIp',
        'Drachenkatze\ZoneChecker\Checkers\CheckTarget',
        'Drachenkatze\ZoneChecker\Checkers\CheckNull'];

    private $output;
    private $input;

    public function __construct (InputInterface $input, OutputInterface $output, $zoneName, $zoneFile, $configFile) {

        if (substr($zoneName, "-1") != ".") {
            $zoneName .= ".";
        }

        $this->output = $output;
        $this->input = $input;
        $this->zoneName = $zoneName;
        $this->zoneFile = $zoneFile;
        $this->configFile = $configFile;
    }

    public function check () {
        $ignoreTypes = [ "SOA", "NS", "TXT"];
        $this->parseConfig();
        $this->parseZone();

        $progressBar = new ProgressBar($this->output, $this->zone->count());
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');

        $progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
        $progressBar->setProgressCharacter("<fg=green>➤</>");

        $progressBar->start();

        foreach ($this->zone->getResourceRecords() as $record) {
            $recordString = "{$record->getName()} {$record->getType()} {$record->getClass()} {$record->getRdata()->toText()}";
            $progressBar->setMessage("Processing record <info>{$recordString}</info>");

            $numChecks = 0;
            $recordErrors = [];
            $config = $this->getConfig($record->getClass(), $record->getType(), $record->getName());

            if (!empty($config)) {
                foreach ($config as $checkerConfig) {
                    $checker = $this->getChecker($checkerConfig);

                    if ($checker != null) {
                        $checker->setZone($this->zone);
                        $checker->setResourceRecord($record);
                        $checker->check();

                        $recordErrors = array_merge($recordErrors, $checker->getErrors());
                        $numChecks++;
                    }
                }
            }

            if (count($recordErrors) > 0) {
                $progressBar->clear();
                $this->output->writeln("Errors for <comment>{$record->getName()} {$record->getType()} {$record->getClass()} {$record->getRdata()->toText()}</comment> in zone <comment>{$this->zoneName}</comment>:");

                foreach ($recordErrors as $recordError) {
                    $this->output->writeln(" - {$recordError}");
                }
                $progressBar->display();
            }

            if ($numChecks == 0 && !in_array($record->getType(), $ignoreTypes)) {
                $progressBar->clear();
                $this->output->writeln("Warning: <comment>{$record->getName()} {$record->getType()} {$record->getClass()} {$record->getRdata()->toText()}</comment> in zone <comment>{$this->zoneName}</comment> has no checks");
                $progressBar->display();
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $progressBar->clear();
    }

    private function parseZone () {
        $zoneContent = file_get_contents($this->zoneFile);
        $this->zone = Parser::parse($this->zoneName, $zoneContent);
    }

    private function parseConfig () {
        $configStr = file_get_contents($this->configFile);
        $this->config = Yaml::parse($configStr);
    }

    private function getConfig ($class, $type, $name): array {
        if (!array_key_exists($class, $this->config)) {
            return [];
        }

        if (!array_key_exists($type, $this->config[$class])) {
            return [];
        }

        if (!array_key_exists($name, $this->config[$class][$type])) {
            return [];
        }

        return $this->config[$class][$type][$name];
    }

    private function getChecker ($checkerConfig): ?AbstractChecker {
        if (array_key_exists("defer_until", $checkerConfig)) {

            if (strtotime($checkerConfig["defer_until"]) > time()) {
                return new CheckNull();
            }
        }

        if (array_key_exists("template", $checkerConfig)) {
            $template = GlobalConfig::getInstance()->getTemplate($checkerConfig["template"]);

            if ($template == null) {
                throw new Exception("Unable to find template with name ".$checkerConfig["template"]);
            }

            $checkerConfig = array_merge($template, $checkerConfig);
        }

        if (!array_key_exists("type", $checkerConfig)) {
            throw new Exception("The key 'type' must be defined!");
        }

        foreach (ZoneChecker::$checkers as $checker) {
            /**
             * @var $checker AbstractChecker
             */
            if (!class_exists($checker)) {
                throw new Exception("Class $checker is defined in ZoneChecker but could not be found");
            }

            if ($checker::canHandle($checkerConfig["type"])) {
                /**
                 * @var $checkerInstance AbstractChecker
                 */
                $checkerInstance = new $checker();
                $checkerInstance->setConfig($checkerConfig);
                return $checkerInstance;
            }
        }

        throw new Exception("Could not find a checker for type {$checkerConfig["type"]}");

        return null;

    }
}