<?php

namespace SearchEngine\Core;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Misc\ArrayWrapper;
use SearchEngine\Core\Search\Hit;
use SearchEngine\Core\Search\Query;
use SearchEngine\Core\Index\Entry;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Misc\Map;
use SplObjectStorage;

class VectorialModel
{

    // term frequency (tf) => nb occurence term (t) in document (D)  D->getWords()[t]->getOccurences()
    // document frequency => nb of documents where term (t) is  count($index[t])
    // inverse document frequency (idf) of a term (t) is log(N/df) where N is nb of documents  log(count($documents)/count($index[t]))
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
         * @var $documentTermEntry Entry the term of a doc
         * @var $queryTermEntry Entry the term querried by the user
         * @var $document Document documents associated with the word
         * @var $suggestions ArrayWrapper
         */
        foreach ($query->suggestions as $canonical => $queryTermEntry) {

            if ($index->has($canonical)) {
                $associatedDocuments = $index->get($canonical);

                // TF-IDF querried term
                $queryTermEntry->weight = $queryTermEntry->occurences * log(
                    $index->length() / $associatedDocuments->count()
                );

                foreach ($associatedDocuments as $document) {
                    $hit = $results->get($document, null);
                    if (null === $hit) {
                        $results->add($hit = new Hit($document));
                    }

                    // retrieve doc entry
                    $documentTermEntry = $associatedDocuments[$document];

                    $hit->match(
                        $queryTermEntry,
                        $documentTermEntry,
                        $queryTermEntry->weight * $documentTermEntry->weight
                    );

                    foreach ($query->suggestions[$queryTermEntry] as $suggestions) {
                        foreach ($suggestions->toArray() as $canonicalSuggestion) {
                            if ($index->has($canonicalSuggestion)) {
                                foreach ($index->get($canonicalSuggestion) as $document) {
                                    $hit = $results->get($document, null);
                                    if (null === $hit) {
                                        $results->add($hit = new Hit($document));
                                    }
                                    $documentTermEntry = $associatedDocuments[$document];
                                    $hit->suggest(
                                        $queryTermEntry,
                                        $documentTermEntry,
                                        $queryTermEntry->weight * $documentTermEntry->weight
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($results->toArray() as $hit) {
            $hit->calculateRevelance();
        }

        $results->sort(function (Hit $a, Hit $b) {
            return $a->getRevelance() < $b->getRevelance();
        });

        return $results->toArray();
    }
}