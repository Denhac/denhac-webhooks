<?php

namespace App\Issues\Checkers\AccessCards;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\AccessCards\ActiveCardMultipleAccounts;
use App\Issues\Types\AccessCards\ActiveCardNoRecord;
use App\Issues\Types\AccessCards\CardHolderIncorrectName;
use App\Issues\Types\AccessCards\MemberCardIsNotActive;
use App\Issues\Types\AccessCards\NonMemberHasActiveCard;
use App\Issues\Types\AccessCards\UnknownActiveCardForMember;
use App\Models\ActiveCardHolderUpdate;
use App\Models\Card;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActiveCardIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
    ) {}

    public function generateIssues(): void
    {
        /** @var Collection<MemberData> $members */
        $members = $this->aggregateCustomerData->get();

        /** @var ActiveCardHolderUpdate $activeCardHolderUpdate */
        $activeCardHolderUpdate = ActiveCardHolderUpdate::latest()->first();
        if (is_null($activeCardHolderUpdate)) {
            return;  // TODO Issue for no active card holder update
        }

        $card_holders = collect($activeCardHolderUpdate->card_holders);
        $card_holders
            ->each(function ($card_holder) use ($members) {
                $membersWithCard = $members
                    ->filter(function ($member) use ($card_holder) {
                        /** @var MemberData $member */
                        return $member->cards->contains(ltrim($card_holder['card_num'], '0'));
                    });

                if ($membersWithCard->count() == 0) {
                    // Okay so we don't have this card in anyone's profile. Let's see if we can't find it via uuid.

                    if (array_key_exists('udf_id', $card_holder)) {
                        /** @var MemberData $member */
                        $member = $members->filter(fn ($m) => $m->uuid == $card_holder['udf_id'])->first();

                        if (! is_null($member)) {
                            if ($this->isCardNumUpdatedInLastDay($member->id, $card_holder['card_num'])) {
                                return;  // Update has already gone through probably
                            } elseif ($member->isMember) {
                                $this->issues->add(new UnknownActiveCardForMember($member, $card_holder['card_num']));
                            } else {
                                $this->issues->add(new NonMemberHasActiveCard($member, $card_holder['card_num']));
                            }

                            return;
                        }
                    }
                    // If we couldn't find the member, we have no record of who this person is. Needs to be handled manually.
                    $this->issues->add(new ActiveCardNoRecord($card_holder));

                    return;
                }

                if ($membersWithCard->count() > 1) {
                    $this->issues->add(new ActiveCardMultipleAccounts($card_holder, $membersWithCard));

                    return;
                }

                /** @var MemberData $member */
                $member = $membersWithCard->first();

                if (! $this->namesAreEqualEnough($card_holder['first_name'], $member->first_name) ||
                    ! $this->namesAreEqualEnough($card_holder['last_name'], $member->last_name)) {
                    $this->issues->add(new CardHolderIncorrectName($member, $card_holder));
                }

                if (! $member->isMember && ! $this->isCardNumUpdatedInLastDay($member->id, $card_holder['card_num'])) {
                    $this->issues->add(new NonMemberHasActiveCard($member, $card_holder['card_num']));
                }
            });

        $members
            ->filter(function ($member) {
                /** @var MemberData $member */
                return ! is_null($member->first_name) &&
                    ! is_null($member->last_name) &&
                    $member->isMember &&
                    $member->hasSignedWaiver;
            })
            ->each(function ($member) use ($card_holders) {
                /** @var MemberData $member */
                $member->cards->each(function ($card) use ($member, $card_holders) {
                    $cardActive = $card_holders->contains('card_num', $card);
                    if (! $cardActive && ! $this->isCardNumUpdatedInLastDay($member->id, $card)) {
                        $this->issues->add(new MemberCardIsNotActive($member, $card));
                    }
                });
            });
    }

    protected function isCardNumUpdatedInLastDay($memberId, $cardNum): bool
    {
        // We get card updates every 8 hours. We only want to report on this if a card hasn't been updated in the last day.
        /** @var Card $card */
        $card = Card::where('number', $cardNum)->where('customer_id', $memberId)->first();

        return ! is_null($card) && $card->updated_at >= Carbon::now()->subDay();
    }

    /**
     * Compares two names, allowing slight differences like how tick marks are represented in the person's name.
     * Sometimes the card access server handles ` and ' weirdly, for example. If that's the only difference in the two
     * names, then we should consider everything fine.
     *
     * @param  string  $left  One of the two names to check
     * @param  string  $right  One of the two names to check
     */
    protected function namesAreEqualEnough(string $left, string $right): bool
    {
        // $from and $to are index based. i.e. index 0 in $from is replaced with index 0 of $to.
        $from = ['`'];
        $to = ['\''];

        $left = str_replace($from, $to, $left);
        $right = str_replace($from, $to, $right);

        return $left == $right;
    }
}
