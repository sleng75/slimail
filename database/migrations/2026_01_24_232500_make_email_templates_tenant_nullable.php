<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes tenant_id and created_by nullable for system templates.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying columns, so we need to recreate the table
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('email_templates', function (Blueprint $table) {
                // Drop foreign keys first (SQLite may not have them enforced)
            });

            // For SQLite, we use a workaround: create a new table
            DB::statement('PRAGMA foreign_keys = OFF;');

            // Create new table with nullable columns
            Schema::create('email_templates_new', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();

                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('thumbnail')->nullable();

                $table->longText('html_content')->nullable();
                $table->longText('text_content')->nullable();
                $table->json('design_json')->nullable();

                $table->string('default_subject')->nullable();
                $table->string('default_from_name')->nullable();
                $table->string('default_from_email')->nullable();

                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);

                $table->unsignedInteger('usage_count')->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->index(['tenant_id', 'category']);
                $table->index('is_system');
            });

            // Copy data
            DB::statement('INSERT INTO email_templates_new SELECT * FROM email_templates;');

            // Drop old table
            Schema::dropIfExists('email_templates');

            // Rename new table
            Schema::rename('email_templates_new', 'email_templates');

            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            // For MySQL/PostgreSQL
            Schema::table('email_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, we need to recreate again
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            Schema::create('email_templates_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('thumbnail')->nullable();

                $table->longText('html_content')->nullable();
                $table->longText('text_content')->nullable();
                $table->json('design_json')->nullable();

                $table->string('default_subject')->nullable();
                $table->string('default_from_name')->nullable();
                $table->string('default_from_email')->nullable();

                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);

                $table->unsignedInteger('usage_count')->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->index(['tenant_id', 'category']);
            });

            // Copy only non-system templates
            DB::statement('INSERT INTO email_templates_old SELECT * FROM email_templates WHERE tenant_id IS NOT NULL;');

            Schema::dropIfExists('email_templates');
            Schema::rename('email_templates_old', 'email_templates');

            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable(false)->change();
                $table->foreignId('created_by')->nullable(false)->change();
            });
        }
    }
};
