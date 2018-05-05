<?php

define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => ROOT . '/src/views',
));

$app['debug'] = true;

$app->get('/', function() {
    return 'hi';
});

$app->get('/{name}', SearchEngine\Controller\IndexController::class . '::indexAction')
    ->bind('index');

$app->run();