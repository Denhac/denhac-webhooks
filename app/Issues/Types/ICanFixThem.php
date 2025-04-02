<?php

namespace App\Issues\Types;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\ChoiceHelper;
use Illuminate\Console\Concerns\InteractsWithIO;

trait ICanFixThem
{
    use InteractsWithIO;

    abstract public function fix(): bool;

    protected function selectMember(): ?MemberData
    {
        /** @var AggregateCustomerData $aggregateCustomerData */
        $aggregateCustomerData = app(AggregateCustomerData::class);
        $memberNames = $aggregateCustomerData->get()
            ->mapWithKeys(fn ($m) => ["$m->first_name $m->last_name" => $m]);

        do {
            $choice = $this->anticipate('Select customer/member', function ($input) use ($memberNames) {
                if (empty($input)) {
                    return [];
                }

                /**
                 * The closer a member's name is to the input text, given the similar_text distance, the more likely we are
                 * to want to use it. Take the top 5 closest ones and return those as options to auto complete.
                 */
                return $memberNames
                    ->keys()
                    ->map(function ($n) use ($input) {
                        similar_text($input, $n, $percent);

                        return [$n, $percent];
                    })
                    ->sortBy(fn ($arr) => $arr[1], SORT_REGULAR, true)
                    ->take(5)
                    ->map(fn ($arr) => $arr[0])
                    ->toArray();
            });

            if (empty($choice)) {
                break;  // They decided to exit without entering anything
            }

            if ($memberNames->has($choice)) {
                break;  // They selected a valid member name
            }

            $this->info("$choice is not a valid option. Please select a member or enter no text to cancel.");
        } while (true);

        if (empty($choice)) {
            return null;
        }

        return $memberNames->get($choice);
    }

    protected function issueFixChoice($text = 'How do you want to fix this issue?'): ChoiceHelper
    {
        return new ChoiceHelper($this->output, $text);
    }
}
