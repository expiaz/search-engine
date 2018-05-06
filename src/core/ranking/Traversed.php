<?php

namespace SearchEngine\Core\Ranking;

use SearchEngine\Core\Document\Document;

class Traversed
{

    private $sum;
    private $depth;
    private $document;

    public function __construct(Document $document, float $sum = 0, int $depth = 0)
    {
        $this->depth = $depth;
        $this->sum = $sum;
        $this->document = $document;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param int $depth
     */
    public function addDepth(int $depth = 1)
    {
        $this->depth += $depth;
    }

    /**
     * @return float
     */
    public function getSum(): float
    {
        return $this->sum;
    }

    /**
     * @param float $sum
     */
    public function addSum(float $sum)
    {
        $this->sum += $sum;
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

}