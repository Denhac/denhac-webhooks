<?php

namespace App\Issues\Types;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\ChoiceHelper;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;

trait ICanFixThem
{
    abstract public function fix(): bool;

    protected function selectMember(): ?MemberData
    {
        /** @var AggregateCustomerData $aggregateCustomerData */
        $aggregateCustomerData = app(AggregateCustomerData::class);
        $memberNames = $aggregateCustomerData->get()
            ->mapWithKeys(fn ($m) => [$m => "$m->first_name $m->last_name"]);

        do {
            $choice = search(
                label: 'Select customer/member',
                options: $memberNames,
                required: false,
            );

            if (empty($choice)) {
                break;  // They decided to exit without entering anything
            }

            if ($memberNames->has($choice)) {
                break;  // They selected a valid member name
            }

            info("$choice is not a valid option. Please select a member or enter no text to cancel.");
        } while (true);

        if (empty($choice)) {
            return null;
        }

        return $memberNames->get($choice);
    }

    protected function issueFixChoice($text = 'How do you want to fix this issue?'): ChoiceHelper
    {
        return new ChoiceHelper($text);
    }
}
