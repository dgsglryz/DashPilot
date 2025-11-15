<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => $this->faker->domainWord().' Site',
            'url' => $this->faker->unique()->url(),
            'type' => $this->faker->randomElement(['wordpress', 'shopify', 'woocommerce', 'custom']),
            'status' => $this->faker->randomElement(['healthy', 'warning', 'critical', 'offline']),
            'health_score' => $this->faker->numberBetween(40, 100),
            'last_checked_at' => $this->faker->dateTimeBetween('-1 day'),
            'uptime_percentage' => $this->faker->randomFloat(2, 92, 100),
            'avg_load_time' => $this->faker->randomFloat(2, 0.8, 5),
            'wp_api_url' => $this->faker->optional()->url(),
            'wp_api_key' => $this->faker->optional()->sha1(),
            'shopify_store_url' => $this->faker->optional()->url(),
            'shopify_api_key' => $this->faker->optional()->sha1(),
            'shopify_access_token' => $this->faker->optional()->uuid(),
            'last_backup_at' => $this->faker->optional()->dateTimeBetween('-3 days'),
            'ssl_expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}

