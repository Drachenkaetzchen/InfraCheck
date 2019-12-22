<?php


namespace Drachenkatze\ZoneChecker;


use Symfony\Component\Yaml\Yaml;

class GlobalConfig
{
    private $config;
    private $configFile;

    private static $instance;

    private $rootDirectory;

    private $configDirectory = "config/";
    private $zoneDirectory = "config/zonefiles";

    public function __construct()
    {
        $this->rootDirectory = realpath(__DIR__ . "/../");
        $this->configFile = $this->getDirectory($this->configDirectory . "config.yml");
        $this->parseConfig();
        self::$instance = $this;
    }

    public function getZoneDirectory () {
        return $this->getDirectory($this->zoneDirectory);
    }

    public function getDirectory ($dir) {
        return $this->rootDirectory . "/" . $dir;
    }

    private function parseConfig () {
        $configStr = file_get_contents($this->configFile);
        $this->config = Yaml::parse($configStr);
    }

    public function getTemplate ($name) {
        if (!array_key_exists("templates", $this->config)) {
            return null;
        }

        foreach ($this->config["templates"] as $template) {
            if (array_key_exists("name", $template) && $template["name"] == $name) {
                return $template;
            }
        }

        return null;
    }

    public static function getInstance () {
        if (self::$instance == null) {
            self::$instance = new GlobalConfig();
        }
        return self::$instance;
    }
}