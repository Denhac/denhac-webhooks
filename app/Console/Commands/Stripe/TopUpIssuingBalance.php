<?php

namespace App\Console\Commands\Stripe;

use App\Actions\Stripe\SetIssuingBalanceToValue;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TopUpIssuingBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:top-up-issuing-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tops up the stripe issuing balance to $1,000';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // This code should only be temporary until we have the full system built out to manage current issuing balance

        $today = Carbon::today();
        app(SetIssuingBalanceToValue::class)
            ->onQueue()
            ->execute(100000, "Top-Up to $1,000 on {$today->toFormattedDayDateString()}");
    }
}
