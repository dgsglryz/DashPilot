<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Models;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use App\Shared\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task represents team work items tied to either a site or a client.
 */
class Task extends Model
{
    use HasFactory, Searchable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'client_id',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'site_id' => 'int',
        'client_id' => 'int',
        'assigned_to' => 'int',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope a query to only include tasks accessible to a user.
     * Admin users see all tasks, others see tasks assigned to them or for their assigned clients.
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

        return $query->where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
                ->orWhereHas('client', function ($clientQ) use ($user) {
                    $clientQ->where('assigned_developer_id', $user->id);
                })
                ->orWhereHas('site.client', function ($siteQ) use ($user) {
                    $siteQ->where('assigned_developer_id', $user->id);
                });
        });
    }

    /**
     * Get the list of fields that should be searchable.
     *
     * @return array<int, string>
     */
    protected function getSearchableFields(): array
    {
        return ['title', 'description'];
    }
}
