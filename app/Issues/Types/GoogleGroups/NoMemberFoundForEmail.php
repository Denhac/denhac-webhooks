<?php

namespace App\Issues\Types\GoogleGroups;

use App\DataCache\MemberData;
use App\External\Google\GoogleApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\FixChooser;
use App\Issues\Fixing\Fixable;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class NoMemberFoundForEmail extends IssueBase implements Fixable
{
    private string $email;

    private Collection $groupsForEmail;

    public function __construct(string $email, Collection $groupsForEmail)
    {
        $this->email = $email;
        $this->groupsForEmail = $groupsForEmail;
    }

    public static function getIssueNumber(): int
    {
        return 102;
    }

    public static function getIssueTitle(): string
    {
        return 'Google Groups: No member found for email';
    }

    public function getIssueText(): string
    {
        return "No member found for email address $this->email in groups: {$this->groupsForEmail->implode(', ')}";
    }

    public function fix(): bool
    {
        return FixChooser::new()
            ->option('Remove email from groups', fn () => $this->removeMemberEmailFromGroups())
            ->option('Assign email to member', fn () => $this->assignEmailToMember())
            ->fix();
    }

    private function removeMemberEmailFromGroups(): bool
    {
        /** @var GoogleApi $googleApi */
        $googleApi = app(GoogleApi::class);
        foreach ($this->groupsForEmail as $group) {
            $googleApi->group($group)->remove($this->email);
        }

        return true;
    }

    private function assignEmailToMember(): bool
    {
        /** @var MemberData $member */
        $member = MemberData::selectMember();

        if (is_null($member)) {
            return false;
        }

        $emailAliases = collect($member->emails);
        $emailAliases->forget($member->primaryEmail);  // Their primary email doesn't go into the aliases
        $emailAliases->add($this->email);  // We're adding this email to the list

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($member->id, [
                'meta_data' => [
                    [
                        'key' => 'email_aliases',
                        'value' => $emailAliases->implode(','),
                    ],
                ],
            ]);

        // If we didn't add this, then assigning two emails to the same member in the same run would fail. Very
        // unlikely to happen, but we should cover it anyway.
        $member->emails->add($this->email);

        return true;
    }
}
