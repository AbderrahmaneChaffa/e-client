<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code_client' => 'C' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->company(),
            'adresse' => $this->faker->address(),
            'nis'  => $this->faker->numerify('###############'), // 15 chiffres
            'rc'   => $this->faker->numerify('##########'),
            'ai'   => $this->faker->numerify('###########'),
            'nif'  => $this->faker->numerify('###############'),
            'email' => $this->faker->unique()->companyEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
