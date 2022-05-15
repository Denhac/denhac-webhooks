<?php

namespace Tests\Helpers\Wordpress;


trait HasMetaData
{
    private int $metaKeyId = 1;

    public function meta_data($key, $value): static
    {
        if (! array_key_exists('meta_data', $this->data)) {
            $this->data['meta_data'] = [];
        }

        foreach ($this->data['meta_data'] as $index => $item) {
            if ($item['key'] == $key) {
                $this->data['meta_data'][$index]['value'] = $value;

                return $this;
            }
        }

        $this->data['meta_data'][] = [
            'id' => $this->metaKeyId,
            'key' => $key,
            'value' => $value,
        ];

        $this->metaKeyId++;

        return $this;
    }
}
