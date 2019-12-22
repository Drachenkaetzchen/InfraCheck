<?php


namespace Drachenkatze\ZoneChecker\Commands;


use Badcow\DNS\Parser\Parser;
use Drachenkatze\ZoneChecker\GlobalConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenerateConfig extends Command
{
    /**
     * @var string
     */
    private $zoneFile;

    /**
     * @var string
     */
    private $zoneName;

    /**
     * @var \Badcow\DNS\Zone
     */
    private $zone;

    /**
     * @var string
     */
    private $configFile;

    /**
     * @var array
     */
    private $config;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'generate-config';

    protected function configure()
    {
        $this->addArgument("zonefile", InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ignoreTypes = [ "SOA", "NS", "TXT"];

        $this->zoneFile = GlobalConfig::getInstance()->getDirectory($input->getArgument("zonefile"));
        $fileName = basename($this->zoneFile);
        $this->zoneName = str_replace(".zone", ".", $fileName);
        $this->configFile = str_replace(".zone", ".config.yml", $this->zoneFile);

        $this->parseZone();
        $this->parseConfig();

        $nullConfig = [["type" => "check_null", "alert" => "auto-generated stub"]];
        foreach ($this->zone->getResourceRecords() as $record) {
            $config = $this->getConfig($record->getClass(), $record->getType(), $record->getName());

            if (!in_array($record->getType(), $ignoreTypes) && count($config) == 0) {
                $this->setConfig($record->getClass(), $record->getType(), $record->getName(), $nullConfig);
            }

        }

        print_r($this->config);
        $this->writeConfig();
        return 0;
    }

    private function parseZone () {
        $zoneContent = file_get_contents($this->zoneFile);
        $this->zone = Parser::parse($this->zoneName, $zoneContent);
    }

    private function parseConfig () {
        if (file_exists($this->configFile)) {
            $configStr = file_get_contents($this->configFile);
            $this->config = Yaml::parse($configStr);
        } else {
            $this->config = [];
        }
    }

    private function writeConfig () {
        $yaml = Yaml::dump($this->config, 4, 4);
        file_put_contents($this->configFile, $yaml);

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

    private function setConfig ($class, $type, $name, $config) {
        if (!array_key_exists($class, $this->config)) {
            $this->config[$class] = [];
        }

        if (!array_key_exists($type, $this->config[$class])) {
            $this->config[$class][$type] = [];
        }

        $this->config[$class][$type][$name] = $config;
    }

}