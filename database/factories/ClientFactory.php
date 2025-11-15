<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'company' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->optional()->e164PhoneNumber(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'assigned_developer_id' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

