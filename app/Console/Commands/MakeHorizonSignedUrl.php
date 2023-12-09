<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class MakeHorizonSignedUrl extends Command
{
    protected $signature = 'denhac:make-horizon-signed-url';

    protected $description = 'Create a signed URL to populate the secret cookie and get access to horizon';

    public function handle()
    {
        $url = URL::temporarySignedRoute('horizon-signed-url', now()->addMinutes(5));
        $this->info($url);
    }
}
