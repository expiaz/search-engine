<?php

namespace SearchEngine\Core\Misc;

class ArrayWrapper
{

    private $array;

    public function __construct()
    {
        $this->array = [];
    }

    public function add($el) {
        $this->array[] = $el;
    }

    public function toArray(): array
    {
        return $this->array;
    }

}