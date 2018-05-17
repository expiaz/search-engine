<?php

namespace SearchEngine\Core\Search;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Index\InvertedIndexEntry;
use SearchEngine\Core\Misc\Hashable;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

/**
 * Represents a matched document for a query
 * @package SearchEngine\Core\Search
 */
class Hit implements Hashable
{

    /**
     * @var Document
     */
    private $document;
    private $matchs;
    private $suggestions;
    private $revelance;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $this->matchs = new Map();
        $this->suggestions = new Map();
        $this->revelance = 0;
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * @return SplObjectStorage
     */
    public function getMatchs(): SplObjectStorage
    {
        return $this->matchs;
    }

    public function getSuggestions(): SplObjectStorage
    {
        return $this->suggestions;
    }

    /**
     * @return int
     */
    public function getRevelance(): int
    {
        return $this->revelance;
    }

    public function calculateRevelance()
    {
        $this->revelance /= $this->document->eucludianLength;
    }

    public function match(InvertedIndexEntry $queryTermEntry, InvertedIndexEntry $documentTermEntry)
    {
        $this->matchs->attach($queryTermEntry, $documentTermEntry);
        $this->revelance += $queryTermEntry->weight * $documentTermEntry->weight;
    }

    public function suggest(InvertedIndexEntry $suggestTermEntry, InvertedIndexEntry $documentTermEntry, float $dotProduct)
    {
        $this->matchs->attach($suggestTermEntry, $documentTermEntry);
        $this->revelance += $dotProduct / 2;
    }

    public function hash(): string
    {
        return $this->document->hash();
    }
}