<?php

namespace KobeniFramework\Controllers\RequestDataMixing;

interface ArrayAccess
{
    public function offsetExists($offset);
    public function offsetGet($offset): mixed;
    public function offsetSet($offset, $value);
    public function offsetUnset($offset);
    public function toArray(): array;
}
