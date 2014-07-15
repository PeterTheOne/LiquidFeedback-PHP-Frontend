<?php

require_once '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\Slim(array(
    'debug' => false
));

require_once '../app/app.php';

$app->run();
