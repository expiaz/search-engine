<?php

require_once __DIR__ . '/src/constants.php';

$q = new \SearchEngine\Core\Search\Query('prop');
$index = unserialize(file_get_contents(CACHED_INDEX));
$q->complete($index);
$results = SearchEngine\Core\Search\VectorialModel::cosim($index, $q);