<?php

namespace App\Jobs;

use App\WinDSX\BackupWinDSX;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BackupWinDSXJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $path;

    private const JOB_FAILURE_KEY = 'jobs.backup.failed_count';

    /**
     * Create a new job instance.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        $this->onQueue("backups");
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        /** @var \App\WinDSX\BackupWinDSX $backupWinDSX */
        $backupWinDSX = app(BackupWinDSX::class);
        $backupWinDSX->backup($this->path);

        setting([self::JOB_FAILURE_KEY => 0])->save();
    }

    /**
     * @param Throwable $throwable
     * @throws Throwable
     */
    public function failed(Throwable $throwable)
    {
        $value = setting(self::JOB_FAILURE_KEY, 0);

        $value++;

        setting([self::JOB_FAILURE_KEY => $value])->save();

        if($value >= 10) {
            throw $throwable;
        }
    }
}
