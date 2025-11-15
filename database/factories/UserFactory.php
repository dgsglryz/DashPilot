<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Users\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement(['member', 'manager', 'admin']),
            'status' => 'active',
            'company' => fake()->company(),
            'timezone' => fake()->randomElement(['UTC', 'America/Los_Angeles', 'Europe/London']),
            'language' => 'en',
            'last_active_at' => now()->subMinutes(random_int(5, 120)),
            'notification_settings' => [
                'emailAlerts' => true,
                'emailReports' => true,
                'emailDowntime' => true,
                'twoFactorEnabled' => false,
            ],
            'monitoring_settings' => [
                'checkInterval' => 5,
                'timeout' => 10,
                'uptimeThreshold' => 95,
                'responseTimeThreshold' => 2000,
            ],
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
