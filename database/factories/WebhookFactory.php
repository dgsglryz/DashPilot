<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Notifications\Models\Webhook;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Webhook',
            'url' => fake()->url(),
            'events' => ['alerts', 'downtime'],
            'is_active' => true,
            'secret' => fake()->sha256(),
            'retry_count' => 3,
            'user_id' => User::factory(),
        ];
    }
}

