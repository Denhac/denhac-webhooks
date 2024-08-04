<?php

namespace App\Issues\Types\GoogleGroups;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class TwoMembersSameEmail extends IssueBase
{
    use ICanFixThem;

    private string $email;

    private Collection $membersForEmail;

    public function __construct(string $email, Collection $membersForEmail)
    {
        $this->email = $email;
        $this->membersForEmail = $membersForEmail;
    }

    public static function getIssueNumber(): int
    {
        return 101;
    }

    public static function getIssueTitle(): string
    {
        return 'Google Groups: Two members have the same email';
    }

    public function getIssueText(): string
    {
        return "More than 1 member exists for email address $this->email: {$this->membersForEmail->implode(', ')}";
    }

    public function fix(): bool
    {
        $options = $this->issueFixChoice();

        $needToUpdate = collect($this->membersForEmail);

        while ($needToUpdate->count() > 1) {
            /** @var MemberData $member */
            foreach ($needToUpdate as $member) {
                $name = "$member->first_name $member->last_name";
                if ($this->email == $member->primaryEmail) {
                    // We can't remove the primary email, only update it to something else
                    $options->option("Update primary email for $name", fn () => $this->updatePrimaryEmail($member, $needToUpdate));
                } else {
                    // We only offer to remove secondary emails
                    $options->option("Remove secondary email for $name", fn () => $this->removeSecondaryEmail($member, $needToUpdate));
                }
            }

            $runResult = $options->run();
            if (! $runResult) {  // Cancelled
                return false;
            }
        }

        return true;
    }

    private function updatePrimaryEmail(MemberData $member, Collection &$needToUpdate): bool
    {
        $newEmail = $this->ask('What should the new email be?');

        /** @var WooCommerceApi $wooCommerceApi */
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($member->id, [
                'email' => $newEmail,   // TODO This won't update subscription emails, but it updates GoogleGroup which is what this issue cares about
            ]);

        $needToUpdate = $needToUpdate->reject(fn ($m) => $m === $member);

        return false;
    }

    private function removeSecondaryEmail(MemberData $member, Collection &$needToUpdate): bool
    {
        $secondaryEmails = collect($member->emails);
        $secondaryEmails->forget($member->primaryEmail);
        $secondaryEmails->forget($this->email);

        /** @var WooCommerceApi $wooCommerceApi */
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($member->id, [
                'meta_data' => [
                    [
                        'key' => 'email_aliases',
                        'value' => $secondaryEmails->implode(','),
                    ],
                ],
            ]);

        $needToUpdate = $needToUpdate->reject(fn ($m) => $m === $member);

        return false;
    }
}
