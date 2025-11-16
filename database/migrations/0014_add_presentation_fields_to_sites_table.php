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
            // industry and region already exist in 0004_create_sites_table.php
            // Only add thumbnail_url and logo_url if they don't exist
            if (!Schema::hasColumn('sites', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('region');
            }
            if (!Schema::hasColumn('sites', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('thumbnail_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail_url', 'logo_url']);
        });
    }
};

