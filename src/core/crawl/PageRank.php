<?php

namespace SearchEngine\Core\Crawl;

use SearchEngine\Core\Document\Document;

class PageRank
{

    private static $dampingFactor = 0.85;
    private static $e = 0.000001;

    /**
     * @param array $documents
     * @param int|null $size
     * @return Document[]
     */
    public static function pageRank(array $documents, int $size)
    {
        $N = $size;

        /**
         * @var $copy Document[]
         */
        $copy = [];
        // at state t=0, PR is assumed to be 1/N
        foreach ($documents as $document) {
            $copy[] = $document;
            $document->setPageRank(1 / $N);
        }

        if ($N === 0) {
            return $documents;
        }

        $bpr = (1 - self::$dampingFactor) / $N;
        while (count($copy)) {
            foreach ($copy as $i => $document) {
                $sum = 0;
                foreach ($document->referencedBy as $ref) {
                    $sum += $ref->getPageRank() / (($L = count($ref->referenceTo)) ? $L : $N);
                }
                $pr = $bpr + self::$dampingFactor * $sum;
                // convergence is assumed to be atteigned
                if ($pr - $document->getPageRank() < self::$e) {
                    array_splice($copy, $i, 1);
                }
                $document->setPageRank($pr);
            }
        }

        uasort($documents, function(Document $a, Document $b) {
            return $a->getPageRank() < $b->getPageRank();
        });

        return $documents;
    }

}