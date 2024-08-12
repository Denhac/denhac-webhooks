<?php

namespace App\External\WooCommerce;

use Illuminate\Support\Collection;

class MetaData implements \ArrayAccess
{
    public function __construct(
        private Collection|array $sourceMetadata
    )
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        foreach ($this->sourceMetadata as $item) {
            if ($item['key'] == $offset) {
                return true;
            }
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        foreach ($this->sourceMetadata as $item) {
            if ($item['key'] == $offset) {
                return $item['value'];
            }
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        foreach ($this->sourceMetadata as $index => $item) {
            if ($item['key'] == $offset) {
                $this->sourceMetadata[$index]['value'] = $value;
            }
        }

        $this->sourceMetadata[] = [
            'key' => $offset,
            'value' => $value,
        ];
    }

    public function offsetUnset(mixed $offset): void
    {
        $itemToUnset = null;
        foreach ($this->sourceMetadata as $item) {
            if ($item['key'] == $offset) {
                $itemToUnset = $item;
                break;
            }
        }

        if(! is_null($itemToUnset)) {
            unset($this->sourceMetadata[$itemToUnset]);
        }
    }
}
