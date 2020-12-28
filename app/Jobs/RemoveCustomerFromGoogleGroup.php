<?php

namespace App\Jobs;

use App\Google\GoogleApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveCustomerFromGoogleGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public string $email;
    public string $group;

    /**
     * Create a new job instance.
     *
     * @param $email
     * @param $group
     */
    public function __construct($email, $group)
    {
        $this->email = $email;
        $this->group = $group;
    }

    /**
     * Execute the job.
     *
     * @param GoogleApi $googleApi
     * @return void
     */
    public function handle(GoogleApi $googleApi)
    {
        $googleApi->group($this->group)->remove($this->email);
    }
}
