<?php

namespace SearchEngine\Core;

use SearchEngine\Core\Document\Document;
use SearchEngine\Core\Document\Query;
use SearchEngine\Core\Index\Entry;
use SearchEngine\Core\Index\InvertedIndex;
use SearchEngine\Core\Misc\Set;

class VectorialModel
{

    // term frequency (tf) => nb occurence term (t) in document (D)  D->getWords()[t]->getOccurences()
    // document frequency => nb of documents where term (t) is  count($index[t])

    // inverse document frequency (idf) of a term (t) is log(N/df) where N is nb of documents  log(count($documents)/count($index[t]))

    // tf-idf weight => tf * idf =>

    // cosine similarity
    // sim(d1, d2) = ( V(d1) . V(d2) ) / ( |V(d1)| * |V(d2)| )

    public static function cosim(InvertedIndex $index, Query $query)
    {
        $results = new Set();

        /**
         * @var $dotProduct int dot product between $querriedTerm and $foundTerm
         * @var $foundTerm Entry the term of a doc
         * @var $querriedTerm Entry the term querried by the user
         * @var $doc Document documents associated with the word
         */
        foreach ($query->words as $canonical => $querriedTerm) {

            if ($index->has($canonical)) {
                $docs = $index->get($canonical);

                // TF-IDF querried term
                $querriedTerm->weight = $querriedTerm->occurences * log($index->length() / $docs->count());

                foreach ($docs as $doc) {
                    $results->add($doc, $doc->getUrl()->getUri());

                    // retrieve doc entry
                    $foundTerm = $docs[$doc];
                    // doc weight * query weight
                    $doc->revelance += $querriedTerm->weight * $foundTerm->weight;
                }
            }
        }


        $documents = $results->toArray();
        foreach ($documents as $doc) {
            $doc->revelance /= $doc->eucludianLength;
        }
        usort($documents,function (Document $a, Document $b) {
            return $a->revelance < $b->revelance;
        });

        return $documents;
    }
}