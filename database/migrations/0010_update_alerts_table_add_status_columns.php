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
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('title')->after('site_id')->default('Site Alert');
            $table->string('status')->after('severity')->default('active');
            $table->boolean('is_read')->after('status')->default(false);
            $table->timestamp('acknowledged_at')->nullable()->after('resolved_at');
            $table->foreignId('acknowledged_by')
                ->nullable()
                ->after('acknowledged_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acknowledged_by');
            $table->dropColumn(['title', 'status', 'is_read', 'acknowledged_at']);
        });
    }
};

