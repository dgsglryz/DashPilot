<?php
declare(strict_types=1);

namespace App\Modules\Sites\Models;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Tasks\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Site stores operational data for each managed property (WP, Shopify, etc.).
 */
class Site extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'url',
        'type',
        'status',
        'is_favorited',
        'industry',
        'region',
        'thumbnail_url',
        'logo_url',
        'health_score',
        'last_checked_at',
        'uptime_percentage',
        'avg_load_time',
        'wp_api_url',
        'wp_api_key',
        'shopify_store_url',
        'shopify_api_key',
        'shopify_access_token',
        'last_backup_at',
        'ssl_expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'client_id' => 'int',
        'health_score' => 'int',
        'last_checked_at' => 'datetime',
        'uptime_percentage' => 'decimal:2',
        'avg_load_time' => 'decimal:2',
        'last_backup_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'wp_api_key' => 'encrypted',
        'shopify_access_token' => 'encrypted',
        'is_favorited' => 'boolean',
    ];

    /**
     * Client that owns the site.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Monitoring snapshots for the site.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SiteCheck>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(SiteCheck::class);
    }

    /**
     * Alerts raised for the site.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Alert>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Tasks associated with the site.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Reports generated for the site.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Report>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Activity logs tied to the site.
     *
      * @return \Illuminate\Database\Eloquent\Relations\HasMany<ActivityLog>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
