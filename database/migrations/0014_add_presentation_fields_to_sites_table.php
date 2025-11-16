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
        Schema::table('sites', function (Blueprint $table): void {
            $table->string('industry')->nullable()->after('type');
            $table->string('region')->nullable()->after('industry');
            $table->string('thumbnail_url')->nullable()->after('region');
            $table->string('logo_url')->nullable()->after('thumbnail_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropColumn(['industry', 'region', 'thumbnail_url', 'logo_url']);
        });
    }
};

