<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Paiement>
 */
class PaiementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            
            'recu' => 'REC-' . $this->faker->unique()->numberBetween(10000, 99999),
            'date_paiement' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'montant' => 0, // Sera injecté par le seeder pour correspondre à la facture
            'mode_paiement' => $this->faker->randomElement(['1', '2', '3']),
            'numero_cheque' => $this->faker->bankAccountNumber,
            'banque' => $this->faker->randomElement(['BEA', 'BNA', 'BADR','CPA', 'Société Générale', 'BNP Paribas']),
            'created_by' => 1, // Sera mis à jour par le Seeder
        ];
    }
}
