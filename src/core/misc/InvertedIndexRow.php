<?php

namespace SearchEngine\Core\Misc;

use SearchEngine\Core\Document;
use SearchEngine\Core\Keyword;

class InvertedIndexRow
{

    private $word;
    private $cells;

    public function __construct(Keyword $word)
    {
        $this->word = $word;
        $this->cells = [];
    }

    /**
     * @return Keyword
     */
    public function getWord(): Keyword
    {
        return $this->word;
    }

    /**
     * @return InvertedIndexCell[]
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    public function addDocument(Document $document)
    {
        $this->cells[] = new InvertedIndexCell($document, $this->word);
    }

}