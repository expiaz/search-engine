<?php

namespace SearchEngine\Core\Search;

use SearchEngine\Core\Crawl\Token;
use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Misc\ArrayWrapper;
use SearchEngine\Core\Index\InvertedIndexEntry;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

class VectorialModel
{

    // term frequency (tf) => nb occurence term (t) in document (D)
    // document frequency => nb of documents where term (t) is
    // inverse document frequency (idf) of a term (t) is log(N/df) where N is nb of documents
    // tf-idf weight => tf * idf

    // cosine similarity
    // sim(d1, d2) = ( V(d1) . V(d2) ) / ( |V(d1)| * |V(d2)| )

    /**
     * @param InvertedIndex $index
     * @param Query $query
     * @return Hit[]
     */
    public static function cosim(InvertedIndex $index, Query $query): array
    {
        $results = new Map();

        /**
         * @var $associatedDocuments SplObjectStorage the documents that reference the keyword
         * @var $hit Hit
         * @var $documentTermEntry InvertedIndexEntry the term of a doc
         *
         * @var $queryTermToken Token the term querried by the user
         * @var $queryTermEntry InvertedIndexEntry
         *
         * @var $document Document documents associated with the word
         * @var $suggestions ArrayWrapper
         */
        foreach ($query->words as $canonical => $queryTermToken) {

            if ($index->has($canonical)) {
                $associatedDocuments = $index->get($canonical);

                // TF-IDF querried term
                $idf = log($index->length() / $associatedDocuments->count());
                $queryTermEntry = $queryTermToken->compute($idf);

                foreach ($associatedDocuments as $document) {
                    $hit = $results->get($document, null);
                    if (null === $hit) {
                        $results->add($hit = new Hit($document));
                    }

                    // retrieve doc entry
                    $documentTermEntry = $associatedDocuments[$document];

                    $hit->match(
                        $queryTermEntry,
                        $documentTermEntry
                    );
                }
            }

            /*
            foreach ($query->suggestions[$queryTermEntry]->toArray() as $canonicalSuggestion) {
                if ($index->has($canonicalSuggestion)) {
                    $associatedDocuments = $index->get($canonicalSuggestion);
                    foreach ($associatedDocuments as $document) {
                        $hit = $results->get($document, null);
                        if (null === $hit) {
                            $results->add($hit = new Hit($document));
                        }
                        $documentTermEntry = $associatedDocuments[$document];
                        $hit->suggest(
                            $queryTermEntry,
                            $documentTermEntry,
                            $queryTermEntry->getWeight() * $documentTermEntry->getWeight()
                        );
                    }
                }
            }
            */
        }

        foreach ($results->toArray() as $hit) {
            $hit->calculateRevelance();
        }

        $results->sort(function (Hit $a, Hit $b) {
            if ($a->getRevelance() === $b->getRevelance()) {
                return 0;
            }
            return ($a->getRevelance() < $b->getRevelance()) ? -1 : 1;
        });

        return $results->toArray();
    }
}