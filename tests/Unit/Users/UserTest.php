<?php
declare(strict_types=1);

namespace Tests\Unit\Users;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for User model relationships.
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sentMessages relationship.
     */
    public function test_sent_messages_relationship(): void
    {
        $user = User::factory()->create();
        $recipient = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $user->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertCount(3, $user->sentMessages);
    }

    /**
     * Test receivedMessages relationship.
     */
    public function test_received_messages_relationship(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        Message::factory()->count(2)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $user->id,
        ]);

        $this->assertCount(2, $user->receivedMessages);
    }
}

