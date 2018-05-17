<?php

namespace SearchEngine\Core\Crawl;

use DOMDocument;
use DOMElement;
use Masterminds\HTML5;
use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Url;
use SearchEngine\Core\Index\Thesaurus;
use SearchEngine\Core\Misc\Logger;
use SplQueue;

class Crawler
{
    private $queue;
    private $index;
    private $lemmatiser;
    private $parser;
    private $thesaurus;

    private $maxDocuments;
    private $maxTags;

    /**
     * Crawler constructor.
     * @param int $documents
     * @param int $tags
     */
    public function __construct(int $documents = 100, int $tags = -1){

        $this->queue = new SplQueue();
        $this->parser = new HTML5();
        $this->index = new Index();
        $this->lemmatiser = new Parser();
        $this->thesaurus = new Thesaurus();

        $this->maxDocuments = $documents > 0 ? $documents : 5;
        $this->maxTags = $tags;
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

    /**
     * @param string $url
     * @return array [InvertedIndex, Map<Document>, Thesaurus]
     */
    public function crawl(string $url): array
    {
        $ressource = new Url($url);
        if ($ressource->shouldFollow($this->index->getDocuments())) {
            $this->index->addDoc($doc = new Document($ressource));
            $this->queue->enqueue($doc);
        }

        while ($this->maxDocuments-- && $this->queue->count()) {
            $this->parse($this->queue->dequeue());
        }

        $infos = $this->index->compute();
        $infos[] = $this->thesaurus;

        return $infos;
    }

    /**
     * @param Document $document
     */
    private function parse(Document $document)
    {
        Logger::logln("parse {$this->maxDocuments} : {$document->getUrl()->getUrl()}");

        Logger::log("LOAD ... ");

        $html = $document->getUrl()->getRessource();
        if (null === $html) {
            return;
        }

        Logger::logln("OK");
        Logger::log("PARSE ... ");

        $dom = $this->parser->loadHTML($html);
        $name = null;

        $title = $this->getTag($dom, 'title');
        if ($title !== null) {
            $value = trim($title->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::TITLE);
                $name = $value;
            }
        }

        foreach ($this->getTags($dom, 'h1') as $h1) {
            $value = trim($h1->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::H1);
                if ($name === null) {
                    $name = $value;
                }
            }
        }

        if ($name !== null) {
            $document->setTitle($name);
        }

        foreach ($this->getTags($dom, 'h2') as $h2) {
            $value = trim($h2->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::H2);
            }
        }

        foreach ($this->getTags($dom, 'h3') as $h3) {
            $value = trim($h3->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::H3);
            }
        }

        foreach ($this->getTags($dom, 'h4') as $h4) {
            $value = trim($h4->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::H4);
            }
        }

        foreach ($this->getTags($dom, 'h5') as $h5) {
            $value = trim($h5->textContent);
            if (strlen($value)) {
                $this->registerWords($document, $value, Token::H5);
            }
        }

        $links = $dom->getElementsByTagName('a');

        Logger::logln("OK");

        $followed = 0;
        $max = $this->maxTags > 0 ? $this->maxTags : $links->length;
        for ($i = 0; $followed < $max && $i < $links->length; ++$i) {
            $a = $links->item($i);
            $href = trim($a->getAttribute('href'));
            $value = trim($a->textContent);

            if (strlen($href) && strlen($value)) {
                $url = new Url($href, $document->getUrl());

                // follow the url, non duplicate in the document or reference the actual document
                // and can be accessed with 200 status code and html content type
                if ($url->shouldFollow($this->index->getDocuments(), $document->referenceTo)) {
                    ++$followed;

                    // if not already indexed, add it
                    if (! $this->index->haveDoc($url->getUri())) {
                        $doc = new Document($url);
                        $this->index->addDoc($doc);
                        // add it to the crawl list
                        $this->queue->enqueue($doc);
                    } else {
                        $doc = $this->index->getDoc($url->getUri());
                    }

                    // register the keywords for the link
                    $this->registerWords(
                        $document,
                        $value,
                        Token::LINK
                    );
                    // reference it from the actual document
                    $document->reference($doc);
                }
            }
        }

        // body occurences
        $qp = html5qp($dom, 'body *:not(script):not(h1):not(h2):not(h3):not(h4):not(h5):not(a)');
        foreach ($qp->toArray() as $tag) {
            foreach ($this->lemmatiser->tokenize($tag->textContent) as $canonical => $token) {
                if (
                    $this->index->haveRow($canonical) &&
                    $this->index->getRow($canonical)->contains($document)
                ) {
                    $existingToken = $this->index->getRow($canonical)->offsetGet($document);
                    $existingToken->merge($token);
                }
            }
        }

        // thesaurus
        $qp = html5qp($dom, 'body *:not(script)');
        foreach ($qp->toArray() as $tag) {
            $this->thesaurus->add($this->lemmatiser->normalize($tag->textContent));
        }
    }

    private function registerWords(Document $document, $sentence, $type)
    {
        foreach ($this->lemmatiser->tokenize($sentence, $type) as $token) {
            $this->index->add($document, $token);
        }
    }

}