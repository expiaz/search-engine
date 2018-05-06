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

    public function pr(Document $doc)
    {
        $sum = 0;
        if ($this->depth > 0) {
            --$this->depth;
            foreach ($doc->referencedBy as $ref) {
                $sum += $this->pr($doc) / (($L = count($ref->referenceTo)) > 0 ? $L : $this->N);
            }
        } else {
            foreach ($doc->referencedBy as $ref) {
                $sum += $ref->getPageRank();
            }
        }
        return (1 - self::$dampingFactor) / $this->N + self::$dampingFactor * $sum;
    }

    /**
     * @return Document[]
     */
    public function run()
    {
        if ($this->N === 0) {
            return $this->documents;
        }

        $t = 0;
        $bpr = (1 - self::$dampingFactor) / $this->N;
        while (count($this->copy)) {
            ++$t;
            foreach ($this->copy as $i => $document) {
                $sum = 0;
                foreach ($document->referencedBy as $ref) {
                    $k = count($ref->referenceTo);
                    $s = $k > 0 ? $k : $this->N;
                    $sum += $ref->getPageRank() / $s;
                }
                $a = self::$dampingFactor * $sum;
                $b = $bpr + $a;
                // convergence is assumed to be atteingned
                if ($b - $document->getPageRank() < self::$e) {
                    array_splice($this->copy, $i, 1);
                }
                $document->setPageRank($b);
            }
        }

        echo $t . "\n";
        return $this->documents;
    }

}