<?php

namespace KobeniFramework\Controllers\RequestDataMixing;

use ArrayAccess;

/////// idk how to mix object with array
class MixedAccessData implements ArrayAccess
{

    private $data;

    public function __construct($data)
    {
        $this->data = (array) $data;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    // Magic method for object-style access (via __get)
    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    // Magic method for object-style access (via __set)
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function toArray(): array{
        return $this->data;
    }
}
