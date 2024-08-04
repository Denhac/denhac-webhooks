<?php

namespace App\Issues\Types\AccessCards;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class ActiveCardMultipleAccounts extends IssueBase
{
    use ICanFixThem;

    private $cardHolder;
    private Collection $membersWithCard;

    public function __construct($cardHolder, $membersWithCard)
    {
        $this->cardHolder = $cardHolder;
        $this->membersWithCard = $membersWithCard;
    }

    public static function getIssueNumber(): int
    {
        return 1;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Access Cards: Active card multiple accounts';
    }

    public function getIssueText(): string
    {
        $otherAccounts = $this->membersWithCard->implode(fn($md) => "$md->first_name $md->last_name ($md->id)", ",");
        return "{$this->cardHolder['first_name']} {$this->cardHolder['last_name']} has the active card ({$this->cardHolder['card_num']}) but is connected to multiple accounts: $otherAccounts";
    }

    public function fix(): bool
    {
        $choiceHelper = $this->issueFixChoice();

        foreach ($this->membersWithCard as $memberData) {
            /** @var MemberData $memberData */
            $choiceHelper->option(
                "Keep {$this->cardHolder['card_num']} ONLY for customer $memberData->first_name, $memberData->last_name ($memberData->id) Member: {$memberData->isMember}",
                fn() => $this->keepOnlyCardHolder($memberData)
            );
        }

        return $choiceHelper->run();
    }

    private function keepOnlyCardHolder(MemberData $winnerMemberData)
    {
        $cardNum = $this->cardHolder['card_num'];

        /** @var WooCommerceApi $wooCommerceApi */
        $wooCommerceApi = app(WooCommerceApi::class);

        foreach ($this->membersWithCard as $memberData) {
            /** @var MemberData $memberData */
            if ($memberData->id == $winnerMemberData->id) {
                continue; // They're keeping the card, nothing to do for them.
            }

            $memberData->cards->forget($cardNum);

            $wooCommerceApi->customers
                ->update($memberData->id, [
                    'meta_data' => [
                        [
                            'key' => 'access_card_number',
                            'value' => $memberData->cards->implode(','),
                        ],
                    ],
                ]);
        }
    }
}
