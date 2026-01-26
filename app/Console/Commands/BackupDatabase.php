<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--compress : Compress the backup with gzip}
                            {--upload : Upload to cloud storage}
                            {--keep=7 : Number of days to keep local backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$timestamp}.sql";
        $backupPath = storage_path("app/backups");

        // Create backup directory if it doesn't exist
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullPath = "{$backupPath}/{$filename}";

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($fullPath)
        );

        // Execute backup
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error('Backup failed!');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }

        $this->info("Database backed up to: {$filename}");

        // Compress if requested
        if ($this->option('compress')) {
            $this->info('Compressing backup...');
            $gzipCommand = "gzip -f {$fullPath}";
            exec($gzipCommand, $output, $returnVar);

            if ($returnVar === 0) {
                $filename .= '.gz';
                $fullPath .= '.gz';
                $this->info("Compressed to: {$filename}");
            }
        }

        // Upload to cloud storage if requested
        if ($this->option('upload')) {
            $this->info('Uploading to cloud storage...');

            try {
                $disk = Storage::disk('s3');
                $remotePath = "backups/database/{$filename}";

                $disk->put($remotePath, file_get_contents($fullPath));

                $this->info("Uploaded to: {$remotePath}");
            } catch (\Exception $e) {
                $this->error("Upload failed: {$e->getMessage()}");
            }
        }

        // Clean up old backups
        $keep = (int) $this->option('keep');
        $this->cleanupOldBackups($backupPath, $keep);

        // Get file size
        $size = filesize($fullPath);
        $sizeFormatted = $this->formatBytes($size);

        $this->newLine();
        $this->info("Backup completed successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Filename', $filename],
                ['Size', $sizeFormatted],
                ['Path', $fullPath],
                ['Timestamp', $timestamp],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Clean up old backup files.
     */
    protected function cleanupOldBackups(string $path, int $keepDays): void
    {
        $files = glob("{$path}/backup_*.sql*");
        $cutoff = Carbon::now()->subDays($keepDays);
        $deleted = 0;

        foreach ($files as $file) {
            $modifiedTime = Carbon::createFromTimestamp(filemtime($file));

            if ($modifiedTime->lt($cutoff)) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup(s)");
        }
    }

    /**
     * Format bytes to human readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
