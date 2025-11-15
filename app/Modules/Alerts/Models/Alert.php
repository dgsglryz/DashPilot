<?php
declare(strict_types=1);

namespace App\Modules\Alerts\Models;

use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alert models actionable incidents such as downtime, SSL expiry, etc.
 */
class Alert extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'type',
        'severity',
        'message',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'site_id' => 'int',
        'is_resolved' => 'bool',
        'resolved_at' => 'datetime',
        'resolved_by' => 'int',
    ];

    /**
     * Site associated with the alert.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * User who resolved the alert (optional).
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
