<?php
declare(strict_types=1);

namespace App\Modules\Alerts\Models;

use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use App\Shared\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alert models actionable incidents such as downtime, SSL expiry, etc.
 */
class Alert extends Model
{
    use HasFactory, Searchable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'title',
        'type',
        'severity',
        'status',
        'message',
        'is_resolved',
        'is_read',
        'resolved_at',
        'resolved_by',
        'acknowledged_at',
        'acknowledged_by',
        'resolution_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'site_id' => 'int',
        'is_resolved' => 'bool',
        'resolved_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_by' => 'int',
        'acknowledged_by' => 'int',
        'is_read' => 'bool',
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

    /**
     * User who acknowledged the alert (optional).
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope a query to only include alerts accessible to a user.
     * Admin users see all alerts, others see only alerts for sites belonging to their assigned clients.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->whereHas('site.client', function ($q) use ($user) {
            $q->where('assigned_developer_id', $user->id);
        });
    }

    /**
     * Get the list of fields that should be searchable.
     *
     * @return array<int, string>
     */
    protected function getSearchableFields(): array
    {
        return ['title', 'message', 'type'];
    }
}
