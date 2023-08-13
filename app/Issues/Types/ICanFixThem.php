<?php

namespace App\Issues\Types;


use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\OutputInterface;

trait ICanFixThem
{
    use InteractsWithIO;

    public abstract function fix(): bool;

    public function setOutput(?OutputInterface $output): void
    {
        $this->output = $output;
    }

    protected function selectMember(): ?MemberData
    {
        /** @var IssueData $issueData */
        $issueData = app(IssueData::class);
        $memberNames = $issueData->members()
            ->mapWithKeys(fn($m) => ["$m->first_name $m->last_name" => $m]);  // TODO What happens when we have duplicate names

        do {
            $choice = $this->anticipate("Select customer/member", function ($input) use ($memberNames) {
                if (length($input) == 0) {
                    return [];
                }

                /**
                 * The closer a member's name is to the input text, given the similar_text distance, the more likely we are
                 * to want to use it. Take the top 5 closest ones and return those as options to auto complete.
                 */
                $values = $memberNames
                    ->keys()
                    ->map(function ($n) use ($input) {
                        similar_text($input, $n, $percent);
                        return [$n, $percent];
                    })
                    ->sortBy(fn($arr) => $arr[1], SORT_REGULAR, true)
                    ->take(5)
                    ->map(fn($arr) => $arr[0])
                    ->toArray();
//                Log::info(print_r($values, true));
                return $values;
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
}
