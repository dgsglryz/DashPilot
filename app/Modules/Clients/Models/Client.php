<?php
declare(strict_types=1);

namespace App\Modules\Clients\Models;

use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use App\Shared\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Client aggregates agency customers and tracks their assigned developer plus portfolio sites.
 */
class Client extends Model
{
    use HasFactory, Searchable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'status',
        'assigned_developer_id',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_developer_id' => 'int',
    ];

    /**
     * The developer responsible for this client.
     */
    public function assignedDeveloper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_developer_id');
    }

    /**
     * Sites owned by the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Site>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * Reports shared with the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Report>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Tasks linked to the client (may or may not be tied to a specific site).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Task>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope a query to only include clients assigned to a specific developer.
     *
     * @param Builder $query
     * @param int $developerId
     * @return Builder
     */
    public function scopeForDeveloper(Builder $query, int $developerId): Builder
    {
        return $query->where('assigned_developer_id', $developerId);
    }

    /**
     * Scope a query to only include clients accessible to a user.
     * Admin users see all clients, others see only their assigned clients.
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

        return $query->forDeveloper($user->id);
    }

    /**
     * Get the list of fields that should be searchable.
     *
     * @return array<int, string>
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'company', 'email'];
    }
}
