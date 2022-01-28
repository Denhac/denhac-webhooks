<?php

namespace App\Console\Commands;

use App\Slack\SlackApi;
use App\Slack\SlackProfileFields;
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
        $this->api->users->list()
            ->filter(function($user) {
                if($user["is_admin"]) return false;         // Spacebot can't update these
                if($user["is_owner"]) return false;         // Spacebot can't update these
                if($user["is_primary_owner"]) return false; // Spacebot can't update these
                if($user["is_bot"]) return false;
                if($user["is_app_user"]) return false;

                return true;
            })
            ->each(function($user) {
                $fields = [];
                if(array_key_exists('profile', $user) && array_key_exists('fields', $user['profile'])) {
                    $fields = $user['profile']['fields'];
                }

                SlackProfileFields::updateIfNeeded($user['id'], $fields);
            });

        return 0;
    }
}
