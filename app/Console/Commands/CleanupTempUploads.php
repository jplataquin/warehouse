<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanupTempUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:cleanup {--hours=24 : The age of files in hours to clean up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale chunk upload folders and temporary files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        clearstatcache();
        $hours = (int)$this->option('hours');
        $tempDir = storage_path('app/temp_uploads');

        if (!File::exists($tempDir)) {
            $this->info('No temporary uploads directory found at: ' . $tempDir);
            return;
        }

        $now = Carbon::now();
        $deletedFilesCount = 0;
        $deletedDirsCount = 0;

        // Delete stale chunk directories (folders created for active chunking)
        $dirs = File::directories($tempDir);
        foreach ($dirs as $dir) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($dir));
            if ($now->diffInHours($lastModified, true) >= $hours) {
                File::deleteDirectory($dir);
                $deletedDirsCount++;
                $this->line("Deleted stale directory: {$dir} (Last Modified: {$lastModified})");
            }
        }

        // Delete stale fully assembled temporary files
        $files = File::files($tempDir);
        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($file->getPathname()));
            if ($now->diffInHours($lastModified, true) >= $hours) {
                File::delete($file->getPathname());
                $deletedFilesCount++;
                $this->line("Deleted stale temporary file: {$file->getPathname()} (Last Modified: {$lastModified})");
            }
        }

        $this->info("Cleanup completed. Deleted {$deletedDirsCount} directory/directories and {$deletedFilesCount} file(s).");
    }
}
