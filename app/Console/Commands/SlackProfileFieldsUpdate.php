<?php

namespace App\Console\Commands;

use App\External\Slack\SlackApi;
use App\External\Slack\SlackProfileFields;
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
     *
     * @return int
     */
    public function handle()
    {
        $this->withProgressBar($this->api->users->list(), function ($user) {
            if (array_key_exists("deleted", $user) && $user["deleted"]) return;
            if (array_key_exists("is_admin", $user) && $user["is_admin"]) return;         // Spacebot can't update these
            if (array_key_exists("is_owner", $user) && $user["is_owner"]) return;         // Spacebot can't update these
            if (array_key_exists("is_primary_owner", $user) && $user["is_primary_owner"]) return; // Spacebot can't update these
            if (array_key_exists("is_bot", $user) && $user["is_bot"]) return;
            if (array_key_exists("is_app_user", $user) && $user["is_app_user"]) return;

            $fields = [];
            if (array_key_exists('profile', $user) &&
                array_key_exists('fields', $user['profile']) &&
                !is_null($user['profile']['fields'])) {
                $fields = $user['profile']['fields'];
            }

            SlackProfileFields::updateIfNeeded($user['id'], $fields);
        });

        return 0;
    }
}
