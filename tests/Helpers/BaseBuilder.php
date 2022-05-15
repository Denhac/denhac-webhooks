<?php

namespace Tests\Helpers;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseBuilder implements \JsonSerializable, Arrayable, \ArrayAccess
{
    protected array $data;

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function __get($name)
    {
        // TODO Make this work on methods that return an object like billing
        return $this->data[$name];
    }
}
