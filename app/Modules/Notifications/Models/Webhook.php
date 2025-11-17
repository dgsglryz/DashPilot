<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook stores outbound webhook endpoints for alert delivery.
 */
class Webhook extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'events',
        'is_active',
        'secret',
        'retry_count',
        'last_triggered_at',
        'user_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'bool',
        'retry_count' => 'int',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'events' => '[]',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

