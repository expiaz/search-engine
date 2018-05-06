<?php

require_once __DIR__ . '/vendor/autoload.php';

$a = new \SearchEngine\Core\Crawler(5, 5, 2);
$index = $a->crawl('https://chamilo.iut2.univ-grenoble-alpes.fr/');
echo "PR\n";
$b = new \SearchEngine\Core\Ranking\PageRank($a->getDocuments());
$pr = $b->run();
usort($pr, function(\SearchEngine\Core\Document\Document $a, \SearchEngine\Core\Document\Document $b) {
    return $a->getPageRank() < $b->getPageRank();
});

print_r(implode("\n", $pr));

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

$pr = new \SearchEngine\Core\Ranking\PageRank([$a, $b, $c], 2);

print_r(implode("\n", $pr->run()));