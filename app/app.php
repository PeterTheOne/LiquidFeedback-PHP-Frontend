<?php

$lqfb = new \LiquidFeedback\LiquidFeedback($config->server->host,
    $config->server->port, $config->server->dbname, $config->server->user,
    $config->server->password);
// !!! don't change the access level if you don't know what you are doing. !!!
$lqfb->setCurrentAccessLevel(\LiquidFeedback\AccessLevel::ANONYMOUS);

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
    $lqfb->setCurrentAccessLevel(\LiquidFeedback\AccessLevel::ANONYMOUS);
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


