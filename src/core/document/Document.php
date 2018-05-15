<?php

namespace SearchEngine\Core\Document;

use SearchEngine\Core\Index\Entry;
use SearchEngine\Core\Misc\Hashable;

class Document implements Hashable
{
    /**
     * @var Url
     */
    private $url;
    /**
     * @var Word[]
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
    /**
     * @var int used at each query
     */
    public $revelance;

    public function __construct(Url $url)
    {
        $this->url = $url;
        $this->words = [];
        $this->referenceTo = [];
        $this->referencedBy = [];
        $this->pageRank = 0;

        $this->title = $url->getUri();

        $this->eucludianLength = 0;

        $this->revelance = 0;
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

    public function addWord(string $canonical, Entry $entry)
    {
        $this->words[$canonical] = $entry;
    }

    public function haveWord(string $canonical): bool
    {
        return array_key_exists($canonical, $this->words);
    }

    public function getWord(string $canonical): ?Entry
    {
        return $this->words[$canonical];
    }

    /**
     * @return Entry[]
     */
    public function getWords(): array
    {
        return $this->words;
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
        return "URI:{$this->url->getUri()} PR:{$this->pageRank} SIM:{$this->revelance}";
    }

    public function hash(): string
    {
        return $this->url->hash();
    }

}