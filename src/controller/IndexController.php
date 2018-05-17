<?php

namespace SearchEngine\Controller;

use SearchEngine\Core\Crawl\Crawler;
use SearchEngine\Core\Crawl\Parser;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Index\Thesaurus;
use SearchEngine\Core\Misc\Logger;
use SearchEngine\Core\Misc\Map;
use SearchEngine\Core\Search\Query;
use SearchEngine\Core\Search\VectorialModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
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
        $index = unserialize(file_get_contents(CACHED_INDEX));

        if (file_exists(CACHED_DICO)) {
            $thesaurus = unserialize(file_get_contents(CACHED_DICO));
        } else {
            $thesaurus = new Thesaurus();
        }

        $q = new Query($query, new Parser());
        $q->complete($index, $thesaurus);
        $results = VectorialModel::cosim($index, $q);

        // display results
        return $twig->render('results.twig', [
            'hits' => $results,
            'query' => $query,
            'time' => number_format(microtime(true) - $time, 2)
        ]);
    }

    public function crawlAction(Request $request, Application $app)
    {
        set_time_limit(0);

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

        $maxDocs = (int) $request->query->get('max_docs');
        $maxTags = (int) $request->query->get('max_tags');

        $crawler = new Crawler($maxDocs, $maxTags);
        /**
         * @var $index InvertedIndex
         * @var $documents Map
         * @var $thesaurus Thesaurus
         */
        list($index, $documents, $thesaurus) = $crawler->crawl($url);

        file_put_contents(CACHED_INDEX, serialize($index));
        file_put_contents(CACHED_DOCS, serialize($documents));
        file_put_contents(CACHED_DICO, serialize($thesaurus));

        Logger::logln("END CRAWLING");
        Logger::logln("{$index->length()} documents, {$index->size()} mots");
        return '';

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

    public function detailsAction(Request $request, Application $app)
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
            return $app->redirect($router->generate('stats'));
        }

        $uri = $request->query->get('uri', '');
        if (!strlen($uri)) {
            return $app->redirect($router->generate('stats'));
        }

        /**
         * @var $documents array
         */
        $documents = unserialize(file_get_contents(CACHED_DOCS));

        if (! array_key_exists($uri, $documents)) {
            return $app->redirect($router->generate('stats'));
        }

        return $twig->render('details.twig', [
            'document' => $documents[$uri]
        ]);
    }

    public function dicoAction(Request $request, Application $app)
    {
        /**
         * @var UrlGenerator $router
         */
        $router = $app['url_generator'];
        /**
         * @var Twig_Environment $twig
         */
        $twig = $app['twig'];

        if (! file_exists(CACHED_DICO)) {
            // redirect crawl
            return $app->redirect($router->generate('search'));
        }

        $thesaurus = unserialize(file_get_contents(CACHED_DICO));
        return $twig->render('dico.twig', [
            'dico' => $thesaurus->getAll()
        ]);
    }

}