<?php

namespace SearchEngine\Core\Document;

class Document
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

    private $pageRank;

    public function __construct(Url $url)
    {
        $this->url = $url;
        $this->words = [];
        $this->referenceTo = [];
        $this->referencedBy = [];
        $this->pageRank = 0;
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

    public function addWord(Word $word)
    {
        $this->words[] = $word;
    }

    /**
     * @return Word[]
     */
    public function getWords(): array
    {
        return $this->words;
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

}