<?php

require_once '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\Slim(array(
    'debug' => false
));
$app->add(new \Slim\Middleware\SessionCookie());

require_once '../app/app.php';

$app->run();
