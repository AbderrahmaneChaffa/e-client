<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prestation>
 */
class PrestationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantite = $this->faker->numberBetween(1, 100);
        $pu = $this->faker->randomFloat(2, 100, 5000);

        return [
            'code_produit' => 'PRD-' . $this->faker->unique(true)->numberBetween(100, 999),
            'designation' => $this->faker->sentence(3), // Simule "Gardiennage Navire", "Remorquage", etc.
            'quantite' => $quantite,
            'prix_unitaire' => $pu,
            'total_ht' => $quantite * $pu, // On garde la logique mathématique
        ];
    }
}
