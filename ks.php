<?php

require_once __DIR__ . '/vendor/autoload.php';

$l = new SearchEngine\Core\Crawler(10);

$i = $l->crawl('https://twitter.com/LeMondefr');
print implode(", ", $i->dumpWords());

file_put_contents(__DIR__ . '/data.index', serialize($i));