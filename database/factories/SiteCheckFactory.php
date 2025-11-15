<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteCheck>
 */
class SiteCheckFactory extends Factory
{
    protected $model = SiteCheck::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'check_type' => $this->faker->randomElement(['uptime', 'performance', 'security', 'backup']),
            'status' => $this->faker->randomElement(['pass', 'warning', 'fail']),
            'response_time' => $this->faker->numberBetween(80, 2500),
            'details' => [
                'http_status' => $this->faker->randomElement([200, 200, 503, 500]),
                'notes' => $this->faker->sentence(),
            ],
            'checked_at' => $this->faker->dateTimeBetween('-2 hours'),
        ];
    }
}

