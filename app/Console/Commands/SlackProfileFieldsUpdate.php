<?php

namespace App\Console\Commands;

use App\Actions\Slack\VerifySlackUserProfile;
use App\External\Slack\SlackApi;
use Illuminate\Console\Command;

class SlackProfileFieldsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:slack-profile-fields-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update any slack profiles that were missed by the user_change event';

    private SlackApi $api;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SlackApi $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->withProgressBar($this->api->users->list(), function ($user) {
            if (array_key_exists('deleted', $user) && $user['deleted']) {
                return;
            }
            if (array_key_exists('is_admin', $user) && $user['is_admin']) {
                return;
            }         // Spacebot can't update these
            if (array_key_exists('is_owner', $user) && $user['is_owner']) {
                return;
            }         // Spacebot can't update these
            if (array_key_exists('is_primary_owner', $user) && $user['is_primary_owner']) {
                return;
            } // Spacebot can't update these
            if (array_key_exists('is_bot', $user) && $user['is_bot']) {
                return;
            }
            if (array_key_exists('is_app_user', $user) && $user['is_app_user']) {
                return;
            }

            app(VerifySlackUserProfile::class)
                ->onQueue()
                ->execute($user['id']);
        });

        return 0;
    }
}
