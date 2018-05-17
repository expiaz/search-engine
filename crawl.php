<?php

require_once __DIR__ . '/src/constants.php';


$tags = 1;
$docs = 1;
$url = 'https://github.com/';
switch ($argc) {
    case 4:
        $tags = (int) $argv[3];
    case 3:
        $docs = (int) $argv[2];
    case 2:
        $url = trim($argv[1]);
        break;

    /*default:
        echo "php {$argv[0]} <url> <max-docs> <max-tags>\n";
        exit(0);*/
}

$crawler = new \SearchEngine\Core\Crawl\Crawler($docs, $tags);
$index = $crawler->crawl($url);