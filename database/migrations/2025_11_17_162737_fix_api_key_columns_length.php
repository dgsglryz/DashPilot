<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix API key columns to support encrypted values (text instead of string).
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->text('wp_api_key')->nullable()->change();
            $table->text('shopify_api_key')->nullable()->change();
            $table->text('shopify_access_token')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('wp_api_key')->nullable()->change();
            $table->string('shopify_api_key')->nullable()->change();
            $table->string('shopify_access_token')->nullable()->change();
        });
    }
};
