<?php

namespace SearchEngine\Core\Misc;

class Set {

    private $values;

    public function __construct(?array $from = [])
    {
        $this->values = [];

        foreach ($from as $v) {
            $this->add($v);
        }
    }

    /**
     * @param $value mixed
     * @param string $key
     */
    public function add($value, ?string $key = null)
    {
        if (! $this->has($value, $key)) {
            if ($key !== null) {
                $this->values[$key] = $value;
            } else {
                $this->values[] = $value;
            }
        }
    }

    public function has($value, ?string $key = null)
    {
        return $key !== null
            ? array_key_exists($key, $this->values)
            : in_array($value, $this->values);
    }

    public function toArray(): array
    {
        return $this->values;
    }

}