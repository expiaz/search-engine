<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Word;
use SearchEngine\Core\Misc\Hashable;
use SearchEngine\Core\Misc\Map;

class Entry implements Hashable
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

    private $words;

    /**
     * Entry constructor.
     * @param array $words
     * @param float|null $weight
     * @param int|null $occurences
     */
    public function __construct(array $words = [], ?float $weight = 0, ?int $occurences = 1)
    {
        $this->words = new Map();
        $this->addAll($words);
        $this->occurences = $occurences;
        $this->weight = $weight;
    }

    public function addAll(array $words)
    {
        foreach ($words as $word) {
            $this->words->add($word);
        }
    }

    public function merge(Entry $entry)
    {
        $this->occurences += $entry->occurences;
        $this->weight += $entry->weight;
        $this->addAll($entry->getWords());
    }

    public function occurence(Word $word, ?float $weight = Word::BODY, ?int $occurences = 1)
    {
        $this->weight += $weight - (self::$diminution * $this->occurences ?: 0);
        $this->occurences += $occurences;
        $this->words->add($word);
    }

    /**
     * @return Word[]
     */
    public function getWords(): array
    {
        return $this->words->toArray();
    }

    public function hash(): string
    {
        // TODO: Implement hash() method.
    }
}