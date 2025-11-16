<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookLog stores delivery attempts and responses for webhook notifications.
 */
class WebhookLog extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'attempt_number',
        'success',
        'error_message',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'webhook_id' => 'int',
        'payload' => 'array',
        'response_status' => 'int',
        'response_body' => 'string',
        'attempt_number' => 'int',
        'success' => 'bool',
    ];

    /**
     * Webhook associated with this log entry.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}

