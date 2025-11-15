<?php
declare(strict_types=1);

namespace App\Modules\Activity\Models;

use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActivityLog captures auditable events (health checks, alert resolutions, etc.).
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'site_id',
        'action',
        'description',
        'ip_address',
        'created_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'int',
        'site_id' => 'int',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

