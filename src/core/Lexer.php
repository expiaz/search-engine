<?php

namespace SearchEngine\Core;

use SearchEngine\Core\Document\Word;
use SearchEngine\Core\Index\Entry;
use Wamania\Snowball\French;

class Lexer
{

    // TODO recognize verbs

    private $stemmer;
    private $dico;

    public function __construct()
    {
        $this->stemmer = new French();
        $this->dico = new AntiDictionnary();
    }

    public function canonize($word): ?string
    {
        // example with c'est
        $lower = strtolower($word);

        if (false !== $pos = strpos($lower, '\'')) {
            // "c'" is ignored
            // $prefix = substr($lower, 0,$pos);
            // "est" is keep
            $suffix = substr($lower, $pos + 1);
        } else {
            $suffix = $lower;
        }

        // replace every special char
        $suffix = preg_replace(
            '#[^A-Za-z0-9]#',
            '',
            $suffix
        );
        // "est" is replaced with ""
        if ($this->dico->has($suffix)) {
            return null;
        }

        // words of 2 or less chars are taken off
        if (3 > strlen($suffix)) {
            return null;
        }

        $lemme = $this->stemmer->stem($suffix);
        // les 2 dernières lettres égales comme pour "travail" et "travailler"
        // qui donne "travail" et "travaill" => "travail"
        if ($lemme[-2] === $lemme[-1]) {
            $lemme = substr($lemme, 0, -1);
        }
        return $lemme;
    }

    /**
     * @param $sentence
     * @param float $type
     * @return Entry[]
     */
    public function lemmatise($sentence, $type = Word::BODY): array
    {
        $lemmes = [];
        $words = preg_split(
            '#[\s \\-.,;:]#',
            $sentence,
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        foreach ($words as $word) {
            $canonical = $this->canonize($word);
            if (null !== $canonical && strlen($canonical)) {
                if (! array_key_exists($canonical, $lemmes)) {
                    $lemmes[$canonical] = new Entry($type, 1);
                } else {
                    $lemmes[$canonical]->occurence(1, $type);
                }
            }
        }

        return $lemmes;
    }

}