<?php
declare(strict_types=1);

namespace App\Modules\Messages\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Messages\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * MessagesController handles HTTP requests for team messaging.
 */
class MessagesController extends Controller
{
    /**
     * @param MessageService $messageService Message service instance
     */
    public function __construct(
        private readonly MessageService $messageService
    ) {
    }

    /**
     * Get conversation between current user and another user.
     *
     * @param int $userId The other user's ID
     * @return JsonResponse JSON response with messages
     */
    public function getConversation(int $userId): JsonResponse
    {
        $user = Auth::user();
        $messages = $this->messageService->getConversation($user->id, $userId);

        // Mark messages as read when viewing conversation
        $this->messageService->markConversationAsRead($user->id, $userId);

        return response()->json([
            'messages' => $messages->map(function ($message) use ($user) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'recipient_id' => $message->recipient_id,
                    'is_sender' => $message->sender_id === $user->id,
                    'sender_name' => $message->sender->name,
                    'created_at' => $message->created_at->toIso8601String(),
                    'is_read' => $message->is_read,
                ];
            }),
        ]);
    }

    /**
     * Send a message to another user.
     *
     * @param Request $request HTTP request
     * @return JsonResponse JSON response with created message
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        // Prevent sending message to self
        if ($user->id === (int) $data['recipient_id']) {
            return response()->json(['error' => 'Cannot send message to yourself.'], 400);
        }

        $message = $this->messageService->sendMessage(
            $user,
            (int) $data['recipient_id'],
            $data['content']
        );

        return response()->json([
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'recipient_id' => $message->recipient_id,
                'is_sender' => true,
                'sender_name' => $message->sender->name,
                'created_at' => $message->created_at->toIso8601String(),
                'is_read' => $message->is_read,
            ],
        ], 201);
    }

    /**
     * Get unread message count for current user.
     *
     * @return JsonResponse JSON response with unread count
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $unreadMessages = $this->messageService->getUnreadMessages($user);

        return response()->json([
            'count' => $unreadMessages->count(),
        ]);
    }

    /**
     * Get all conversations for current user.
     *
     * @return JsonResponse JSON response with conversations
     */
    public function conversations(): JsonResponse
    {
        $user = Auth::user();
        $conversations = $this->messageService->getConversations($user);

        return response()->json([
            'conversations' => $conversations->map(function ($otherUser) {
                return [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'email' => $otherUser->email,
                    'unread_count' => $otherUser->unread_count ?? 0,
                ];
            }),
        ]);
    }
}

