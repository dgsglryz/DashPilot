<?php
declare(strict_types=1);

namespace App\Modules\Messages\Services;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * MessageService handles business logic for team messaging.
 */
class MessageService
{
    /**
     * Send a message from one user to another.
     *
     * @param User $sender The user sending the message
     * @param int $recipientId The ID of the recipient
     * @param string $content The message content
     * @return Message The created message
     */
    public function sendMessage(User $sender, int $recipientId, string $content): Message
    {
        return Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'content' => $content,
        ]);
    }

    /**
     * Get conversation between two users.
     *
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @param int $limit Number of messages to retrieve
     * @return Collection<Message> Collection of messages
     */
    public function getConversation(int $userId1, int $userId2, int $limit = 50): Collection
    {
        return Message::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)
                ->where('recipient_id', $userId2);
        })
            ->orWhere(function ($query) use ($userId1, $userId2) {
                $query->where('sender_id', $userId2)
                    ->where('recipient_id', $userId1);
            })
            ->with(['sender:id,name,email', 'recipient:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();
    }

    /**
     * Get unread messages for a user.
     *
     * @param User $user The user to get unread messages for
     * @return Collection<Message> Collection of unread messages
     */
    public function getUnreadMessages(User $user): Collection
    {
        return Message::where('recipient_id', $user->id)
            ->where('is_read', false)
            ->with(['sender:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark messages as read for a conversation.
     *
     * @param int $userId Current user ID
     * @param int $otherUserId Other user ID in conversation
     * @return int Number of messages marked as read
     */
    public function markConversationAsRead(int $userId, int $otherUserId): int
    {
        return Message::where('recipient_id', $userId)
            ->where('sender_id', $otherUserId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get all conversations for a user (list of users they've messaged with).
     *
     * @param User $user The user
     * @return Collection<User> Collection of users with recent messages
     */
    public function getConversations(User $user): Collection
    {
        $sentTo = Message::where('sender_id', $user->id)
            ->select('recipient_id')
            ->distinct()
            ->pluck('recipient_id');

        $receivedFrom = Message::where('recipient_id', $user->id)
            ->select('sender_id')
            ->distinct()
            ->pluck('sender_id');

        $allUserIds = $sentTo->merge($receivedFrom)->unique();

        return User::whereIn('id', $allUserIds)
            ->where('id', '!=', $user->id)
            ->withCount([
                'receivedMessages as unread_count' => function ($query) use ($user) {
                    $query->where('recipient_id', $user->id)
                        ->where('is_read', false);
                },
            ])
            ->get();
    }
}

