<?php

namespace Drachenkatze\ZoneChecker\Checkers;

class CheckNull extends AbstractChecker {

    private $alert = null;

    public function check()
    {
        if ($this->alert != null) {
            $this->logError($this->alert);
        }
    }

    public function setConfig(array $config)
    {
        parent::setConfig($config); // TODO: Change the autogenerated stub

        if(array_key_exists("alert",$config)) {
            $this->alert = $config["alert"];
        }
    }

    public static function canHandle(string $checkerType)
    {
        if ($checkerType == "check_null") {
            return true;
        }

        return false;
    }

}