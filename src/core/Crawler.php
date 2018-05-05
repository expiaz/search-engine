<?php

namespace SearchEngine\Core;

use DOMElement;
use Masterminds\HTML5;
use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Url;
use SearchEngine\Core\Document\Word;
use SearchEngine\Core\Index\InvertedIndex;
use SplQueue;

class Crawler
{
    private $queue;
    private $index;
    private $lemmatiser;
    private $documents;
    private $fetched;

    private $maxDepth;

    /**
     * Crawler constructor.
     * @param int $maxDepth
     * @param null|InvertedIndex $from
     * @param Document[] $documents
     */
    public function __construct(int $maxDepth = 100, ?InvertedIndex $from = null, array $documents = []){
        $this->maxDepth = $maxDepth;
        $this->queue = new SplQueue();
        $this->index = $from ?? new InvertedIndex();
        $this->lemmatiser = new Lexer();
        $this->documents = $documents;
        $this->fetched = [];
        foreach ($documents as $document) {
            $this->fetched[] = md5($document->getUrl()->getUri());
        }
    }

    public function crawl(string $url): InvertedIndex
    {
        $ressource = new Url($url);
        if ($ressource->shouldFollow()) {
            $this->queue->enqueue(new Document($ressource));
        }
        while ($this->maxDepth && $this->queue->count() > 0) {
            --$this->maxDepth;
            $this->parse($this->queue->dequeue());
        }

        return $this->index;
    }

    /**
     * @param Document $document
     */
    private function parse(Document $document)
    {
        $html = $document->getUrl()->getRessource();
        if (null === $document) {
            return;
        }

        $dom = (new HTML5())->loadHTML($html);

        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            $title = $titles->item(0);
            $this->registerWords(
                $document,
                $title->textContent,
                Word::TITLE
            );
        }

        foreach ($dom->getElementsByTagName('h1') as $h1) {
            $this->registerWords(
                $document,
                $h1->textContent,
                Word::H1
            );
        }
        foreach ($dom->getElementsByTagName('h2') as $h2) {
            $this->registerWords($document,
                $h2->textContent,
                Word::H2
            );
        }
        foreach ($dom->getElementsByTagName('h3') as $h3) {
            $this->registerWords(
                $document,
                $h3->textContent,
                Word::H3
            );
        }
        foreach ($dom->getElementsByTagName('h4') as $h4) {
            $this->registerWords(
                $document,
                $h4->textContent,
                Word::H4
            );
        }

        foreach ($dom->getElementsByTagName('a') as $a) {
            /**
             * @var $a DOMElement
             */
            $href = $a->getAttribute('HREF');
            $value = $a->textContent;
            if (strlen($href) && strlen($value)) {
                $url = new Url($href, $document->getUrl());
                if ($url->shouldFollow() && !in_array($url->getUri(), $this->uris)) {
                    $this->registerWords(
                        $document,
                        $value,
                        Word::LINK
                    );

                    $this->uris[] = md5($url->getUri());

                    $doc = new Document($url);
                    $document->reference($doc);

                    $this->queue->enqueue($doc);
                }
            }
        }

        // search words in the full document
        /*
        foreach ($document->getWords() as $word) {
            $pos = 0;
            while (false !== $pos = strpos(
                $dom->textContent,
                $word->getValue(),
                $pos
            )) {
                $word->occurence();
            }
        }
        */
    }

    private function registerWords(Document $document, $sentence, $type)
    {
        foreach ($this->lemmatiser->lex($sentence, $type) as $word) {
           $this->index->addEntry($word, $document);
        }
    }

    /**
     * @return null|InvertedIndex
     */
    public function getIndex()
    {
        return $this->index;
    }


    /**
     * @return array|Document[]
     */
    public function getDocuments()
    {
        return $this->documents;
    }

}