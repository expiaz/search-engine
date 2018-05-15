<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Document;
use SplObjectStorage;

class InvertedIndex
{
    private $words;
    private $size;
    private $length;

    public function __construct()
    {
        $this->words = [];
        $this->size = 0;
        $this->length = 0;
    }

    public function add(Document $document, string $canonical, Entry $entry)
    {
        if (! $this->has($canonical)) {
            ++$this->size;
            $this->words[$canonical] = new SplObjectStorage();
        }
        $this->words[$canonical]->attach($document, $entry);
    }

    public function has(string $canonical): bool
    {
        return array_key_exists($canonical, $this->words);
    }

    public function get(string $canonical): SplObjectStorage
    {
        return $this->words[$canonical];
    }

    /**
     * @return SplObjectStorage[]
     */
    public function all(): array
    {
        return $this->words;
    }

    public function size(): int
    {
        return $this->size;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length)
    {
        $this->length = $length;
    }

    public function length(): int
    {
        return $this->length;
    }
}