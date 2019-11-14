<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \AAD\TelegramBots\Helper\Config;
use \AAD\TelegramBots\App;

Config::init(__DIR__ . "/../config/config.ini");

if (Config::get()->debug) {
    ini_set('display_startup_errors', 1);
    ini_set('display_errors', 1);
    error_reporting(-1);
}

$app = new App();
$app->run();
