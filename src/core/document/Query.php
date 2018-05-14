<?php

namespace SearchEngine\Core\Document;

use SearchEngine\Core\Lexer;

class Query
{

    public $query;
    public $words;

    /**
     * Search constructor.
     * @param string $query
     * @param null|Lexer $lexer
     */
    public function __construct(string $query, ?Lexer $lexer = null)
    {
        $this->query = $query;
        $this->words = ($lexer ?? new Lexer())->lemmatise($query);
    }

    public function tfidf()
    {

    }

}