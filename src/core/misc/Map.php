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
     * @param Hashable $key
     * @param $value mixed
     */
    public function add(Hashable $key, $value = null)
    {
        $this->values[$key->hash()] = $value ?? $key;
    }

    /**
     * @param string $key
     * @param $value
     */
    public function addKey(string $key, $value = null)
    {
        $this->values[$key] = $value ?? $key;
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
        uasort($this->values, $cb);
    }

    public function merge(Map $map)
    {
        foreach ($map->toArray() as $key => $value) {
            $this->addKey($key, $value);
        }
    }

}