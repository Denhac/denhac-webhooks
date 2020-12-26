<?php

namespace App\Console\Commands;

use App\ActiveCardHolderUpdate;
use App\Card;
use App\FeatureFlags;
use App\Google\GmailEmailHelper;
use App\Google\GoogleApi;
use App\PaypalBasedMember;
use App\Slack\SlackApi;
use App\Subscription;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use YlsIdeas\FeatureFlags\Facades\Features;

class IdentifyIssues extends Command
{
    const ISSUE_WITH_A_CARD = 'Issue with a card';
    const ISSUE_SLACK_ACCOUNT = 'Issue with a Slack account';
    const ISSUE_GOOGLE_GROUPS = 'Issue with google groups';
    const ISSUE_INTERNAL_CONSISTENCY = 'Issue with our store\'s internal consistency';

    const SYSTEM_WOOCOMMERCE = 'WooCommerce';
    const SYSTEM_PAYPAL = 'PayPal';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:identify-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies issues with membership and access';

    /**
     * @var MessageBag
     */
    private $issues;
    /**
     * @var WooCommerceApi
     */
    private $wooCommerceApi;
    /**
     * @var SlackApi
     */
    private $slackApi;
    /**
     * @var GoogleApi
     */
    private $googleApi;

    /**
     * Create a new command instance.
     *
     * @param WooCommerceApi $wooCommerceApi
     * @param SlackApi $slackApi
     * @param GoogleApi $googleApi
     */
    public function __construct(WooCommerceApi $wooCommerceApi,
                                SlackApi $slackApi,
                                GoogleApi $googleApi)
    {
        parent::__construct();

        $this->issues = new MessageBag();
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
        $this->googleApi = $googleApi;
    }

    /**
     * Execute the console command.
     *
     * @throws ApiCallFailed
     */
    public function handle()
    {
        $this->info('Identifying issues');
        $members = $this->getMembers();
        $this->unknownActiveCard($members);
        $this->extraSlackUsers($members);
        $this->missingSlackUsers($members);
        $this->googleGroupIssues($members);
        $this->internalConsistencyCardIssues($members);
        $this->internalConsistencySubscriptionIssues();

        $this->printIssues();
    }

    private function printIssues()
    {
        $this->info("There are {$this->issues->count()} total issues.");
        $this->info('');
        collect($this->issues->keys())
            ->each(function ($key) {
                $knownIssues = collect($this->issues->get($key));
                $this->info("$key ({$knownIssues->count()})");

                $knownIssues
                    ->map(function ($issue) {
                        $this->info(">>> $issue");
                    });
                $this->info('');
            });
    }

    /**
     * @throws ApiCallFailed
     */
    private function getMembers()
    {
        $customers = $this->wooCommerceApi->customers->list();

        $subscriptions = $this->wooCommerceApi->subscriptions->list();

        $members = $customers->map(function ($customer) use ($subscriptions) {
            $isMember = $subscriptions
                ->where('customer_id', $customer['id'])
                ->whereIn('status', ['active', 'pending-cancel'])
                ->isNotEmpty();

            $meta_data = collect($customer['meta_data']);
            $card_string = $meta_data->where('key', 'access_card_number')->first()['value'] ?? null;
            $cards = is_null($card_string) ? collect() : collect(explode(',', $card_string))
                ->map(function ($card) {
                    return ltrim($card, '0');
                });

            $emails = collect();
            if (!is_null($customer['email'])) {
                $emails->push(GmailEmailHelper::handleGmail(Str::lower($customer['email'])));
            }

            $email_aliases_string = $meta_data->where('key', 'email_aliases')->first()['value'] ?? null;
            $email_aliases = is_null($email_aliases_string) ? collect() : collect(explode(',', $email_aliases_string));
            $emails = $emails->merge($email_aliases);

            $subscriptionMap = $subscriptions
                ->where('customer_id', $customer['id'])
                ->map(function ($subscription) {
                    return $subscription['status'];
                });

            return [
                'id' => $customer['id'],
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'email' => $emails,
                'is_member' => $isMember,
                'subscriptions' => $subscriptionMap,
                'cards' => $cards,
                'slack_id' => $meta_data->where('key', 'access_slack_id')->first()['value'] ?? null,
                'system' => self::SYSTEM_WOOCOMMERCE,
            ];
        });

        $members = $members->concat(PaypalBasedMember::all()
            ->map(function ($member) {
                $emails = collect();
                if (!is_null($member->email)) {
                    $emails->push(GmailEmailHelper::handleGmail(Str::lower($member->email)));
                }

                return [
                    'id' => $member->paypal_id,
                    'first_name' => $member->first_name,
                    'last_name' => $member->last_name,
                    'email' => $emails,
                    'is_member' => $member->active,
                    'subscriptions' => collect(),
                    'cards' => is_null($member->card) ? collect() : collect([$member->card]),
                    'slack_id' => $member->slack_id,
                    'system' => self::SYSTEM_PAYPAL,
                ];
            }));

        return $members;
    }

    /**
     * Identify any issues where there is an active card listed for someone, but we have no record of them being an
     * active member.
     * @param $members
     */
    private function unknownActiveCard(Collection $members)
    {
        /** @var ActiveCardHolderUpdate $activeCardHolderUpdate */
        $activeCardHolderUpdate = ActiveCardHolderUpdate::latest()->first();
        if (is_null($activeCardHolderUpdate)) {
            return;
        }

        $card_holders = collect($activeCardHolderUpdate->card_holders);
        $card_holders
            ->each(function ($card_holder) use ($members) {
                $membersWithCard = $members
                    ->filter(function ($member) use ($card_holder) {
                        return $member['cards']->contains(ltrim($card_holder['card_num'], '0'));
                    });

                if ($membersWithCard->count() == 0) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but I have no membership record of them with that card.";
                    $this->issues->add(self::ISSUE_WITH_A_CARD, $message);

                    return;
                }

                if ($membersWithCard->count() > 1) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is connected to multiple accounts.";
                    $this->issues->add(self::ISSUE_WITH_A_CARD, $message);

                    return;
                }

                $member = $membersWithCard->first();

                if ($card_holder['first_name'] != $member['first_name'] ||
                    $card_holder['last_name'] != $member['last_name']) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is listed as {$member['first_name']} {$member['last_name']} in our records.";
                    $this->issues->add(self::ISSUE_WITH_A_CARD, $message);
                }

                if (! $member['is_member']) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is not currently a member.";
                    $this->issues->add(self::ISSUE_WITH_A_CARD, $message);
                }
            });

        $members
            ->filter(function ($member) {
                return ! is_null($member['first_name']) &&
                    ! is_null($member['last_name']) &&
                    $member['is_member'];
            })
            ->each(function ($member) use ($card_holders) {
                $member['cards']->each(function ($card) use ($member, $card_holders) {
                    $cardActive = $card_holders->contains('card_num', $card);
                    if (! $cardActive) {
                        $message = "{$member['first_name']} {$member['last_name']} has the card $card but it doesn't appear to be active";
                        $this->issues->add(self::ISSUE_WITH_A_CARD, $message);
                    }
                });
            });
    }

    private function extraSlackUsers(Collection $members)
    {
        $slackUsers = $this->slackApi->users_list()
            ->filter(function ($user) {
                if (array_key_exists('is_bot', $user) && $user['is_bot']) {
                    return false;
                }

                if (
                    $user['id'] == 'UNEA0SKK3' || // slack-api
                    $user['id'] == 'USLACKBOT' // slackbot
                ) {
                    return false;
                }

                return true;
            });

        $slackUsers
            ->each(function ($user) use ($members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($membersForSlackId->count() == 0) {
                    if ($this->isFullSlackUser($user) &&
                        ! Features::accessible(FeatureFlags::IGNORE_UNIDENTIFIABLE_MEMBERSHIP)) {
                        $message = "{$user['name']} with slack id ({$user['id']}) is a full user in slack but I have no membership record of them.";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                    }

                    return;
                }

                $member = $membersForSlackId->first();

                if ($member['is_member']) {
                    if (array_key_exists('is_invited_user', $user) && $user['is_invited_user']) {
                        return; // Do nothing, we've sent the invite and that's all we can do.
                    }

                    if (array_key_exists('deleted', $user) && $user['deleted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is deleted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                        return;
                    }

                    if (array_key_exists('is_restricted', $user) && $user['is_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is restricted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                        return;
                    }
                    if (array_key_exists('is_ultra_restricted', $user) && $user['is_ultra_restricted']) {
                        $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is ultra restricted, but they are a member";
                        $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);

                        return;
                    }
                } elseif ($this->isFullSlackUser($user) &&
                    ! Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
                    $message = "{$member['first_name']} {$member['last_name']} with slack id ({$user['id']}) is not an active member but they have a full slack account.";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                }
            });
    }

    private function isFullSlackUser($slackUser)
    {
        if (
            (array_key_exists('deleted', $slackUser) && $slackUser['deleted']) ||
            (array_key_exists('is_restricted', $slackUser) && $slackUser['is_restricted']) ||
            (array_key_exists('is_ultra_restricted', $slackUser) && $slackUser['is_ultra_restricted'])
        ) {
            return false;
        }

        return true;
    }

    private function missingSlackUsers(Collection $members)
    {
        $slackUsers = $this->slackApi->users_list();

        $members
            ->each(function ($member) use ($slackUsers) {
                if (! Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {
                    if (! $member['is_member']) {
                        return;
                    }
                } else {
                    if (! $member['subscriptions']->contains('need-id-check')) {
                        return;
                    }
                }

                $slackForMember = $slackUsers
                    ->filter(function ($user) use ($member) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($slackForMember->count() == 0) {
                    $message = "{$member['first_name']} {$member['last_name']} ({$member['id']}) doesn't appear to have a slack account";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                }
            });
    }

    private function googleGroupIssues(Collection $members)
    {
        $groups = $this->googleApi->groupsForDomain('denhac.org')
            ->filter(function ($group) {
                // TODO handle excluded groups in a better way
                return $group != 'denhac@denhac.org' &&
                    $group != 'lpfmerrors@denhac.org';
            });

        $emailsToGroups = collect();

        $groups->each(function ($group) use (&$emailsToGroups) {
            $membersInGroup = $this->googleApi->group($group)->list();

            $membersInGroup->each(function ($groupMember) use ($group, &$emailsToGroups) {
                $groupMember = GmailEmailHelper::handleGmail(Str::lower($groupMember));
                $groupsForEmail = $emailsToGroups->get($groupMember, collect());
                $groupsForEmail->add($group);
                $emailsToGroups->put($groupMember, $groupsForEmail);
            });
        });

        $emailsToGroups->each(function ($groupsForEmail, $email) use ($groups, $members) {
            /** @var Collection $groupsForEmail */

            // Ignore groups of ours that are part of another group
            if ($groups->contains($email)) {
                return;
            }

            $membersForEmail = $members
                ->filter(function ($member) use ($email) {
                    /** @var Collection $memberEmails */
                    $memberEmails = $member['email'];

                    return $memberEmails->contains(Str::lower($email));
                });

            if ($membersForEmail->count() > 1) {
                $message = "More than 2 members exist for email address $email";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);

                return;
            }

            if ($membersForEmail->count() == 0) {
                if (Features::accessible(FeatureFlags::IGNORE_UNIDENTIFIABLE_MEMBERSHIP)) {
                    return;
                }

                $message = "No member found for email address $email in groups: {$groupsForEmail->implode(', ')}";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);

                return;
            }

            $member = $membersForEmail->first();

            if (! $member['is_member'] &&
                ! Features::accessible(FeatureFlags::KEEP_MEMBERS_IN_SLACK_AND_EMAIL)) {
                $message = "{$member['first_name']} {$member['last_name']} with email ($email) is not an active member but is in groups: {$groupsForEmail->implode(', ')}";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);
            }
        });

        $members->each(function ($member) use ($emailsToGroups) {
            /** @var Collection $memberEmails */
            $memberEmails = $member['email'];

            $membersGroupMailing = 'members@denhac.org'; // TODO dedupe this

            if ($memberEmails->isEmpty()) {
                return;
            }

            if (! $member['is_member']) {
                return;
            }

            $membersGroupMailing = 'members@denhac.org'; // TODO dedupe this

            $memberHasEmailInMembersList = $memberEmails
                ->filter(function ($memberEmail) use ($emailsToGroups, $membersGroupMailing) {
                    return $emailsToGroups->has($memberEmail) &&
                        $emailsToGroups->get($memberEmail)->contains($membersGroupMailing);
                })
                ->isNotEmpty();

            if ($memberHasEmailInMembersList) {
                return;
            }

            if ($member['is_member']) {
                $message = "{$member['first_name']} {$member['last_name']} with email ({$memberEmails->implode(', ')}) is an active member but is not part of $membersGroupMailing";
                $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);
            } elseif (Features::accessible(FeatureFlags::NEED_ID_CHECK_GETS_ADDED_TO_SLACK_AND_EMAIL)) {
                if ($member['subscriptions']->contains('need-id-check')) {
                    $message = "{$member['first_name']} {$member['last_name']} with email ({$memberEmails->implode(', ')}) needs an id check but is not part of $membersGroupMailing";
                    $this->issues->add(self::ISSUE_GOOGLE_GROUPS, $message);
                }
            }
        });
    }

    private function internalConsistencyCardIssues(Collection $members)
    {
        $cards = Card::all();

        $members->each(function ($member) use ($cards) {
            if ($member['system'] == self::SYSTEM_PAYPAL) {
                // We don't update the cards database for paypal members
                return;
            }

            $cardsForMember = $cards
                ->where('woo_customer_id', $member['id']);

            // $member['cards'] is the list of cards in WooCommerce
            $member['cards']->each(function ($memberCard) use ($member, $cardsForMember) {
                if (! $cardsForMember->contains('number', $memberCard)) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} but it's not listed in our database";
                    $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);

                    return;
                }

                /** @var Card $card */
                $card = $cardsForMember->where('number', $memberCard)->first();

                if ($member['is_member'] && ! $card->active) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} listed in ".
                        "their account but we think it's NOT active";
                    $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
                }

                if (! $member['is_member'] && $card->active) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} listed in ".
                        "their account but we think it's active";
                    $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
                }
            });

            $cardsForMember->each(function ($cardForMember) use ($member) {
                /** @var Card $cardForMember */
                if (! $member['cards']->contains($cardForMember->number) && $cardForMember->active) {
                    $message = "{$member['first_name']} {$member['last_name']} doesn't have {$cardForMember->number} ".
                        "listed in their profile, but we think it's active";
                    $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
                }

                if (! $member['cards']->contains($cardForMember->number) && $cardForMember->member_has_card) {
                    $message = "{$member['first_name']} {$member['last_name']} doesn't have {$cardForMember->number} ".
                        'listed in their profile, but we think they still have it';
                    $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
                }
            });
        });

        $cards
            ->groupBy(function ($card) {
                return $card->number;
            })
            ->filter(function ($value) {
                return $value->count() > 1;
            })
            ->each(function ($cards, $cardNum) {
                $uniqueCustomers = $cards
                    ->map(function ($card) {
                        return $card->woo_customer_id;
                    })
                    ->unique()
                    ->implode(', ');
                $numEntries = $cards->count();

                $message = "$cardNum has $numEntries entries in the database for customer(s): $uniqueCustomers";
                $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
            });
    }

    private function internalConsistencySubscriptionIssues()
    {
        $subscriptions_api = $this->wooCommerceApi->subscriptions->list();

        $subscriptions_api->each(function ($subscription_api) {
            $sub_id = $subscription_api['id'];
            $sub_status = $subscription_api['status'];

            $model = Subscription::whereWooId($sub_id)->first();

            if (is_null($model)) {
                $message = "Subscription $sub_id doesn't exist in our local database";
                $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);

                return;
            }

            if ($model->status != $sub_status) {
                $message = "Subscription $sub_id has api status $sub_status but local status {$model->status}";
                $this->issues->add(self::ISSUE_INTERNAL_CONSISTENCY, $message);
            }
        });
    }
}
