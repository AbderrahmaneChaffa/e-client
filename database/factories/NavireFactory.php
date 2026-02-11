<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Navire>
 */
class NavireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName . ' Express',
            'pavillon' => $this->faker->country,
            'date_arrivee' => $this->faker->dateTimeBetween('-1 year', '-2 month'),
            'date_sortie' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
