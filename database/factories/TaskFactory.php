<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'client_id' => Client::factory(),
            'assigned_to' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 weeks'),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

