<?php

namespace SearchEngine\Core\Crawl;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Url;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

/**
 * Index of Documents
 * @package SearchEngine\Core\Crawl
 */
class Index
{

    private $documents;
    private $words;
    private $limit;
    private $visited;

    public function __construct(int $banLimit = 5)
    {
        $this->limit = $banLimit;

        $this->documents = new Map();
        $this->words = new Map();
        $this->visited = new Map();
    }

    public function add(Document $document, Token $token)
    {
        if (! $this->haveRow($token->token)) {
            $this->words->addKey($token->token, new SplObjectStorage());
        }
        /**
         * @var $entries SplObjectStorage
         */
        $entries = $this->words->getKey($token->token);
        if (! $entries->contains($document)) {
            $entries->attach($document, $token);
        } else {
            $entries->offsetGet($document)->merge($token);
        }

        if (! $this->documents->has($document)) {
            $this->documents->add($document);
        }

        // banlist
        /**
         * @var $pages SplObjectStorage
         */
        /*
        foreach ($document->referenceTo as $ref) {
            $host = $ref->getUrl()->getHost();
            if (! $this->visited->hasKey($host)) {
                $this->visited->addKey($host, new SplObjectStorage());
            }
            $pages = $this->visited->getKey($host);
            $passages = $pages->offsetExists($ref) ? $pages->offsetGet($ref) : 0;
            $pages->attach($ref, $passages + 1);
        }
        */
    }

    public function haveRow(string $canonical): bool
    {
        return $this->words->hasKey($canonical);
    }

    public function haveDoc(string $url): bool
    {
        return $this->documents->hasKey($url);
    }

    public function getRow(string $canonical, $default = null): SplObjectStorage
    {
        return $this->words->getKey($canonical, $default);
    }

    public function getDoc(string $uri, $default = null): ?Document
    {
        return $this->documents->getKey($uri, $default);
    }

    public function isBanned(Document $document)
    {
        /**
         * @var $list SplObjectStorage
         */
        $list = $this->visited->getKey($document->getUrl()->getHost(), null);
        if (null === $list) {
            return false;
        }
        $passages = $list->offsetExists($document) ? $list->offsetGet($document) : 0;
        return $passages > $this->limit;
    }

    /**
     * @return Map
     */
    public function getDocuments(): Map
    {
        return $this->documents;
    }

    public function addDoc(Document $document)
    {
        $this->documents->add($document);
    }

    /**
     * @return array [InvertedIndex, Map<Document>]
     */
    public function compute(): array
    {
        // compute host index and banned links

        $size = count($this->words->toArray());
        $length = count($this->documents->toArray());

        // TF-IDF calculation
        /**
         * @var $tokens SplObjectStorage <Document, Token>
         * @var $document Document
         * @var $token Token
         */
        $N = $length;
        foreach ($this->words->toArray() as $canonical => $tokens) {
            // document frequency
            $df = $tokens->count();
            // inverse document frequency
            $idf = log($N / $df);
            foreach ($tokens as $document) {
                // replace tokens with entries
                $tokens->attach($document, $tokens->offsetGet($document)->compute($idf));
                $document->addWord($tokens[$document]);
            }
        }

        // compute euclidian length of each document
        foreach ($this->documents->toArray() as $document) {
            $len = 0;
            foreach ($document->getWords() as $word) {
                $len += $word->weight * $word->weight;
            }
            $document->eucludianLength = sqrt($len);
        }

        return [
            new InvertedIndex($this->words, $size, $length),
            PageRank::pageRank($this->documents->toArray(), $N)
        ];
    }

}