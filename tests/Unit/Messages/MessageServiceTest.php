<?php
declare(strict_types=1);

namespace Tests\Unit\Messages;

use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Services\MessageService;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MessageService.
 */
class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->messageService = new MessageService();
    }

    /**
     * Test sending a message.
     */
    public function test_send_message_creates_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = $this->messageService->sendMessage($sender, $recipient->id, 'Test message');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($sender->id, $message->sender_id);
        $this->assertEquals($recipient->id, $message->recipient_id);
        $this->assertEquals('Test message', $message->content);
        $this->assertFalse($message->is_read);
    }

    /**
     * Test getting conversation between two users.
     */
    public function test_get_conversation_returns_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $user1->id,
            'recipient_id' => $user2->id,
            'content' => 'Message 1',
        ]);

        Message::factory()->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'content' => 'Message 2',
        ]);

        $conversation = $this->messageService->getConversation($user1->id, $user2->id);

        $this->assertCount(2, $conversation);
        $this->assertEquals('Message 1', $conversation->first()->content);
    }

    /**
     * Test getting unread messages.
     */
    public function test_get_unread_messages_returns_only_unread(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'content' => 'Unread 1',
            'is_read' => false,
        ]);

        Message::factory()->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'content' => 'Read 1',
            'is_read' => true,
        ]);

        $unread = $this->messageService->getUnreadMessages($user1);

        $this->assertCount(1, $unread);
        $this->assertEquals('Unread 1', $unread->first()->content);
    }

    /**
     * Test marking conversation as read.
     */
    public function test_mark_conversation_as_read_updates_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => false,
        ]);

        $count = $this->messageService->markConversationAsRead($user1->id, $user2->id);

        $this->assertEquals(3, $count);
        $this->assertDatabaseMissing('messages', [
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => false,
        ]);
    }
}

