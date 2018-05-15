<?php

namespace SearchEngine\Core\Search;

use SearchEngine\Core\Index\Entry;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Lexer;
use SearchEngine\Core\Misc\ArrayWrapper;
use SplObjectStorage;

class Query
{

    public $query;
    /**
     * @var Entry[]
     */
    public $words;
    /**
     * @var SplObjectStorage
     */
    public $suggestions;

    /**
     * Search constructor.
     * @param string $query
     * @param null|Lexer $lexer
     */
    public function __construct(string $query, ?Lexer $lexer = null)
    {
        $this->query = $query;
        $this->words = ($lexer ?? new Lexer())->lemmatise($query);
        $this->suggestions = new SplObjectStorage();
    }

    public function complete(InvertedIndex $index)
    {
        foreach ($this->words as $querried => $entry) {
            if (! $index->has($querried)) {
                foreach ($index->all() as $canonical => $_) {
                    // starts with or at least half of the word
                    if (
                        0 === strpos($canonical, $querried) ||
                        levenshtein($querried, $canonical) <= strlen($canonical) / 3
                    ) {
                        if (! $this->suggestions->contains($entry)) {
                            $this->suggestions->attach($entry, new ArrayWrapper());
                        }
                        $this->suggestions[$entry]->add($canonical);
                    }
                }
            } else {
                $this->suggestions->attach($entry, new ArrayWrapper());
            }
        }
    }

}