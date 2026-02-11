<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Facture;
use App\Models\Navire;
use App\Models\Paiement;
use App\Models\Prestation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        
        // 2. Créer 1000 clients de manière performante
        // On utilise count(1000) pour générer la masse
        Client::factory()->count(1000)->create();

        $this->command->info('1000 clients ont été créés avec succès pour le Port d\'Oran !');
       //--------------------------------------------------------------------------------------------
       
       // 1. Créer 500 Navires
        $navires = Navire::factory()->count(500)->create();

        // 2. Récupérer les IDs des 1000 clients créés précédemment
        $clientIds = Client::pluck('id');

        $this->command->info('Génération de 20 000 factures...');

        // Utilisation de blocs pour ne pas saturer la mémoire
        for ($i = 0; $i < 20; $i++) {
            Facture::factory()
                ->count(1000)
                ->create([
                    'client_id' => fn() => $clientIds->random(),
                    'navire_id' => fn() => $navires->random()->id,
                ])
                ->each(function ($facture) {
                    // Ajouter 3 prestations par facture
                    Prestation::factory()->count(3)->create([
                        'facture_id' => $facture->id,
                        'total_ht' => $facture->total_ht / 3
                    ]);

                    // Simuler un paiement aléatoire pour certaines factures
                    if (rand(0, 1)) {
                        $versement = $facture->total_ttc * (rand(50, 100) / 100);
                        Paiement::factory()->create([
                            'facture_id' => $facture->id,
                            'montant_verse' => $versement,
                            'date_paiement' => now(),
                        ]);

                        // Mise à jour des soldes de la facture
                        $facture->update([
                            'montant_paye' => $versement,
                            'reste_a_payer' => $facture->total_ttc - $versement
                        ]);
                    }
                });
            $this->command->info("Bloc " . ($i + 1) . " terminé...");
        }
    }
}
