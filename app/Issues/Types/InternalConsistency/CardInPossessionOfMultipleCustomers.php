<?php

namespace App\Issues\Types\InternalConsistency;

use App\Models\Card;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class CardInPossessionOfMultipleCustomers extends IssueBase
{
    use ICanFixThem;

    private $cardNum;

    private $numEntries;

    private Collection $uniqueCustomers;

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
        $this->line('Usually, this will happen if a customer is deleted or if a card got re-used. If');
        $this->line('one of the customers is deleted, the other customer almost definitely has the');
        $this->line('card. The same thing is true if one of the customers is active and the other');
        $this->line("isn't. If they both appear active and both members have the card in their");
        $this->line('profile, then you unfortunately need to go track down who has the card.');
        $this->newLine();
        $this->line('By selecting one here, we will remove the card from the profiles of any non-deleted customer');

        $options = $this->issueFixChoice();

        foreach ($this->uniqueCustomers as $customerId) {
            $this->newLine();

            $this->line("Customer ID: $customerId");
            $member = $this->memberDataById($customerId);

            if (is_null($member)) {
                $this->line('Customer Deleted');

                continue;
            }

            if ($member->cards->contains($this->cardNum)) {
                $this->line("Card is in customer's profile");
            } else {
                $this->line("Card is NOT  in customer's profile");
            }

            if ($member->isMember) {
                $this->line('They are an active member');
            } else {
                $this->line('They are NOT an active member');
            }

            /** @var Card $card */
            $card = Card::where('number', $this->cardNum)->where('woo_customer_id', $customerId)->first();
            if ($card->active) {
                $this->line('We think the card is currently active');
            } else {
                $this->line('We do NOT think the card is currently active');
            }

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

            $member = $this->memberDataById($customerId);

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
