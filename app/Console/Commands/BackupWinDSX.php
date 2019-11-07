<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupWinDSX extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:windsx-backup {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backs up the remote WinDSX directory to a local directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->argument("path");
        mkdir($path, 0777, true);

        $command = "scp -r denhac-access:/C:/WinDSX {$path}";
        exec($command); # TODO Handle failures
    }
}
