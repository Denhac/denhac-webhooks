<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Console\Command;
use ParentIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CleanupOldWinDSXBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:backup-cleanup {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up old backups';
    /**
     * @var string
     */
    private $backup_path;
    /**
     * @var array|bool|string|null
     */
    private $isDryRun = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->backup_path = storage_path('backups');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        /*
         * 1) The last 30 days of backups are full backups
         * 2) From 30 days until 3 months, we keep one backup per day
         * 3) From 3 months until 1 year, we keep mdb files for backups per day
         * 4) Past 1 year, everything is gone.
         */

        $this->isDryRun = $this->option('dry-run');
        if ($this->isDryRun) {
            $this->info('This is a dry run, no changes will be made.');
        }

        $interval = new DateInterval('P1D');
        $fullBackupRange = new DatePeriod(
            Carbon::now()->subDays(30),
            $interval,
            Carbon::now()
        );
        $dailyBackupRange = new DatePeriod(
            Carbon::instance($fullBackupRange->getStartDate())
                ->subMonths(3),
            $interval,
            $fullBackupRange->getStartDate()
        );
        $databaseFilesOnlyRange = new DatePeriod(
            Carbon::now()->subYear(),
            $interval,
            $dailyBackupRange->getStartDate()
        );

        $windsx_paths = $this->get_windsx_paths();

        $this->handle_daily_backups($windsx_paths, $dailyBackupRange);
        $this->handle_database_only_backups($windsx_paths, $databaseFilesOnlyRange);
        $this->handle_older_than_one_year($windsx_paths, Carbon::now()->subYear());

        $this->handle_empty_directories();

        return 0;
    }

    private function get_windsx_paths()
    {
        $directory = new \RecursiveDirectoryIterator($this->backup_path, RecursiveIteratorIterator::SELF_FIRST);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
            $path_name = $current->getPathname();
            $file_name = $current->getFilename();

            if (strpos($path_name, 'WinDSX') === false) {
                if ($file_name[0] === '.') {
                    return false; // Skip hidden files and directories above WinDSX
                }

                return true; // Keep going
            }

            if ($file_name === 'WinDSX') {
                return true; // Accept the WinDSX directory
            }

            if ($file_name === '.') {
                return true; // Accept the WinDSX directory dot file
            }

            if ($file_name[0] === '.') {
                return false; // Skip hidden files and directories below WinDSX
            }

            return false;
        });

        $iterator = new \RecursiveIteratorIterator($filter);
        $paths = [];
        foreach ($iterator as $info) {
            $pathname = $info->getPathname();
            if (ends_with($pathname, '.')) {
                $pathname = substr($pathname, 0, strlen($pathname) - strlen('.'));
            }
            $paths[] = $pathname;
        }

        return $paths;
    }

    /**
     * @param $file
     * @return DateTime
     * @throws \Exception
     */
    private function get_date_from_path($file): DateTime
    {
        $file = substr($file, strlen($this->backup_path));

        if (starts_with($file, '/on_time/')) {
            $file = substr($file, 9);
        } else {
            throw new \Exception("Unknown starting point for {$file}");
        }

        $file = substr($file, 0, strlen($file) - strlen('/WinDSX/'));

        return DateTime::createFromFormat('Y/m/d/h/i', $file);
    }

    private function deleteFull($path)
    {
        if ($this->isDryRun) {
            $this->info("Would delete $path.");

            return;
        }

        $this->info("Deleting $path.");
        $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($path);
    }

    private function deleteNonDatabase($path)
    {
        $it = new RecursiveDirectoryIterator($path);
        $filter = new \RecursiveCallbackFilterIterator($it, function ($current, $key, $iterator) {
            $file_name = $current->getFilename();

            if (ends_with($file_name, '.mdb')) {
                return false;
            }

            if (ends_with($file_name, '.ldb')) {
                return false;
            }

            if (strpos($current->getPathname(), 'MdbStruc')) {
                return false;
            }

            if ($file_name[0] == '.') {
                return false;
            }

            return true;
        });
        $files = iterator_to_array(
            new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::CHILD_FIRST)
        );

        if (count($files) == 0) {
            return;
        }

        if ($this->isDryRun) {
            $this->info("Would delete all but database in $path.");

            return;
        }

        $this->info("Deleting all but database $path.");

        foreach ($files as $file) {
            if (! $file->isDir()) {
                unlink($file->getPathname());
            } else {
                $this->remove_empty_sub_folders($file->getPathname());
            }
        }
    }

    /**
     * @param array $windsx_paths
     * @param DatePeriod $dailyBackupRange
     * @throws \Exception
     */
    private function handle_daily_backups(array $windsx_paths, DatePeriod $dailyBackupRange): void
    {
        $dailyBackups = [];
        foreach ($windsx_paths as $path) {
            $date = $this->get_date_from_path($path);

            if ($date >= $dailyBackupRange->getStartDate() &&
                $date <= $dailyBackupRange->getEndDate()) {
                $formattedDate = $date->format('Y-m-d');

                if (array_key_exists($formattedDate, $dailyBackups)) {
                    // Delete these as we already have an entry for that day
                    $this->deleteFull($path);
                } else {
                    $dailyBackups[$formattedDate] = $path;
                }
            }
        }
    }

    private function handle_database_only_backups(array $windsx_paths, DatePeriod $databaseFilesOnlyRange)
    {
        foreach ($windsx_paths as $path) {
            $date = $this->get_date_from_path($path);

            if ($date >= $databaseFilesOnlyRange->getStartDate() &&
                $date <= $databaseFilesOnlyRange->getEndDate()) {
                $this->deleteNonDatabase($path);
            }
        }
    }

    private function handle_older_than_one_year(array $windsx_paths, Carbon $subYear)
    {
        foreach ($windsx_paths as $path) {
            $date = $this->get_date_from_path($path);

            if ($date <= $subYear) {
                $this->deleteFull($path);
            }
        }
    }

    private function handle_empty_directories()
    {
        $this->remove_empty_sub_folders($this->backup_path);
    }

    private function remove_empty_sub_folders($path)
    {
        $empty = true;
        foreach (glob($path.DIRECTORY_SEPARATOR.'*') as $file) {
            if (strpos($path, 'WinDSX') === false) {
                $empty &= is_dir($file) && $this->remove_empty_sub_folders($file);
            } else {
                $empty = false;
            }
        }

        if (! $empty) {
            return;
        }

        if ($this->isDryRun) {
            $this->info("Directory {$path} is empty, would delete.");
        } else {
            $this->info("Deleting {$path} because it is empty.");
            rmdir($path);
        }
    }
}
