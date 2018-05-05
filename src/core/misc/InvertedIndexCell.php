<?php

namespace SearchEngine\Core\Misc;

use SearchEngine\Core\Document;
use SearchEngine\Core\Keyword;

class InvertedIndexCell
{

    private $document;
    private $strength;
    private $occurences;
    private $type;

    public function __construct(Document $document, Keyword $word)
    {
        $this->document = $document;
        $this->strength = $word->getStrength();
        $this->occurences = $word->getOccurences();
        $this->type = $word->getType();
    }

}