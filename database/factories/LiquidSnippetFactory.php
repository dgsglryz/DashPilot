<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiquidSnippet>
 */
class LiquidSnippetFactory extends Factory
{
    protected $model = LiquidSnippet::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(['Loops', 'Conditions', 'Filters']),
            'description' => fake()->sentence(),
            'code' => "{% comment %}\n".fake()->sentence()."\n{% endcomment %}",
            'is_public' => fake()->boolean(60),
            'usage_count' => fake()->numberBetween(0, 50),
            'user_id' => User::factory(),
        ];
    }
}

