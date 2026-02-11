<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facture>
 */
class FactureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total_ht = $this->faker->randomFloat(2, 5000, 500000);
        $tva = $total_ht * 0.19; // TVA 19%
        $ttc = $total_ht + $tva;
        $annee = fake()->numberBetween(2024, 2026);
        $codes = ['M', 'D', 'C', 'T', 'P', 'PADR'];

        $numero = fake()->unique()->regexify(
            $annee .
                '(' . implode('|', $codes) . ')' .
                '[0-9]{5}'
        );

        return [
            'numero_facture' => $numero,
            'date_facture' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'date_mise_en_ligne' => now(),
            'total_ht' => $total_ht,
            'total_tva' => $tva,
            'total_ttc' => $ttc,
            'montant_paye' => 0, // Sera mis à jour par le Seeder
            'reste_a_payer' => $ttc,
        ];
    }
}
