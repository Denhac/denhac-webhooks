<?php

namespace App\Jobs;

use App\Google\GoogleApi;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddCustomerToGoogleGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $email;
    protected $group;

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
     * @throws Exception
     */
    public function handle(GoogleApi $googleApi)
    {
        $googleApi->group($this->group)->add($this->email);
    }
}