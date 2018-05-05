<?php

namespace SearchEngine\Core\Document;

class Word
{

    const TITLE = 1.0;
    const H1 = 0.9;
    const H2 = 0.8;
    const H3 = 0.7;
    const H4 = 0.6;
    const H5 = 0.5;
    const H6 = 0.4;
    const LINK = 0.2;
    const BODY = 0.1;

    private $value;
    private $canonical;
    private $type;
    private $strength;
    private $occurences;

    public function __construct(string $value, string $canonical, float $type)
    {
        $this->value = $value;
        $this->canonical = $canonical;
        $this->type = $type;
        $this->strength = $type;
        $this->occurences = 1;
    }

    /**
     * @param int $nb
     * @param float $type
     */
    public function addOccurence($nb = 1, $type = self::BODY)
    {
        $this->occurences += $nb;
        $this->strength += $nb * $type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getCanonical(): string
    {
        return $this->canonical;
    }

    /**
     * @return float
     */
    public function getType(): float
    {
        return $this->type;
    }

    /**
     * @return float
     */
    public function getStrength(): float
    {
        return $this->strength;
    }

    /**
     * @return mixed
     */
    public function getOccurences()
    {
        return $this->occurences;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}