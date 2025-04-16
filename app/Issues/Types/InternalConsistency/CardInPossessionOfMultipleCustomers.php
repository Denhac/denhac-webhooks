<?php

namespace App\Issues\Types\InternalConsistency;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Fixing\Preamble;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Models\Card;
use Illuminate\Support\Collection;
use function Laravel\Prompts\info;

class CardInPossessionOfMultipleCustomers extends IssueBase
{
    use ICanFixThem;

    public $cardNum;

    private $numEntries;

    public Collection $uniqueCustomers;

    public function __construct($cardNum, $numEntries, Collection $uniqueCustomers)
    {
        $this->cardNum = $cardNum;
        $this->numEntries = $numEntries;
        $this->uniqueCustomers = $uniqueCustomers;
    }

    public static function getIssueNumber(): int
    {
        return 211;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Card in possession of multiple customers';
    }

    public function getIssueText(): string
    {
        return "Card $this->cardNum has $this->numEntries entries in the database for customer IDs: {$this->uniqueCustomers->implode(', ')}";
    }

    public function fix(): bool
    {
        $options = $this->issueFixChoice();
        $options->preamble(new class($this) extends Preamble {
            public function __construct(private readonly CardInPossessionOfMultipleCustomers $outer)
            {
            }

            public function preamble(): void
            {
                info('Usually, this will happen if a customer is deleted or if a card got re-used. If');
                info('one of the customers is deleted, the other customer almost definitely has the');
                info('card. The same thing is true if one of the customers is active and the other');
                info("isn't. If they both appear active and both members have the card in their");
                info('profile, then you unfortunately need to go track down who has the card.');
                info("");
                info('By selecting one here, we will remove the card from the profiles of any non-deleted customer');

                foreach ($this->outer->uniqueCustomers as $customerId) {
                    info("");

                    info("Customer ID: $customerId");
                    $member = MemberData::byID($customerId);

                    if (is_null($member)) {
                        info('Customer Deleted');

                        continue;
                    }

                    if ($member->cards->contains($this->outer->cardNum)) {
                        info("Card is in customer's profile");
                    } else {
                        info("Card is NOT  in customer's profile");
                    }

                    if ($member->isMember) {
                        info('They are an active member');
                    } else {
                        info('They are NOT an active member');
                    }

                    /** @var Card $card */
                    $card = Card::where('number', $this->outer->cardNum)->where('customer_id', $customerId)->first();
                    if ($card->active) {
                        info('We think the card is currently active');
                    } else {
                        info('We do NOT think the card is currently active');
                    }
                }
            }
        });

        foreach ($this->uniqueCustomers as $customerId) {
            $options->option("Give the card {$this->cardNum} to customer id $customerId", fn () => $this->assignCardTo($customerId));
        }

        return $options->run();
    }

    private function assignCardTo(int $winnerCustomerId): bool
    {
        /** @var WooCommerceApi $wooCommerceApi */
        $wooCommerceApi = app(WooCommerceApi::class);

        foreach ($this->uniqueCustomers as $customerId) {
            if ($winnerCustomerId == $customerId) {
                continue;  // They get to keep the card so we do nothing.
            }

            $member = MemberData::byID($customerId);

            if (is_null($member)) {
                continue;  // Deleted customer, we can't update them
            }

            $member->cards->forget($this->cardNum);

            $wooCommerceApi->customers
                ->update($customerId, [
                    'meta_data' => [
                        [
                            'key' => 'access_card_number',
                            'value' => $member->cards->implode(','),
                        ],
                    ],
                ]);
        }

        return true;
    }
}
