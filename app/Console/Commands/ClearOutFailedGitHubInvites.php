<?php

namespace App\Console\Commands;

use App\External\GitHub\GitHubApi;
use App\External\HasApiProgressBar;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Models\Customer;
use App\Notifications\GitHubInviteExpired;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ClearOutFailedGitHubInvites extends Command
{
    use HasApiProgressBar;

    protected $signature = 'denhac:clear-out-failed-git-hub-invites {--dry-run}';

    protected $description = 'Members can add their GitHub username to their profile, but they don\'t always accept the
    invite and it expires after 7 days. GitHub keeps those and we can check against our customers. If there\'s a failed
    invite, we update their profile to remove the GitHub username (they can add it back) and email them if they are a
    current member letting them know about the issue.';

    private GitHubApi $gitHubApi;

    private WooCommerceApi $wooCommerceApi;

    public function __construct(
        GitHubApi $gitHubApi,
        WooCommerceApi $wooCommerceApi
    ) {
        parent::__construct();

        $this->gitHubApi = $gitHubApi;
        $this->wooCommerceApi = $wooCommerceApi;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->line('Dry run, will not actually update anything.');
        }

        $progressBar = $this->apiProgress('GitHub members in Organization');
        $inOrganization = $this->getUsernames($this->gitHubApi->denhac()->listMembers($progressBar));
        $this->info("We have {$inOrganization->count()} people in our GitHub organization");

        $progressBar = $this->apiProgress('GitHub pending invitations');
        $pendingInvitations = $this->getUsernames($this->gitHubApi->denhac()->pendingInvitations($progressBar));
        $this->info("We have {$pendingInvitations->count()} pending invitations that haven't been accepted");

        $progressBar = $this->apiProgress('GitHub failed invitations');
        $failedInvites = $this->getUsernames($this->gitHubApi->denhac()->failedInvitations($progressBar));
        $this->info("We have {$failedInvites->count()} failed invites");

        $customers = Customer::whereNotNull('github_username')->where('member', true)->get();
        $this->info("We have {$customers->count()} that we need to verify");

        foreach ($customers as $customer) {
            /** @var Customer $customer */
            $isInOrganization = $inOrganization
                ->filter(fn ($username) => Str::lower($username) == Str::lower($customer->github_username))
                ->isNotEmpty();
            $isPending = $pendingInvitations
                ->filter(fn ($username) => Str::lower($username) == Str::lower($customer->github_username))
                ->isNotEmpty();

            if ($isInOrganization || $isPending) {
                // If they're in the organization already or they're pending, we won't check any failed invites. Failed
                // invites persist even if someone has been invited again.
                continue;
            }

            $hasFailedInvite = $failedInvites
                ->filter(fn ($username) => Str::lower($username) == Str::lower($customer->github_username))
                ->isNotEmpty();

            if (! $hasFailedInvite) {
                continue;  // This user is either in the GitHub organization or has been invited and that hasn't expired
            }

            $this->info("$customer->first_name $customer->last_name has a failed invite for GitHub username $customer->github_username");

            if (! $isDryRun) {
                $this->wooCommerceApi->customers
                    ->update($customer->id, [
                        'meta_data' => [
                            [
                                'key' => 'github_username',
                                'value' => null,
                            ],
                        ],
                    ]);

                Notification::route('mail', $customer->email)->notify(new GitHubInviteExpired);
            }
        }
    }

    protected function getUsernames(Collection $gitHubCollection): Collection
    {
        return $gitHubCollection->map(fn ($gitHubUser) => $gitHubUser['login']);
    }
}
