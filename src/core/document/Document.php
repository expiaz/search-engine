<?php

namespace SearchEngine\Core\Document;

use SearchEngine\Core\Index\InvertedIndexEntry;
use SearchEngine\Core\Misc\Hashable;
use SearchEngine\Core\Misc\Map;

class Document implements Hashable
{
    /**
     * @var Url
     */
    private $url;
    /**
     * @var InvertedIndexEntry[]
     */
    private $words;
    /**
     * @var Document[]
     */
    public $referenceTo;
    /**
     * @var Document[]
     */
    public $referencedBy;

    private $title;

    private $pageRank;


    public $eucludianLength;

    public function __construct(Url $url)
    {
        $this->url = $url;
        $this->words = new Map();
        $this->referenceTo = [];
        $this->referencedBy = [];
        $this->pageRank = 0;

        $this->title = $url->getUri();

        $this->eucludianLength = 0;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function reference(Document $document)
    {
        $document->referencedBy[] = $this;
        $this->referenceTo[] = $document;
    }

    public function addWord(InvertedIndexEntry $entry)
    {
        $this->words->addKey($entry->canonical, $entry);
    }

    public function haveWord(string $canonical): bool
    {
        return $this->words->hasKey($canonical);
    }

    public function getWord(string $canonical, $default = null): ?InvertedIndexEntry
    {
        return $this->words->getKey($canonical, $default);
    }

    /**
     * @return InvertedIndexEntry[]
     */
    public function getWords(): array
    {
        return $this->words->toArray();
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param float $pageRank
     */
    public function setPageRank(float $pageRank)
    {
        $this->pageRank = $pageRank;
    }

    /**
     * @return float
     */
    public function getPageRank(): float
    {
        return $this->pageRank;
    }

    public function __toString()
    {
        return "URI:{$this->url->getUri()} PR:{$this->pageRank}";
    }

    public function hash(): string
    {
        return $this->url->hash();
    }

}