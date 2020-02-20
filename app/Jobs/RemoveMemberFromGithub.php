<?php

namespace App\Jobs;

use App\Github\GithubApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveMemberFromGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $username;
    private $team;

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
     * @param GithubApi $githubApi
     * @return void
     */
    public function handle(GithubApi $githubApi)
    {
        $githubApi->team($this->team)->remove($this->username);
    }
}
