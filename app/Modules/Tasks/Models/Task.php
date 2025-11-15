<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Models;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task represents team work items tied to either a site or a client.
 */
class Task extends Model
{
    use HasFactory;

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
}
