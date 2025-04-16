<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Fixing\Preamble;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\AccessCards\CardSentForDeactivation;
use function Laravel\Prompts\info;

class ActiveCardNotInCustomerProfile extends IssueBase
{
    use ICanFixThem;

    public MemberData $member;

    private $cardNumber;

    public function __construct(MemberData $member, $cardNumber)
    {
        $this->member = $member;
        $this->cardNumber = $cardNumber;
    }

    public static function getIssueNumber(): int
    {
        return 209;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Active card not in customer profile';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} doesn't have {$this->cardNumber} " .
            "listed in their profile, but we think it's active";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->preamble(new class($this) extends Preamble {
                public function __construct(public ActiveCardNotInCustomerProfile $outer)
                {
                }

                public function preamble(): void
                {
                    info("This issue requires some communication with the customer. There's a chance that");
                    info('because this card is active, that they are using this card. If we deactivate the');
                    info("card and they're using it, we could mess up their access to the space. If we add");
                    info("it to their profile, but they don't have it, we're creating a possible security");
                    info('issue. You can either email them or try to find the last badge in use to decide');
                    info('on the correct coarse of action.');
                    info("");
                    info("For non-members, it's easy enough to just revoke this card and issue a new one.");
                    info('It is also abnormal for a member to have or use more than one card.');
                    info("");

                    info("Here's what we know:");

                    if ($this->outer->member->isMember) {
                        info(' - They are a member');
                    } else {
                        info(' - They are NOT a member');
                    }
                    info(" - Cards in their profile: {$this->outer->member->cards->implode(', ')}");

                    info("");
                }
            })
            ->option('Add card to member profile', fn() => $this->addCardToMemberProfile())
            ->option('Deactivate card', fn() => $this->deactivateCard())
            ->run();
    }

    private function addCardToMemberProfile(): bool
    {
        $wooCommerceApi = app(WooCommerceApi::class);

        $this->member->cards->add($this->cardNumber);

        $wooCommerceApi->customers
            ->update($this->member->id, [
                'meta_data' => [
                    [
                        'key' => 'access_card_number',
                        'value' => $this->member->cards->implode(','),
                    ],
                ],
            ]);

        return true;
    }

    private function deactivateCard(): bool
    {
        MembershipAggregate::make($this->member->id)
            ->recordThat(new CardSentForDeactivation($this->member->id, $this->cardNumber))
            ->persist();

        return true;
    }
}
