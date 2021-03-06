<?php

$lqfb = new \LiquidFeedback\LiquidFeedback($config->server->host,
    $config->server->port, $config->server->dbname, $config->server->user,
    $config->server->password);
$lqfb->setCurrentAccessLevel($config->defaultAccessLevel);

$loader = new Twig_Loader_Filesystem('../app/templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false//'../cache'
));
$twig->addGlobal('baseUrl', $config->server->baseUrl);
$twig->addGlobal('title', 'LiquidFeedback PHP Frontend');

function loggedIn($config) {
    return isset($_SESSION['member']) && isset($_SESSION['HTTP_USER_AGENT']) &&
        $_SESSION['HTTP_USER_AGENT'] === sha1($config->session->salt . $_SERVER['HTTP_USER_AGENT']);
}

$checkLogin = function() {
    return function() {
        global $config;
        $app = \Slim\Slim::getInstance();
        if (!loggedIn($config)) {
            $_SESSION['urlRedirect'] = $app->request()->getPathInfo();
            $app->redirect($config->server->baseUrl . '/login');
        }
    };
};

//setAccessLevelFromSession
$app->hook('slim.before.router', function() use ($config, $lqfb, $twig) {
    if (loggedIn($config)) {
        $lqfb->setCurrentAccessLevel(
            \LiquidFeedback\AccessLevel::MEMBER,
            $_SESSION['member']->id
        );
        $twig->addGlobal('member', $_SESSION['member']);
    }
    $units = $lqfb->getUnit();
    $twig->addGlobal('units', $units);
});

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

$app->get('/login', function() use($app, $config, $twig) {
    if (loggedIn($config)) {
        $app->redirect($config->server->baseUrl);
    }
    echo $twig->render('login.html', array(
        'title' => 'LiquidFeedback PHP Frontend',
    ));
});

$app->post('/login', function() use($app, $config, $lqfb, $twig) {
    $member = $lqfb->login($app->request->params('login'), $app->request->params('password'));
    if (isset($member)) {
        $_SESSION['member'] = $member;
        $_SESSION['HTTP_USER_AGENT'] = sha1($config->session->salt . $_SERVER['HTTP_USER_AGENT']);
        if (isset($_SESSION['urlRedirect'])) {
            $app->redirect($config->server->baseUrl . $_SESSION['urlRedirect']);
        }
        $app->redirect($config->server->baseUrl);
    }
    $app->redirect($config->server->baseUrl . '/login');
});

$app->get('/logout', function() use($app, $config, $lqfb) {
    unset($_SESSION['member']);
    unset($_SESSION['HTTP_USER_AGENT']);
    $lqfb->setCurrentAccessLevel($config->defaultAccessLevel);
    $app->redirect($config->server->baseUrl . '/login');
});

$app->get('/member', $checkLogin(), function() use($app, $config, $lqfb, $twig) {
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

$app->get('/unit/:id', function($id) use($app, $config, $lqfb, $twig) {
    $unit = $lqfb->getUnit($id)[0];
    $areas = $lqfb->getArea(null, null, null, $id);

    echo $twig->render('unit.html', array(
        'title' => 'LiquidFeedback PHP Frontend',
        'unit' => $unit,
        'areas' => $areas
    ));
});

$app->get('/area/:id', function($id) use($app, $config, $lqfb, $twig) {
    $area = $lqfb->getArea($id)[0];
    // todo: fix parameter madness!
    $issues = $lqfb->getIssue(null, null, null, null, null, null, null, null,
        null, null, null, null, null, null, null, null, $id);

    echo $twig->render('area.html', array(
        'title' => 'LiquidFeedback PHP Frontend',
        'area' => $area,
        'issues' => $issues
    ));
});




