<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '__assets' . DIRECTORY_SEPARATOR . 'Storage.php';

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .
if (@!include __DIR__ . "/../vendor/autoload.php") {
    echo "Install Nette Tester using `composer update`";
    exit(1);
}

// configure environment
Tester\Environment::setup();
date_default_timezone_set("Europe/Prague");

\obo\obo::$developerMode = true;

\obo\obo::setCache(new obo\DataStorage\Tests\Assets\Cache(__DIR__ . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . "obo"));
\obo\obo::setTempDir(__DIR__ . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . "obo");
\obo\obo::addModelsDirs([
   __DIR__ . DIRECTORY_SEPARATOR . "__assets" . DIRECTORY_SEPARATOR . "Entities",
]);

\obo\obo::setDefaultDataStorage(\obo\DataStorage\Tests\Assets\Storage::getMySqlDataStorage());

\obo\obo::run();
