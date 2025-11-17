<?php
declare(strict_types=1);

namespace Tests\Feature\Messages;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for MessagesController.
 */
class MessagesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting conversation between two users.
     */
    public function test_get_conversation_returns_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create messages
        Message::factory()->create([
            'sender_id' => $user1->id,
            'recipient_id' => $user2->id,
            'content' => 'Hello from user1',
        ]);

        Message::factory()->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'content' => 'Hello from user2',
        ]);

        $response = $this->actingAs($user1)
            ->getJson("/messages/conversation/{$user2->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'messages' => [
                    '*' => ['id', 'content', 'sender_id', 'recipient_id', 'is_sender', 'sender_name', 'created_at', 'is_read'],
                ],
            ]);

        // Verify messages are marked as read
        $this->assertDatabaseHas('messages', [
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => true,
        ]);
    }

    /**
     * Test sending a message.
     */
    public function test_send_message_creates_message(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($user1)
            ->postJson('/messages/send', [
                'recipient_id' => $user2->id,
                'content' => 'Test message',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message' => ['id', 'content', 'sender_id', 'recipient_id', 'is_sender', 'sender_name', 'created_at', 'is_read'],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $user1->id,
            'recipient_id' => $user2->id,
            'content' => 'Test message',
            'is_read' => false,
        ]);
    }

    /**
     * Test cannot send message to self.
     */
    public function test_cannot_send_message_to_self(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/messages/send', [
                'recipient_id' => $user->id,
                'content' => 'Test message',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Cannot send message to yourself.']);
    }

    /**
     * Test getting unread message count.
     */
    public function test_unread_count_returns_correct_count(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create unread messages
        Message::factory()->count(3)->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => false,
        ]);

        // Create read message
        Message::factory()->create([
            'sender_id' => $user2->id,
            'recipient_id' => $user1->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/messages/unread-count');

        $response->assertStatus(200)
            ->assertJson(['count' => 3]);
    }

    /**
     * Test getting conversations list.
     */
    public function test_conversations_returns_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create messages with user2
        Message::factory()->create([
            'sender_id' => $user1->id,
            'recipient_id' => $user2->id,
            'content' => 'Message 1',
        ]);

        // Create messages with user3
        Message::factory()->create([
            'sender_id' => $user3->id,
            'recipient_id' => $user1->id,
            'content' => 'Message 2',
        ]);

        $response = $this->actingAs($user1)
            ->getJson('/messages/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversations' => [
                    '*' => ['id', 'name', 'email', 'unread_count'],
                ],
            ]);

        $conversations = $response->json('conversations');
        $this->assertCount(2, $conversations);
    }
}

