<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $name = $this->faker->company().' '.$this->faker->randomElement(['Studio', 'Labs', 'Collective', 'Digital', 'Partners']);
        $slug = Str::slug($name.'-'.$this->faker->unique()->numberBetween(100, 999));
        $industry = $this->faker->randomElement([
            'E-commerce & Retail',
            'Hospitality & Travel',
            'Healthcare',
            'Financial Services',
            'Education',
            'Media & Publishing',
            'Non-profit',
        ]);

        return [
            'client_id' => Client::factory(),
            'name' => $name,
            'url' => $this->faker->unique()->url(),
            'type' => $this->faker->randomElement(['wordpress', 'shopify', 'woocommerce', 'custom']),
            'status' => $this->faker->randomElement(['healthy', 'warning', 'critical']),
            'industry' => $industry,
            'region' => $this->faker->randomElement(['North America', 'Europe', 'Asia-Pacific', 'Latin America', 'Middle East & Africa']),
            'thumbnail_url' => "https://images.unsplash.com/photo-1506765515384-028b60a970df?auto=format&fit=crop&w=1200&q=80&sig={$slug}",
            'logo_url' => "https://api.dicebear.com/7.x/initials/svg?seed={$slug}&backgroundColor=111827,1c1f2b&fontSize=60",
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

