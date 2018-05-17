<?php

namespace SearchEngine\Core\Search;

use SearchEngine\Core\Crawl\Parser;
use SearchEngine\Core\Index\InvertedIndexEntry;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Index\Thesaurus;
use SearchEngine\Core\Lexer;
use SearchEngine\Core\Misc\ArrayWrapper;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

class Query
{

    public $query;
    /**
     * @var InvertedIndexEntry[]
     */
    public $words;
    /**
     * @var SplObjectStorage
     */
    public $suggestions;
    private $synonyms;

    /**
     * Search constructor.
     * @param string $query
     * @param Parser $parser
     */
    public function __construct(string $query, Parser $parser)
    {
        $this->query = $query;
        $this->words = $parser->tokenize($query);
        $this->suggestions = [];
        $this->synonyms = [];
    }

    /**
     * @param InvertedIndex $index
     * @param Thesaurus $thesaurus
     */
    public function complete(InvertedIndex $index, Thesaurus $thesaurus)
    {
        foreach ($this->words as $queryCanonical => $token) {
            $this->suggestions[$queryCanonical] = [];
            $this->synonyms[$queryCanonical] = [];

            if (! $index->has($queryCanonical)) {
                foreach ($index->all() as $canonical => $_) {
                    // starts with or at least 2/3 of the word
                    if (
                        0 === strpos($canonical, $queryCanonical) ||
                        levenshtein($queryCanonical, $canonical) <= strlen($canonical) / 3
                    ) {
                        $this->suggestions[$queryCanonical][] = $canonical;
                    }
                }
            } else {
                // for the moment, extends to synonyms only for existing requested words
                $this->synonyms[$queryCanonical] = $thesaurus->getSynonyms($queryCanonical);
            }
        }
    }

}