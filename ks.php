<?php

require_once __DIR__ . '/vendor/autoload.php';

$time = microtime(true);

$cache = file_exists(__DIR__ . '/index.data') && file_exists(__DIR__ . '/documents.data');

if ($cache) {
    $index = unserialize(file_get_contents(__DIR__ . '/index.data'));
    //$documents = unserialize(file_get_contents(__DIR__ . '/documents.data'));
} else {
    $crawler = new \SearchEngine\Core\Crawler(5, 5);
    $index = $crawler->crawl('https://fr.slideshare.net/dalal404/document-similarity-with-vector-space-model');
    $documents = $crawler->getDocuments();

    echo "PR\n";
    $pageRanker = new \SearchEngine\Core\Ranking\PageRank($documents);
    $pageRanker->run();
    // sort by page rank
    usort($documents, function(\SearchEngine\Core\Document\Document $a, \SearchEngine\Core\Document\Document $b) {
        return $a->getPageRank() < $b->getPageRank();
    });

    print_r(implode("\n", $documents));

    file_put_contents(__DIR__  . '/index.data', serialize($index));
    file_put_contents(__DIR__  . '/documents.data', serialize($documents));
}

$search = 'Explore similarity';
$query = new \SearchEngine\Core\Document\Query($search);
$res = \SearchEngine\Core\VectorialModel::cosim($index, $query);

$l = count($res);
$f = microtime(true) - $time;
$t = number_format($f, 2);
echo "{$l} results found in {$t} seconds\n\n";

print_r(implode("\n", $res));

die();

$a = new \SearchEngine\Core\Document\Document(new \SearchEngine\Core\Document\Url("http://domain.a"));
$b = new \SearchEngine\Core\Document\Document(new \SearchEngine\Core\Document\Url("http://domain.b"));
$c = new \SearchEngine\Core\Document\Document(new \SearchEngine\Core\Document\Url("http://domain.c"));

$a->reference($b);
$a->reference($c);
$b->reference($a);
$b->reference($c);
$c->reference($b);
$c->reference($a);

$pr = new \SearchEngine\Core\Ranking\PageRank([$a, $b, $c]);

print_r(implode("\n", $pr->run()));