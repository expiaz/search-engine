<?php

namespace SearchEngine\Core\Document;

class Document
{
    private $url;
    private $words;
    public $referenceTo;
    public $referencedBy;

    public function __construct(Url $url)
    {
        $this->url = $url;
        $this->words = [];
        $this->referenceTo = [];
        $this->referencedBy = [];
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


}