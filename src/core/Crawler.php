<?php

namespace SearchEngine\Core;

use DOMDocument;
use DOMElement;
use Masterminds\HTML5;
use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Url;
use SearchEngine\Core\Document\Word;
use SearchEngine\Core\Index\Entry;
use SearchEngine\Core\Index\InvertedIndex;
use SplQueue;

class Crawler
{
    private $queue;
    private $index;
    private $lemmatiser;
    private $documents;
    private $parser;

    private $maxDocuments;
    private $maxTags;

    /**
     * Crawler constructor.
     * @param int|Document[] $documents
     * @param int $tags
     * @param null|InvertedIndex $from
     */
    public function __construct(int $documents = 100, int $tags = 5, ?InvertedIndex $from = null){

        $this->queue = new SplQueue();
        $this->parser = new HTML5();
        $this->index = $from ?? new InvertedIndex();
        $this->lemmatiser = new Lexer();
        $this->documents = [];

        $this->maxDocuments = $documents ?? 1;
        $this->maxTags = $tags ?? 1;
    }


    /**
     * @param DOMDocument $dom
     * @param $tagName
     * @return DOMElement[]
     */
    private function getTags(DOMDocument $dom, string $tagName): array
    {
        $list = $dom->getElementsByTagName($tagName);
        $collection = iterator_to_array($list);
        if ($list->length > $this->maxTags) {
            return array_slice($collection, 0, $this->maxTags);
        }
        return $collection;
    }

    /**
     * @param DOMDocument $dom
     * @param $tagName
     * @return DOMElement|null
     */
    private function getTag(DOMDocument $dom, string $tagName): ?DOMElement
    {
        $list = $dom->getElementsByTagName($tagName);
        return $list->length ? $list->item(0) : null;
    }

    public function crawl(string $url): InvertedIndex
    {
        $ressource = new Url($url);
        if ($ressource->shouldFollow()) {
            $doc = new Document($ressource);
            $this->documents[$doc->getUrl()->getUri()] = $doc;
            $this->queue->enqueue($doc);
        }

        while ($this->maxDocuments-- && $this->queue->count()) {
            $this->parse($this->queue->dequeue());
        }

        // TF-IDF calculation
        $N = $this->index->length();
        foreach ($this->index->all() as $canonical => $entries) {
            // document frequency
            $df = $entries->count();
            // inverse document frequency
            $idf = log($N / $df);
            foreach ($entries as $document) {
                /**
                 * @var $document Document
                 * @var $entry Entry
                 */
                $entry = $entries[$document];
                // term frequency
                $tf = $entry->occurences;
                // tf-idf
                $tfidf = $tf * $idf;
                // TODO merge semantic weight with tfidf
                $entry->weight = $tfidf;
            }
        }

        // compute euclidian length of each document
        foreach ($this->documents as $document) {
            $len = 0;
            foreach ($document->getWords() as $word) {
                $len += $word->weight * $word->weight;
            }
            $document->eucludianLength = sqrt($len);
        }

        return $this->index;
    }



    /**
     * @param Document $document
     */
    private function parse(Document $document)
    {
        echo "parse {$this->maxDocuments} : {$document->getUrl()->getUrl()}\n";

        echo "\tLOAD ... ";

        $html = $document->getUrl()->getRessource();
        if (null === $html) {
            return;
        }

        echo "OK\n";

        echo "\tPARSE ... ";

        $dom = $this->parser->loadHTML($html);
        $name = null;

        $title = $this->getTag($dom, 'title');
        if ($title !== null) {
            $value = trim($title->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Word::TITLE);
                $name = $value;
            }
        }

        foreach ($this->getTags($dom, 'h1') as $h1) {
            $value = trim($h1->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Word::H1);
                if ($name === null) {
                    $name = $value;
                }
            }
        }

        if ($name !== null) {
            $document->setTitle($name);
        }

        $links = $dom->getElementsByTagName('a');

        echo "OK\n";

        $followed = 0;
        for ($i = 0; $followed < $this->maxTags && $i < $links->length; ++$i) {
            $a = $links->item($i);
            $href = trim($a->getAttribute('href'));
            $value = trim($a->textContent);
            if (strlen($href) && strlen($value)) {
                $url = new Url($href, $document->getUrl());

                // follow the url, non duplicate in the document or reference the actual document
                // and can be accessed with 200 status code and html content type
                if ($url->shouldFollow($this->documents, $document->referenceTo)) {
                    ++$followed;

                    // if not already indexed, add it
                    if (! array_key_exists($url->getUri(), $this->documents)) {
                        $doc = new Document($url);
                        // add it to the crawl list
                        $this->documents[$url->getUri()] = $doc;
                        $this->queue->enqueue($doc);
                    } else {
                        $doc = $this->documents[$url->getUri()];
                    }

                    // register the keywords for the link
                    $this->registerWords(
                        $document,
                        $value,
                        Word::LINK
                    );
                    // reference it from the actual document
                    $document->reference($doc);
                }
            }
        }

        foreach ($this->lemmatiser->lemmatise(
            trim($dom->textContent),
            Word::BODY
        ) as $canonical => $entry) {
            if ($document->haveWord($canonical)) {
                // remove the ones found before as titles and links
                $base = $document->getWord($canonical)->occurences;
                // $entry->occurence(- $document->getWord($canonical)->occurences);
                $entry->occurences -= $base;
                $entry->weight -= $base * Word::BODY;
                $document->getWord($canonical)->merge($entry);
            }
        }
    }

    private function registerWords(Document $document, $sentence, $type)
    {
        foreach ($this->lemmatiser->lemmatise($sentence, $type) as $canonnical => $entry) {
            if ($document->haveWord($canonnical)) {
                $document->getWord($canonnical)->merge($entry);
            } else {
                $this->index->add($document, $canonnical, $entry);
                $document->addWord($canonnical, $entry);
            }
        }
    }

    /**
     * @return InvertedIndex
     */
    public function getIndex()
    {
        return $this->index;
    }


    /**
     * @return Document[]
     */
    public function getDocuments()
    {
        return $this->documents;
    }

}