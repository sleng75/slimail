<?php

namespace App\Console\Commands;

use App\Models\EmailEvent;
use App\Models\SentEmail;
use App\Models\AutomationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:data
                            {--days=90 : Number of days to keep data}
                            {--events : Clean up email events}
                            {--logs : Clean up automation logs}
                            {--emails : Clean up old sent emails}
                            {--all : Clean up all data types}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old data to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $all = $this->option('all');

        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up data older than {$days} days (before {$cutoff->toDateString()})");

        if ($dryRun) {
            $this->warn('DRY RUN - No data will be deleted');
        }

        $this->newLine();

        $totalDeleted = 0;

        // Clean email events
        if ($all || $this->option('events')) {
            $count = $this->cleanupEmailEvents($cutoff, $dryRun);
            $totalDeleted += $count;
        }

        // Clean automation logs
        if ($all || $this->option('logs')) {
            $count = $this->cleanupAutomationLogs($cutoff, $dryRun);
            $totalDeleted += $count;
        }

        // Clean old sent emails (be careful with this!)
        if ($this->option('emails')) {
            $count = $this->cleanupSentEmails($cutoff, $dryRun);
            $totalDeleted += $count;
        }

        $this->newLine();
        $this->info("Total records " . ($dryRun ? 'to be ' : '') . "deleted: {$totalDeleted}");

        return Command::SUCCESS;
    }

    /**
     * Clean up old email events.
     */
    protected function cleanupEmailEvents(Carbon $cutoff, bool $dryRun): int
    {
        $this->info('Cleaning up email events...');

        $query = EmailEvent::where('created_at', '<', $cutoff);
        $count = $query->count();

        if (!$dryRun && $count > 0) {
            // Delete in chunks to avoid memory issues
            $deleted = 0;
            while ($deleted < $count) {
                $batch = EmailEvent::where('created_at', '<', $cutoff)
                    ->limit(10000)
                    ->delete();

                $deleted += $batch;

                if ($batch === 0) {
                    break;
                }

                $this->output->write('.');
            }
            $this->newLine();
        }

        $this->line("  Email events: {$count} records " . ($dryRun ? 'to delete' : 'deleted'));

        return $count;
    }

    /**
     * Clean up old automation logs.
     */
    protected function cleanupAutomationLogs(Carbon $cutoff, bool $dryRun): int
    {
        $this->info('Cleaning up automation logs...');

        // Check if AutomationLog model exists
        if (!class_exists(AutomationLog::class)) {
            $this->warn('  AutomationLog model not found, skipping...');
            return 0;
        }

        $query = AutomationLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $deleted = 0;
            while ($deleted < $count) {
                $batch = AutomationLog::where('created_at', '<', $cutoff)
                    ->limit(10000)
                    ->delete();

                $deleted += $batch;

                if ($batch === 0) {
                    break;
                }

                $this->output->write('.');
            }
            $this->newLine();
        }

        $this->line("  Automation logs: {$count} records " . ($dryRun ? 'to delete' : 'deleted'));

        return $count;
    }

    /**
     * Clean up old sent emails.
     * Note: This deletes actual email records, use with caution!
     */
    protected function cleanupSentEmails(Carbon $cutoff, bool $dryRun): int
    {
        $this->info('Cleaning up old sent emails...');
        $this->warn('  Warning: This will delete sent email records!');

        // Only delete emails that are not part of campaigns (transactional emails)
        $query = SentEmail::whereNull('campaign_id')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            if (!$this->confirm("Are you sure you want to delete {$count} sent email records?")) {
                $this->info('  Skipped.');
                return 0;
            }

            $deleted = 0;
            while ($deleted < $count) {
                // First delete related events
                $emailIds = SentEmail::whereNull('campaign_id')
                    ->where('created_at', '<', $cutoff)
                    ->limit(1000)
                    ->pluck('id');

                if ($emailIds->isEmpty()) {
                    break;
                }

                EmailEvent::whereIn('sent_email_id', $emailIds)->delete();

                $batch = SentEmail::whereIn('id', $emailIds)->delete();
                $deleted += $batch;

                $this->output->write('.');
            }
            $this->newLine();
        }

        $this->line("  Sent emails: {$count} records " . ($dryRun ? 'to delete' : 'deleted'));

        return $count;
    }
}
