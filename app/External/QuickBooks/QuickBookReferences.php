<?php

namespace App\External\QuickBooks;


use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Data\IPPReferenceType;

/**
 * @property IPPReferenceType vendingAdjustmentAccountFrom
 * @property IPPReferenceType vendingAdjustmentAccountTo
 * @property IPPReferenceType vendingPoolClass
 */
class QuickBookReferences
{
    protected Collection $cache;

    public function __construct()
    {
        $this->cache = collect();
    }

    /**
     * This function helps categorize reference values for setting storage. For example, given vendingAdjustmentAccountFrom,
     * this function will output "quickbooks.references.vending.adjustmentAccountFrom". Categories can be updated via
     * updating the knownPrefixes array bellow. You can use either a single value or you can use a key => value to
     * customize what the replacement looks like.
     *
     * @param string $name The property name to read or write
     * @return string the setting we're going to access
     */
    public function getSettingKey(string $name): string
    {
        $knownPrefixes = [
            "vending",
        ];

        foreach ($knownPrefixes as $prefix => $replacement) {
            if (!is_string($prefix)) {
                $prefix = $replacement;
            }

            if (!Str::startsWith($name, $prefix)) {
                continue;
            }

            $name = Str::camel(substr($name, strlen($prefix)));

            return "quickbooks.references.$replacement.$name";
        }

        $name = Str::camel($name);
        return "quickbook.references.other.$name";
    }

    public function __get(string $name): ?IPPReferenceType
    {
        if ($this->cache->contains($name)) {
            return $this->cache->get($name);
        }

        $settingKey = $this->getSettingKey($name);
        $settingValue = setting($settingKey);

        if (is_null($settingValue)) {
            return null;
        }

        $referenceType = new IPPReferenceType([
            'value' => $settingValue,
        ]);

        $this->cache->put($name, $referenceType);

        return $referenceType;
    }

    public function __set(string $name, IPPReferenceType|string|int $value): void
    {
        if ($this->cache->contains($name)) {
            $this->cache->offsetUnset($name);
        }

        $settingKey = $this->getSettingKey($name);

        if($value instanceof IPPReferenceType) {
            $value = $value->value;
        }

        setting([$settingKey => $value])->save();
    }
}
