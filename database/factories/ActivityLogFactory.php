<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory(),
            'action' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now(),
        ];
    }
}

