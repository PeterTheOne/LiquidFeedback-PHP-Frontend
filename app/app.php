<?php

$lqfb = new \LiquidFeedback\LiquidFeedback($config->server->host,
    $config->server->port, $config->server->dbname, $config->server->user,
    $config->server->password);
// !!! don't change the access level if you don't know what you are doing. !!!
$lqfb->setCurrentAccessLevel(\LiquidFeedback\LiquidFeedback::ACCESS_LEVEL_ANONYMOUS);

$loader = new Twig_Loader_Filesystem('../app/templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false//'../cache'
));
$twig->addGlobal('baseUrl', $config->server->baseUrl);
$twig->addGlobal('title', 'LiquidFeedback PHP Frontend');

// todo: only display errors if config says so.
$app->error(function(\Exception $exception) use ($app, $twig) {
    echo $twig->render('error.html', array('exception' => $exception));
});

$app->notFound(function () {
    global $twig;
    echo $twig->render('404.html');
});

$app->get('/', function() use($app, $twig) {
    echo $twig->render('index.html', array('title' => 'LiquidFeedback PHP Frontend'));
});

$app->get('/login', function() use($app, $twig) {
    echo $twig->render('login.html', array('title' => 'LiquidFeedback PHP Frontend'));
});

$app->get('/members', function() use($app, $lqfb, $twig) {
    $members = $lqfb->getMember(
        null,
        $app->request->params('active'),
        null,
        null,
        $app->request->params('orderByCreated')
    );

    echo $twig->render('members.html', array(
        'title' => 'LiquidFeedback PHP Frontend',
        'members' => $members
    ));
});


