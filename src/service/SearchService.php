<?php

namespace SearchEngine\Service;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Query;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\VectorialModel;

class SearchService
{

    public function __construct()
    {
    }

    /**
     * @param string $query
     * @return Document[]
     */
    public function search(string $query): array
    {
        if (! file_exists(CACHED_INDEX)) {
            return [];
        }

        /**
         * @var InvertedIndex $index
         */
        $index = file_get_contents(CACHED_INDEX);
        $results = VectorialModel::cosim($index, new Query($query));

        return $results;

    }

}