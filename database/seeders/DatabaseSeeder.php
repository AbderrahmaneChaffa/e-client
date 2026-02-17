<?php



namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Navire;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Prestation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Créer l'admin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@e-client.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 2. Créer 1000 clients
        $this->command->info('Création des clients...');
        Client::factory()->count(1000)->create([
            'created_by' => $admin->id
        ]);
        $clientIds = Client::pluck('id');

        // 3. Créer 500 Navires
        $this->command->info('Création des navires...');
        $navires = Navire::factory()->count(500)->create();

        $this->command->info('Génération de 20 000 factures (Processus lourd)...');

        // On utilise des blocs de 500 pour ne pas surcharger la RAM
        for ($i = 0; $i < 40; $i++) {
            Facture::factory()
                ->count(500)
                ->create([
                    'client_id' => fn() => $clientIds->random(),
                    'navire_id' => fn() => $navires->random()->id,
                    'created_by' => $admin->id,
                ])
                ->each(function ($facture) use ($admin) {
                    // 4. Ajouter 3 prestations
                    // On s'assure que le total_ht de la facture est cohérent avec les prestations
                    Prestation::factory()->count(3)->create([
                        'facture_id' => $facture->id,
                        'total_ht' => $facture->total_ht / 3
                    ]);

                    // 5. Simuler un paiement aléatoire (50% de chance)
                    if (rand(0, 1)) {
                        $montantAPayer = $facture->total_ttc * (rand(50, 100) / 100);

                        Paiement::factory()->create([
                            'facture_id' => $facture->id,
                            'montant' => $montantAPayer, // Correction du nom de colonne
                            'date_paiement' => now(),
                            'created_by' => $admin->id,
                        ]);

                        // Mise à jour de l'état de la facture
                        $facture->update([
                            'montant_paye' => $montantAPayer,
                            'reste_a_payer' => $facture->total_ttc - $montantAPayer
                        ]);
                    }
                });

            $this->command->info("Progrès : " . (($i + 1) * 500) . " / 20 000 factures générées.");
        }
    }
}
