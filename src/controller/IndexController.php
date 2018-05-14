<?php

namespace SearchEngine\Controller;

use SearchEngine\Core\Crawler;
use SearchEngine\Core\Document\Query;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Ranking\PageRank;
use SearchEngine\Core\VectorialModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Twig_Environment;

class IndexController
{

    public function indexAction(Request $request, Application $app)
    {
        /**
         * @var UrlGenerator $router
         */
        $router = $app['url_generator'];

        if (! file_exists(CACHED_INDEX)) {
            // redirect crawl
            return $app->redirect($router->generate('crawl'));
        }

        return $app->redirect($router->generate('search'));
    }

    public function searchAction(Request $request, Application $app)
    {
        /**
         * @var UrlGenerator $router
         */
        $router = $app['url_generator'];
        /**
         * @var Twig_Environment $twig
         */
        $twig = $app['twig'];

        if (! file_exists(CACHED_INDEX)) {
            // redirect crawl
            return $app->redirect($router->generate('crawl'));
        }

        $query = $request->query->get('s', '');

        if (! strlen($query)) {
            // search bar
            return $twig->render('search.twig');
        }

        $time = microtime(true);

        /**
         * @var InvertedIndex $index
         */
        $index = unserialize(file_get_contents(CACHED_INDEX));
        $results = VectorialModel::cosim($index, new Query($query));

        // display results
        return $twig->render('results.twig', [
            'documents' => $results,
            'query' => $query,
            'time' => number_format(microtime(true) - $time, 2)
        ]);
    }

    public function crawlAction(Request $request, Application $app)
    {
        /**
         * @var UrlGenerator $router
         */
        $router = $app['url_generator'];
        /**
         * @var Twig_Environment $twig
         */
        $twig = $app['twig'];

        $url = $request->query->get('url', '');
        if (! strlen($url)) {
            return $twig->render('crawl.twig');
        }

        $crawler = new Crawler(5, 5);

        $index = $crawler->crawl($url);
        $documents = $crawler->getDocuments();

        $pageRanker = new PageRank($documents);
        $pageRanker->run();

        file_put_contents(CACHED_INDEX, serialize($index));
        file_put_contents(CACHED_DOCS, serialize($documents));

        return $app->redirect($router->generate('stats'));
    }

    public function statsAction(Request $request, Application $app)
    {
        /**
         * @var UrlGenerator $router
         */
        $router = $app['url_generator'];
        /**
         * @var Twig_Environment $twig
         */
        $twig = $app['twig'];

        if (! file_exists(CACHED_DOCS)) {
            // redirect crawl
            return $app->redirect($router->generate('crawl'));
        }

        $documents = unserialize(file_get_contents(CACHED_DOCS));
        return $twig->render('stats.twig', [
            'documents' => $documents
        ]);
    }

}