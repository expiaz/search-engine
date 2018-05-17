<?php

define('ROOT', dirname(__DIR__));
require_once ROOT . '/vendor/autoload.php';

define('CACHED_INDEX', ROOT . '/data/index.data');
define('CACHED_DOCS', ROOT . '/data/documents.data');
define('CACHED_DICO', ROOT . '/data/thesaurus.data');

define('IS_CLI', php_sapi_name() === "cli");