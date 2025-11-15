<?php
declare(strict_types=1);

namespace App\Modules\Monitoring\Models;

use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SiteCheck captures automated/manual health check payloads for a site.
 */
class SiteCheck extends Model
{
    use HasFactory;

    public const TYPE_UPTIME = 'uptime';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_SECURITY = 'security';
    public const TYPE_BACKUP = 'backup';

    public const STATUS_PASS = 'pass';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAIL = 'fail';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'check_type',
        'status',
        'response_time',
        'details',
        'checked_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'site_id' => 'int',
        'response_time' => 'int',
        'details' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Site that was checked.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

}
