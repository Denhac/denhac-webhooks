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
        $this->onQueue('backups');
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Throwable
     */
    public function handle()
    {
        try {
            /** @var BackupWinDSX $backupWinDSX */
            $backupWinDSX = app(BackupWinDSX::class);
            $backupWinDSX->backup($this->path);

            setting([self::JOB_FAILURE_KEY => 0])->save();
        } catch (Throwable $throwable) {
            $value = setting(self::JOB_FAILURE_KEY, 0);

            $value++;

            setting([self::JOB_FAILURE_KEY => $value])->save();

            if($value >= 100) {
                throw $throwable;
            }
        }
    }
}
