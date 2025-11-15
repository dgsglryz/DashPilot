<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'site_id' => Site::factory(),
            'report_month' => $this->faker->dateTimeBetween('-3 months'),
            'uptime_percentage' => $this->faker->randomFloat(2, 95, 100),
            'avg_load_time' => $this->faker->randomFloat(2, 0.8, 4),
            'total_backups' => $this->faker->numberBetween(20, 60),
            'security_scans' => $this->faker->numberBetween(4, 12),
            'incidents_count' => $this->faker->numberBetween(0, 5),
            'pdf_path' => null,
            'generated_at' => now(),
        ];
    }
}

