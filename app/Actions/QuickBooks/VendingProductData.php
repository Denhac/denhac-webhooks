<?php

namespace App\Actions\QuickBooks;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class VendingProductData extends Data
{
    protected bool $isVendingOrder = false;

    public function __construct(
        public Collection $productData,
    ) {
        foreach ($this->productData['categories'] as $category) {
            if ($category['id'] == 66) {
                $this->isVendingOrder = true;
            }
        }
    }

    public function isVendingOrder(): bool
    {
        return $this->isVendingOrder;
    }
}
