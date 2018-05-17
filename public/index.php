<?php

require_once __DIR__ . '/../src/constants.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => ROOT . '/src/views',
));

$app['debug'] = true;

$app->get('/', \SearchEngine\Controller\IndexController::class . '::indexAction')
    ->bind('index');

$app->get('/search', SearchEngine\Controller\IndexController::class . '::searchAction')
    ->bind('search');

$app->get('/crawl', SearchEngine\Controller\IndexController::class . '::crawlAction')
    ->bind('crawl');

$app->get('/stats', SearchEngine\Controller\IndexController::class . '::statsAction')
    ->bind('stats');

$app->get('/details', \SearchEngine\Controller\IndexController::class . '::detailsAction')
    ->bind('details');

$app->get('/dico', \SearchEngine\Controller\IndexController::class . '::dicoAction')
    ->bind('dico');

$app->run();