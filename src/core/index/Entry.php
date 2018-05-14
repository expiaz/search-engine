<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Word;

class Entry
{
    private static $diminution = 0.01;

    /**
     * @var int
     */
    public $occurences;
    /**
     * @var float
     */
    public $weight;

    public function __construct(?float $weight = 0, ?int $occurences = 1)
    {
        $this->occurences = $occurences;
        $this->weight = $weight;
    }

    public function merge(Entry $entry)
    {
        $this->occurences += $entry->occurences;
        $this->weight += $entry->weight;
    }

    public function occurence(int $nb  = 1, ?float $weight = Word::BODY)
    {
        $this->weight += $nb * $weight - self::$diminution * $this->occurences;
        $this->occurences += $nb;
    }

}