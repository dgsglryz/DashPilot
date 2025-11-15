<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['site_down', 'security', 'backup_failed', 'ssl_expiry', 'performance']),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'active',
            'message' => $this->faker->sentence(),
            'is_resolved' => false,
            'is_read' => false,
            'resolved_at' => null,
            'resolved_by' => null,
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'resolution_notes' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(function () {
            return [
                'is_resolved' => true,
                'status' => 'resolved',
                'is_read' => true,
                'resolved_at' => now()->subMinutes(rand(5, 60)),
                'resolved_by' => User::factory(),
                'resolution_notes' => $this->faker->sentence(),
            ];
        });
    }
}

