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

    private $maxDepth;
    private $maxTags;
    private $maxRedoundance;
    private $sites;

    /**
     * Crawler constructor.
     * @param int $maxDepth
     * @param int $maxUniqueTagIndexed
     * @param null|InvertedIndex $from
     * @param Document[] $documents
     */
    public function __construct(int $maxDepth = 100, int $maxUniqueTagIndexed = 5, int $maxLinksRedoundance = 5, ?InvertedIndex $from = null, array $documents = []){
        $this->maxDepth = $maxDepth;
        $this->maxTags = $maxUniqueTagIndexed;
        $this->queue = new SplQueue();
        $this->index = $from ?? new InvertedIndex();
        $this->lemmatiser = new Lexer();
        $this->documents = [];
        $this->sites = [];
        $this->maxRedoundance = $maxLinksRedoundance;
        foreach ($documents as $document) {
            $this->documents[$document->getUrl()->getUri()] = $document;
        }
    }

    public function crawl(string $url): InvertedIndex
    {
        $ressource = new Url($url);
        if ($ressource->shouldFollow($this->maxRedoundance)) {
            $this->queue->enqueue(new Document($ressource));
        }
        while ($this->maxDepth && $this->queue->count() > 0) {
            --$this->maxDepth;
            echo $this->maxDepth . " : ";
            $a = $this->queue->dequeue();
            echo $a->getUrl() . "\n";
            $this->parse($a);
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

        foreach ([
            'h1' => Word::H1,
            'h2' => Word::H2,
            'h3' => Word::H3
        ] as $tag => $type) {
            $collection = $dom->getElementsByTagName($tag);
            $max = $collection->length > $this->maxTags ? $this->maxTags : $collection->length;
            for ($i = 0; $i < $max; $i++) {
                $this->registerWords(
                    $document,
                    $collection->item($i)->textContent,
                    $type
                );
            }
        }

        /*foreach ($dom->getElementsByTagName('h1') as $h1) {
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
        }*/

        $links = $dom->getElementsByTagName('a');
        $followed = 0;
        for ($i = 0; $followed < $this->maxTags && $i < $links->length; ++$i) {
            $a = $links->item($i);
            $href = $a->getAttribute('href');
            $value = $a->textContent;
            if (strlen($href) && strlen($value)) {
                $url = new Url($href, $document->getUrl());

                // banlist for header / footer / menus links in the same site
                if (! array_key_exists($url->getHost(), $this->sites)) {
                    $this->sites[$url->getHost()] = [];
                }

                // follow the url, non duplicate in the document or reference the actual document
                // and can be accessed with 200 status code and html content type
                if ($url->shouldFollow(
                    $this->maxRedoundance,
                    $this->sites[$url->getHost()],
                    $document->referenceTo
                )) {
                    ++$followed;
                    echo $href . "\n";

                    // add it to the traversed links for this domain
                    $this->sites[$url->getHost()][] = $url;

                    // if not already indexed, add it
                    if (! array_key_exists($url->getUri(), $this->documents)) {
                        $doc = new Document($url);
                        // add it to the crawl list
                        $this->queue->enqueue($doc);
                        $this->documents[$url->getUri()] = $doc;
                    } else {
                        $doc = $this->documents[$url->getUri()];
                    }
                    // register the keywords for the link
                    /*$this->registerWords(
                        $document,
                        $value,
                        Word::LINK
                    );*/
                    // reference it from the actual document
                    $document->reference($doc);
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
        foreach ($this->lemmatiser->lemmatise($sentence, $type) as $word) {
           $this->index->addEntry($word, $document);
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