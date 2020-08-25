<?php

namespace Tests\Helpers\Wordpress;


use Illuminate\Contracts\Support\Arrayable;

abstract class BaseBuilder implements \JsonSerializable, Arrayable, \ArrayAccess
{
    protected $data;

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function offsetExists($offset)
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
}
