<?php
declare(strict_types=1);

namespace App\Modules\Sites\Models;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Tasks\Models\Task;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

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

    /**
     * Provide lenient encryption handling for the WordPress API key.
     */
    protected function wpApiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $exception) {
                    Log::warning('Failed to decrypt site credential.', [
                        'column' => 'wp_api_key',
                        'site_id' => $this->id,
                        'message' => $exception->getMessage(),
                    ]);

                    return null;
                }
            },
            set: function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                try {
                    Crypt::decryptString($value);

                    return $value;
                } catch (DecryptException) {
                    return Crypt::encryptString($value);
                }
            },
        );
    }

    /**
     * Provide lenient encryption handling for the Shopify access token.
     */
    protected function shopifyAccessToken(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $exception) {
                    Log::warning('Failed to decrypt site credential.', [
                        'column' => 'shopify_access_token',
                        'site_id' => $this->id,
                        'message' => $exception->getMessage(),
                    ]);

                    return null;
                }
            },
            set: function (?string $value): ?string {
                if (empty($value)) {
                    return null;
                }

                try {
                    Crypt::decryptString($value);

                    return $value;
                } catch (DecryptException) {
                    return Crypt::encryptString($value);
                }
            },
        );
    }
}
