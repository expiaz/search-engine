<?php

namespace SearchEngine\Core\Ranking;

use SearchEngine\Core\Document\Document;

class PageRank
{

    private static $dampingFactor = 0.85;
    private static $e = 0.000001;

    private $documents;
    private $copy;
    private $N;

    /**
     * PageRank constructor.
     * @param Document[] $documents
     */
    public function __construct(array $documents)
    {
        $this->documents = $documents;
        $this->copy = [];
        $this->N = count($documents);

        // at state t=0, PR is assumed to be 1/N
        foreach ($documents as $document) {
            $this->copy[] = $document;
            $document->setPageRank(1 / $this->N);
        }
    }

    /**
     * @return Document[]
     */
    public function run()
    {
        if ($this->N === 0) {
            return $this->documents;
        }

        $bpr = (1 - self::$dampingFactor) / $this->N;
        while (count($this->copy)) {
            foreach ($this->copy as $i => $document) {
                $sum = 0;
                foreach ($document->referencedBy as $ref) {
                    $sum += $ref->getPageRank() / (($L = count($ref->referenceTo)) ? $L : $this->N);
                }
                $pr = $bpr + self::$dampingFactor * $sum;
                // convergence is assumed to be atteigned
                if ($pr - $document->getPageRank() < self::$e) {
                    array_splice($this->copy, $i, 1);
                }
                $document->setPageRank($pr);
            }
        }

        usort($this->documents, function(Document $a, Document $b) {
            return $a->getPageRank() < $b->getPageRank();
        });

        return $this->documents;
    }

}