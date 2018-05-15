<?php

namespace SearchEngine\Core\Misc;

class Map {

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
    public function add(Hashable $value, ?string $key = null)
    {
        $this->values[$key ?? $value->hash()] = $value;
    }

    public function get(Hashable $value, $default = null)
    {
        return $this->has($value) ? $this->values[$value->hash()] : $default;
    }

    public function getKey(string $key, $default = null)
    {
        return $this->hasKey($key) ? $this->values[$key] : $default;
    }

    public function has(Hashable $value)
    {
        return array_key_exists($value->hash(), $this->values);
    }

    public function hasKey(string $key)
    {
        return array_key_exists($key, $this->values);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function sort(callable $cb)
    {
        usort($this->values, $cb);
    }

}