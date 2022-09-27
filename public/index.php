<?php

use Timo\Core\Engine;

define('APP_NAME', 'api');
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require ROOT_PATH . 'vendor/autoload.php';

$engine = new Engine();
$engine->start();
