<?php

namespace App\Jobs;

use App\GitHub\GitHubApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveMemberFromGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $username;
    public $team;

    /**
     * Create a new job instance.
     *
     * @param $username
     * @param $team
     */
    public function __construct($username, $team)
    {
        $this->username = $username;
        $this->team = $team;
    }

    /**
     * Execute the job.
     *
     * @param GitHubApi $githubApi
     * @return void
     */
    public function handle(GitHubApi $githubApi)
    {
        $githubApi->team($this->team)->remove($this->username);
    }
}
