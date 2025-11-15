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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url')->unique();
            $table->enum('type', ['wordpress', 'shopify', 'woocommerce', 'custom'])->default('wordpress');
            $table->enum('status', ['healthy', 'warning', 'critical', 'offline'])->default('healthy');
            $table->unsignedTinyInteger('health_score')->default(100);
            $table->timestamp('last_checked_at')->nullable();
            $table->decimal('uptime_percentage', 5, 2)->nullable();
            $table->decimal('avg_load_time', 8, 2)->nullable();
            $table->string('wp_api_url')->nullable();
            $table->string('wp_api_key')->nullable();
            $table->string('shopify_store_url')->nullable();
            $table->string('shopify_api_key')->nullable();
            $table->string('shopify_access_token')->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
