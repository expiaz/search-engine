<?php

namespace SearchEngine\Core\Index;

class InvertedIndexEntry
{
    /**
     * @var string
     */
    public $canonical;

    /**
     * @var int
     */
    public $occurences;

    /**
     * @var float
     */
    public $weight;

    /**
     * @var string[]
     */
    public $words;
    public $tfidf;

    /**
     * @param string $canonical
     * @param float $weight
     * @param float $tfidf
     * @param int $occurences
     * @param array $words
     */
    public function __construct(string $canonical, float $weight = 0, float $tfidf, int $occurences = 1, array $words = [])
    {
        $this->tfidf = $tfidf;
        $this->words = $words;
        $this->occurences = $occurences;
        $this->weight = $weight;
        $this->canonical = $canonical;
    }

}