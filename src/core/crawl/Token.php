<?php

namespace SearchEngine\Core\Crawl;

use SearchEngine\Core\Index\InvertedIndexEntry;
use SearchEngine\Core\Misc\Hashable;
use SearchEngine\Core\Misc\Map;

class Token implements Hashable
{
    private static $diminution = 0.01;

    const TITLE = 10;
    const H1 = 5;
    const H2 = 4;
    const H3 = 3;
    const H4 = 2;
    const H5 = 1.5;
    const H6 = 1.2;
    const LINK = 1.5;
    const BODY = 1;

    public $token;
    public $occurences;
    public $types;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->occurences = new Map();
        $this->types = [
            Token::TITLE => 0,
            Token::BODY => 0,
            Token::LINK => 0,
            Token::H1 => 0,
            Token::H2 => 0,
            Token::H3 => 0,
            Token::H4 => 0,
            Token::H5 => 0
        ];
    }

    public function merge(Token $token)
    {
        foreach ($token->types as $type => $occ) {
            $this->types[$type] += $occ;
        }
        $this->occurences->merge($token->occurences);
    }

    /**
     * @param string $needle
     * @param float $weight
     * @param int $occurences
     */
    public function occurence(string $needle, float $weight = Token::BODY, int $occurences = 1)
    {
        $this->types[$weight] += $occurences;
        $this->occurences->addKey($needle);
    }

    /**
     * @param float $IDF Inverse Document Frequency used to calculate TF-IDF
     * @return InvertedIndexEntry
     */
    public function compute(float $IDF): InvertedIndexEntry
    {
        $totalOccurences = 0;
        $totalWeight = 0;

        foreach ($this->types as $type => $occurences) {
            $totalOccurences += $occurences;
            // each new occurence of the word on the document is less important
            $i = 0;
            $wordWeight = 0;
            do {
                $totalWeight += $wordWeight;
                $wordWeight = $type - $i * self::$diminution;
            } while($wordWeight > 0 && ++$i < $occurences);
        }

        //$totalWeight += $totalOccurences * $IDF;
        return new InvertedIndexEntry($this->token, $totalWeight, $totalOccurences * $IDF, $totalOccurences, $this->occurences->toArray());
    }

    public function hash(): string
    {
        return $this->token;
    }
}