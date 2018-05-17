<?php

namespace SearchEngine\Core\Index;

use SearchEngine\Core\Misc\Map;

class Thesaurus
{

    private $dico;
    private $range;

    public function __construct(int $range = 5)
    {
        $this->range = $range;
        $this->dico = new Map();
    }

    /**
     * @param string[] $stems
     */
    public function add(array $stems)
    {
        $l = count($stems);
        for($i = 0; $i < $l; ++$i) {
            $stem = $stems[$i];

            if ($i > $this->range) {
                $start = $i - $this->range;
            } else {
                $start = 0;
            }

            // X words before
            for ($j = $start; $j < $i; ++$j) {
                $this->register($stem, $stems[$j]);
            }

            if ($i + 1 + $this->range < $l) {
                $end = $i + $this->range;
            } else {
                $end = $l;
            }

            // X words after
            for ($j = $i + 1; $j < $end; ++$j) {
                $this->register($stem, $stems[$j]);
            }
        }
    }

    private function register(string $stem, string $sibling)
    {
        if (! $this->dico->hasKey($stem)) {
            $this->dico->addKey($stem, new ThesaurusEntry($stem));
        }
        $this->dico->getKey($stem)->add($sibling);
    }

    public function get(string $stem, $default = null): ?ThesaurusEntry
    {
        return $this->dico->hasKey($stem) ? $this->dico->getKey($stem) : $default;
    }

    public function getSynonyms(string $stem)
    {
        $row = $this->get($stem);
        if (null === $row) {
            return [];
        }

        return $row->getSynonyms();
    }

    public function getAll(): array
    {
        return $this->dico->toArray();
    }

}