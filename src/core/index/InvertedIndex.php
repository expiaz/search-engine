<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Word;

class InvertedIndex
{
    private $words;

    public function __construct()
    {
        $this->words = [];
    }

    public function addEntry(Word $word, Document $document)
    {
        if (!array_key_exists($word->getCanonical(), $this->words)) {
            $this->words[$word->getCanonical()] = [];
        }
        $entry = new Entry($word, $document);
        $this->words[$word->getCanonical()][] = $entry;
        $document->addWord($word);
    }

    public function getEntry(Word $word): ?Entry
    {
        return array_key_exists($word->getCanonical(), $this->words)
            ? $this->words[$word->getCanonical()]
            : null;
    }

    public function dumpWords()
    {
        return array_keys($this->words);
    }
}