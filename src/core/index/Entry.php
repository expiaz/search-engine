<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Word;

class Entry
{
    public $word;
    public $document;

    public function __construct(Word $word, Document $document)
    {
        $this->word = $word;
        $this->document = $document;
    }

}