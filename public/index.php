<?php

define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';

define('CACHED_INDEX', ROOT . '/data/index.data');
define('CACHED_DOCS', ROOT . '/data/documents.data');

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

$app->run();