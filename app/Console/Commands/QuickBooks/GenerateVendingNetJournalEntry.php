<?php

namespace App\Console\Commands\QuickBooks;

use App\External\QuickBooks\QuickBooksAuthSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateVendingNetJournalEntry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:generate-vending-net-journal-entry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a vending net journal entry for yesterday';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        $yesterday = Carbon::now('America/Denver')->subDay();
        app(GenerateVendingNetJournalEntry::class)
            ->onQueue()
            ->execute($yesterday);
    }
}
