<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

class InvertedIndex
{
    private $words;
    private $size;
    private $length;

    public function __construct(Map $words, int $size, int $length)
    {
        $this->words = $words;
        $this->size = $size;
        $this->length = $length;
    }

    public function has(string $canonical)
    {
        return $this->words->hasKey($canonical);
    }

    public function get(string $canonical, $default = null): SplObjectStorage
    {
        return $this->words->getKey($canonical, $default);
    }

    /**
     * @return SplObjectStorage[]
     */
    public function all(): array
    {
        return $this->words->toArray();
    }

    public function size(): int
    {
        return $this->size;
    }

    public function length(): int
    {
        return $this->length;
    }
}