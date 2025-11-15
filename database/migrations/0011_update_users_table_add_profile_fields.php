<?php
declare(strict_types=1);

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
            $table->string('role')->default('member')->after('password');
            $table->string('status')->default('active')->after('role');
            $table->string('company')->nullable()->after('status');
            $table->string('timezone')->default('UTC')->after('company');
            $table->string('language')->default('en')->after('timezone');
            $table->timestamp('last_active_at')->nullable()->after('language');
            $table->json('notification_settings')->nullable()->after('last_active_at');
            $table->json('monitoring_settings')->nullable()->after('notification_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'status',
                'company',
                'timezone',
                'language',
                'last_active_at',
                'notification_settings',
                'monitoring_settings',
            ]);
        });
    }
};

