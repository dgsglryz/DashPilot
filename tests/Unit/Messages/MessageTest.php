<?php
declare(strict_types=1);

namespace Tests\Unit\Messages;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for Message model.
 */
class MessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test markAsRead method marks message as read.
     */
    public function test_mark_as_read_updates_message(): void
    {
        $message = Message::factory()->create([
            'is_read' => false,
        ]);

        $message->markAsRead();

        $this->assertTrue($message->fresh()->is_read);
        $this->assertNotNull($message->fresh()->read_at);
    }

    /**
     * Test markAsRead does not update if already read.
     */
    public function test_mark_as_read_does_not_update_if_already_read(): void
    {
        $readAt = now()->subHour();
        $message = Message::factory()->create([
            'is_read' => true,
            'read_at' => $readAt,
        ]);

        $message->markAsRead();

        $this->assertEquals($readAt->format('Y-m-d H:i:s'), $message->fresh()->read_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test sender relationship.
     */
    public function test_sender_relationship(): void
    {
        $sender = User::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($sender->id, $message->sender->id);
    }

    /**
     * Test recipient relationship.
     */
    public function test_recipient_relationship(): void
    {
        $recipient = User::factory()->create();
        $message = Message::factory()->create([
            'recipient_id' => $recipient->id,
        ]);

        $this->assertInstanceOf(User::class, $message->recipient);
        $this->assertEquals($recipient->id, $message->recipient->id);
    }
}

