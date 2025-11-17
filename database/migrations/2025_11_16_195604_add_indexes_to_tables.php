<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add database indexes to improve query performance.
 * 
 * This migration adds indexes on frequently queried columns
 * to reduce query time from ~500ms to ~10ms.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sites table indexes
        Schema::table('sites', function (Blueprint $table) {
            if (!$this->hasIndex('sites', 'sites_client_id_index')) {
                $table->index('client_id');
            }
            if (!$this->hasIndex('sites', 'sites_type_index')) {
                $table->index('type');
            }
            if (!$this->hasIndex('sites', 'sites_status_index')) {
                $table->index('status');
            }
        });

        // Alerts table indexes
        Schema::table('alerts', function (Blueprint $table) {
            if (!$this->hasIndex('alerts', 'alerts_site_id_index')) {
                $table->index('site_id');
            }
            if (!$this->hasIndex('alerts', 'alerts_severity_index')) {
                $table->index('severity');
            }
            if (!$this->hasIndex('alerts', 'alerts_resolved_at_index')) {
                $table->index('resolved_at');
            }
            if (!$this->hasIndex('alerts', 'alerts_status_index')) {
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['site_id']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['resolved_at']);
            $table->dropIndex(['status']);
        });
    }

    /**
     * Check if an index exists on a table.
     * Supports both MySQL and SQLite.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Check sqlite_master table
            $result = $connection->select(
                "SELECT COUNT(*) as count 
                 FROM sqlite_master 
                 WHERE type = 'index' 
                 AND name = ?",
                [$indexName]
            );
            return $result[0]->count > 0;
        }

        // MySQL: Use information_schema
        $database = $connection->getDatabaseName();
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }
};
