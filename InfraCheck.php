#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->setName("InfraCheck");

$executeCommand = new \Drachenkatze\ZoneChecker\Commands\ExecuteCommand();
$application->addCommands([$executeCommand, new \Drachenkatze\ZoneChecker\Commands\GenerateConfig()]);
//$application->setDefaultCommand($executeCommand->getName());

$application->run();
