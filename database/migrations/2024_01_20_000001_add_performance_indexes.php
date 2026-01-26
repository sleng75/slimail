<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes for common queries.
     * Note: These indexes are only for MySQL/MariaDB, not SQLite (used in tests).
     */
    public function up(): void
    {
        // Skip index creation for SQLite (tests use SQLite in memory)
        if ($this->isSqlite()) {
            return;
        }

        // Contacts table indexes
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                // Composite index for tenant + status queries (most common)
                if (!$this->indexExists('contacts', 'contacts_tenant_status_deleted_idx')) {
                    $table->index(['tenant_id', 'status', 'deleted_at'], 'contacts_tenant_status_deleted_idx');
                }

                // Index for search queries
                if (!$this->indexExists('contacts', 'contacts_tenant_email_idx')) {
                    $table->index(['tenant_id', 'email'], 'contacts_tenant_email_idx');
                }

                // Index for engagement-based sorting
                if (!$this->indexExists('contacts', 'contacts_tenant_engagement_idx')) {
                    $table->index(['tenant_id', 'engagement_score'], 'contacts_tenant_engagement_idx');
                }

                // Index for date-based queries
                if (!$this->indexExists('contacts', 'contacts_tenant_created_idx')) {
                    $table->index(['tenant_id', 'created_at'], 'contacts_tenant_created_idx');
                }
            });
        }

        // Sent emails table indexes
        if (Schema::hasTable('sent_emails')) {
            Schema::table('sent_emails', function (Blueprint $table) {
                // Composite index for campaign statistics
                if (!$this->indexExists('sent_emails', 'sent_emails_campaign_status_idx')) {
                    $table->index(['campaign_id', 'status'], 'sent_emails_campaign_status_idx');
                }

                // Index for tenant + date queries
                if (!$this->indexExists('sent_emails', 'sent_emails_tenant_created_idx')) {
                    $table->index(['tenant_id', 'created_at'], 'sent_emails_tenant_created_idx');
                }

                // Index for status tracking
                if (!$this->indexExists('sent_emails', 'sent_emails_tenant_status_idx')) {
                    $table->index(['tenant_id', 'status'], 'sent_emails_tenant_status_idx');
                }

                // Index for tracking opens/clicks
                if (!$this->indexExists('sent_emails', 'sent_emails_tenant_opened_idx')) {
                    $table->index(['tenant_id', 'opened_at'], 'sent_emails_tenant_opened_idx');
                }
                if (!$this->indexExists('sent_emails', 'sent_emails_tenant_clicked_idx')) {
                    $table->index(['tenant_id', 'clicked_at'], 'sent_emails_tenant_clicked_idx');
                }
            });
        }

        // Campaigns table indexes
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                // Index for listing campaigns by status
                if (!$this->indexExists('campaigns', 'campaigns_tenant_status_deleted_idx')) {
                    $table->index(['tenant_id', 'status', 'deleted_at'], 'campaigns_tenant_status_deleted_idx');
                }

                // Index for scheduled campaigns
                if (!$this->indexExists('campaigns', 'campaigns_status_scheduled_idx')) {
                    $table->index(['status', 'scheduled_at'], 'campaigns_status_scheduled_idx');
                }
            });
        }

        // Contact list members table indexes
        if (Schema::hasTable('contact_list_members')) {
            Schema::table('contact_list_members', function (Blueprint $table) {
                // Composite index for efficient list member queries
                if (!$this->indexExists('contact_list_members', 'clm_list_status_idx')) {
                    $table->index(['contact_list_id', 'status'], 'clm_list_status_idx');
                }
            });
        }

        // Email events table indexes
        if (Schema::hasTable('email_events')) {
            Schema::table('email_events', function (Blueprint $table) {
                // Index for event type queries
                if (!$this->indexExists('email_events', 'email_events_sent_type_idx')) {
                    $table->index(['sent_email_id', 'event_type'], 'email_events_sent_type_idx');
                }

                // Index for date-based event queries
                if (!$this->indexExists('email_events', 'email_events_type_created_idx')) {
                    $table->index(['event_type', 'created_at'], 'email_events_type_created_idx');
                }
            });
        }

        // Automation enrollments table indexes
        if (Schema::hasTable('automation_enrollments')) {
            Schema::table('automation_enrollments', function (Blueprint $table) {
                // Index for automation stats
                if (!$this->indexExists('automation_enrollments', 'enrollments_automation_status_idx')) {
                    $table->index(['automation_id', 'status'], 'enrollments_automation_status_idx');
                }

                // Index for contact enrollment lookups
                if (!$this->indexExists('automation_enrollments', 'enrollments_contact_automation_idx')) {
                    $table->index(['contact_id', 'automation_id'], 'enrollments_contact_automation_idx');
                }
            });
        }

        // Subscriptions table indexes
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Index for active subscription lookups
                if (!$this->indexExists('subscriptions', 'subscriptions_tenant_status_idx')) {
                    $table->index(['tenant_id', 'status'], 'subscriptions_tenant_status_idx');
                }

                // Index for expiring subscriptions
                if (!$this->indexExists('subscriptions', 'subscriptions_status_ends_idx')) {
                    $table->index(['status', 'ends_at'], 'subscriptions_status_ends_idx');
                }
            });
        }

        // Invoices table indexes
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Index for tenant invoices
                if (!$this->indexExists('invoices', 'invoices_tenant_status_idx')) {
                    $table->index(['tenant_id', 'status'], 'invoices_tenant_status_idx');
                }

                // Index for due invoices
                if (!$this->indexExists('invoices', 'invoices_status_due_idx')) {
                    $table->index(['status', 'due_date'], 'invoices_status_due_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite
        if ($this->isSqlite()) {
            return;
        }

        $tables = [
            'contacts' => ['contacts_tenant_status_deleted_idx', 'contacts_tenant_email_idx', 'contacts_tenant_engagement_idx', 'contacts_tenant_created_idx'],
            'sent_emails' => ['sent_emails_campaign_status_idx', 'sent_emails_tenant_created_idx', 'sent_emails_tenant_status_idx', 'sent_emails_tenant_opened_idx', 'sent_emails_tenant_clicked_idx'],
            'campaigns' => ['campaigns_tenant_status_deleted_idx', 'campaigns_status_scheduled_idx'],
            'contact_list_members' => ['clm_list_status_idx'],
            'email_events' => ['email_events_sent_type_idx', 'email_events_type_created_idx'],
            'automation_enrollments' => ['enrollments_automation_status_idx', 'enrollments_contact_automation_idx'],
            'subscriptions' => ['subscriptions_tenant_status_idx', 'subscriptions_status_ends_idx'],
            'invoices' => ['invoices_tenant_status_idx', 'invoices_status_due_idx'],
        ];

        foreach ($tables as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($indexes) {
                    foreach ($indexes as $index) {
                        if ($this->indexExists($t->getTable(), $index)) {
                            $t->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if we're using SQLite.
     */
    protected function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Check if an index exists on a table.
     */
    protected function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection();
        $prefix = $connection->getTablePrefix();
        $tableName = $prefix . $table;

        if ($this->isSqlite()) {
            return false;
        }

        $indexes = $connection->select(
            "SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?",
            [$index]
        );

        return count($indexes) > 0;
    }
};
