<?php
declare(strict_types=1);

namespace App\Modules\Messages\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message model represents in-app messages between team members.
 */
class Message extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'content',
        'is_read',
        'read_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sender_id' => 'int',
        'recipient_id' => 'int',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user who sent the message.
     *
     * @return BelongsTo<User>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received the message.
     *
     * @return BelongsTo<User>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Mark message as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
}

