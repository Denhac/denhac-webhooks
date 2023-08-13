<?php

namespace App\Issues\Types\Stripe;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Stripe\Issuing\Cardholder;
use Stripe\StripeClient;

class NoMemberForCardHolder extends IssueBase
{
    use ICanFixThem;

    private Cardholder $cardHolder;

    public function __construct(Cardholder $cardHolder)
    {
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 500;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Stripe: No member for card holder";
    }

    public function getIssueText(): string
    {
        $id = $this->cardHolder->id;
        $name = $this->cardHolder->name;
        return "CardHolder for \"$name\" ($id) is not assigned to any member";
    }

    public function fix(): bool
    {
        $MATCH_MEMBER = "Match Member";
        $DEACTIVATE_CARD_HOLDER = "Deactivate Card Holder";
        $CANCEL = "Cancel";
        $choice = $this->choice("How do you want to fix this issue?", [$MATCH_MEMBER, $DEACTIVATE_CARD_HOLDER, $CANCEL]);

        if ($choice == $MATCH_MEMBER) {
            /** @var MemberData $member */
            $member = $this->selectMember();
            if(is_null($member)) {
                $this->info("No member selected. Aborting issue fix.");
                return false;
            }

            $wooCommerceApi = app(WooCommerceApi::class);

            $wooCommerceApi->customers
                ->update($member->id, [
                    'meta_data' => [
                        [
                            'key' => 'stripe_card_holder_id',
                            'value' => $this->cardHolder->id,
                        ],
                    ],
                ]);

            return true;
        } else if ($choice == $DEACTIVATE_CARD_HOLDER) {
            /** @var StripeClient $stripeClient */
            $this->cardHolder->status = "inactive";
            $this->cardHolder->save();
            return true;
        }

        return false;
    }
}
