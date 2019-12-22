<?php
namespace Drachenkatze\ZoneChecker\Checkers;


use Badcow\DNS\ResourceRecord;
use Badcow\DNS\Zone;

abstract class AbstractChecker
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var ResourceRecord
     */
    protected $resourceRecord;

    /**
     * @var Zone
     */
    protected $zone;

    protected $errors = [];

    abstract public function check();
    abstract public static function canHandle(string $checkerType);

    public function setZone (Zone $zone) {
        $this->zone = $zone;
    }

    public function setResourceRecord (ResourceRecord $record) {
        $this->resourceRecord = $record;
    }
    public function setConfig (array $config) {
        $this->config = $config;
    }

    public function getFQDN () {
        $zoneNameWithoutDot = substr($this->zone->getName(),0,-1);
        if ($this->resourceRecord->getName() !== "@") {
            $finalName = $this->resourceRecord->getName() . ".".$zoneNameWithoutDot;
        } else {
            $finalName = $zoneNameWithoutDot;
        }

        return $finalName;
    }

    public function getCNameFQDN () {
        $cnameTarget = $this->resourceRecord->getRdata()->toText();

        if (substr($cnameTarget, -1) == ".") {
            return $cnameTarget;
        }

        $zoneNameWithoutDot = substr($this->zone->getName(),0,-1);
        $finalName = $cnameTarget . ".".$zoneNameWithoutDot;

        return $finalName;
    }

    protected function logError ($message) {
        $this->errors[] = $message;
    }

    public function getErrors () {
        return $this->errors;
    }
}