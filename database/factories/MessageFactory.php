<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Messages\Models\Message;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'content' => $this->faker->sentence(),
            'is_read' => false,
            'read_at' => null,
        ];
    }
}
