<?php

namespace SearchEngine\Core\Document;

use SearchEngine\Core\Misc\Hashable;

class Word implements Hashable
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

    public static function from(string $word)
    {
        return new self($word);
    }

    private $value;

    public function __construct(string $value)
    {
        $this->value = strtolower($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function hash(): string
    {
        return $this->value;
    }
}