<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Misc\Map;

class ThesaurusEntry
{

    private static $isSynonym = 1/10;

    private $word;
    private $siblings;
    private $sum;

    public function __construct(string $word)
    {
        $this->word = $word;
        $this->sum = 0;
        $this->siblings = new Map();
    }

    public function add(string $sibling)
    {
        if (! $this->siblings->hasKey($sibling)) {
            $this->siblings->addKey($sibling, 0);
        }
        $this->sum += 1;
        $this->siblings->addKey($sibling, $this->siblings->getKey($sibling) + 1);
    }

    public function isSynonym(string $synonym)
    {
        if (! $this->siblings->hasKey($synonym)) {
            return false;
        }

        $occ = $this->siblings->getKey($synonym);
        $total = $this->sum;
        // at least 1/10 of time is grouped with this word
        return $occ / $total > self::$isSynonym;
    }

    public function getSynonyms(): array
    {
        return array_filter($this->siblings->toArray(), function (string $sibling) {
            return $this->isSynonym($sibling);
        });
    }

    /**
     * @return int
     */
    public function getSum(): int
    {
        return $this->sum;
    }

    public function getAll(): array
    {
        return $this->siblings->toArray();
    }

}