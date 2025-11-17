<?php
declare(strict_types=1);

namespace Tests\Unit\Messages\Services;

use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Services\MessageService;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_creates_message(): void
    {
        $service = new MessageService();
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = $service->sendMessage($sender, $recipient->id, 'Test message');

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'content' => 'Test message',
            'is_read' => false,
        ]);
    }

    public function test_get_conversation_returns_messages_between_users(): void
    {
        $service = new MessageService();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Message::factory()->create(['sender_id' => $user1->id, 'recipient_id' => $user2->id]);
        Message::factory()->create(['sender_id' => $user2->id, 'recipient_id' => $user1->id]);

        $messages = $service->getConversation($user1->id, $user2->id);

        $this->assertCount(2, $messages);
    }

    public function test_get_unread_messages_returns_only_unread(): void
    {
        $service = new MessageService();
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Message::factory()->create(['recipient_id' => $user->id, 'is_read' => false]);
        Message::factory()->create(['recipient_id' => $user->id, 'is_read' => true]);

        $unread = $service->getUnreadMessages($user);

        $this->assertCount(1, $unread);
    }

    public function test_mark_conversation_as_read_updates_messages(): void
    {
        $service = new MessageService();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Message::factory()->count(2)->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => false,
        ]);

        $count = $service->markConversationAsRead($user1->id, $user2->id);

        $this->assertEquals(2, $count);
        $this->assertDatabaseMissing('messages', [
            'recipient_id' => $user1->id,
            'sender_id' => $user2->id,
            'is_read' => false,
        ]);
    }

    public function test_get_conversations_returns_users_with_messages(): void
    {
        $service = new MessageService();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Message::factory()->create(['sender_id' => $user1->id, 'recipient_id' => $user2->id]);

        $conversations = $service->getConversations($user1);

        $this->assertCount(1, $conversations);
        $this->assertEquals($user2->id, $conversations->first()->id);
    }
}

