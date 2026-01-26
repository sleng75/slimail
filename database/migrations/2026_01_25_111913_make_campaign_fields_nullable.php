<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support altering columns directly, so we need to recreate
        // For SQLite, we'll use a workaround
        if (config('database.default') === 'sqlite') {
            // For SQLite, we need to recreate the table
            // But since we're in development, let's just use raw SQL
            \DB::statement('PRAGMA foreign_keys=off');

            // Create temp table with correct schema
            \DB::statement('
                CREATE TABLE campaigns_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    created_by INTEGER NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    type VARCHAR(255) DEFAULT "regular",
                    from_name VARCHAR(255),
                    from_email VARCHAR(255),
                    reply_to VARCHAR(255),
                    subject VARCHAR(255),
                    preview_text VARCHAR(255),
                    html_content TEXT,
                    text_content TEXT,
                    template_id INTEGER,
                    status VARCHAR(255) DEFAULT "draft",
                    scheduled_at DATETIME,
                    started_at DATETIME,
                    completed_at DATETIME,
                    timezone VARCHAR(255) DEFAULT "Africa/Abidjan",
                    send_by_timezone TINYINT(1) DEFAULT 0,
                    list_ids TEXT,
                    segment_ids TEXT,
                    excluded_list_ids TEXT,
                    recipients_count INTEGER DEFAULT 0,
                    sent_count INTEGER DEFAULT 0,
                    delivered_count INTEGER DEFAULT 0,
                    opened_count INTEGER DEFAULT 0,
                    clicked_count INTEGER DEFAULT 0,
                    bounced_count INTEGER DEFAULT 0,
                    complained_count INTEGER DEFAULT 0,
                    unsubscribed_count INTEGER DEFAULT 0,
                    ab_test_config TEXT,
                    winning_variant_id INTEGER,
                    track_opens TINYINT(1) DEFAULT 1,
                    track_clicks TINYINT(1) DEFAULT 1,
                    google_analytics TINYINT(1) DEFAULT 0,
                    utm_source VARCHAR(255),
                    utm_medium VARCHAR(255),
                    utm_campaign VARCHAR(255),
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME,
                    FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                    FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE CASCADE
                )
            ');

            // Copy data
            \DB::statement('INSERT INTO campaigns_temp SELECT * FROM campaigns');

            // Drop old table
            \DB::statement('DROP TABLE campaigns');

            // Rename temp to campaigns
            \DB::statement('ALTER TABLE campaigns_temp RENAME TO campaigns');

            // Recreate indexes
            \DB::statement('CREATE INDEX campaigns_tenant_id_status_index ON campaigns (tenant_id, status)');
            \DB::statement('CREATE INDEX campaigns_tenant_id_scheduled_at_index ON campaigns (tenant_id, scheduled_at)');

            \DB::statement('PRAGMA foreign_keys=on');
        } else {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('from_name')->nullable()->change();
                $table->string('from_email')->nullable()->change();
                $table->string('subject')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse in development
    }
};
