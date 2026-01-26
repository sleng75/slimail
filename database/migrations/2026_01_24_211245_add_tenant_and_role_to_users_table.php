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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'editor', 'analyst', 'developer'])->default('owner')->after('email');
            $table->boolean('is_super_admin')->default(false)->after('role'); // Pour SLIMAT
            $table->string('phone')->nullable()->after('is_super_admin');
            $table->string('avatar')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();

            $table->index(['tenant_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropColumn([
                'tenant_id',
                'role',
                'is_super_admin',
                'phone',
                'avatar',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
