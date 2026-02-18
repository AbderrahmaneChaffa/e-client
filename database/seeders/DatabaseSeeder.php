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
        // 1. Créer l'admin et client
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@e-client.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $client = User::factory()->create([
            'name' => 'client',
            'email' => 'client@e-client.com',
            'password' => bcrypt('password'),
            'role' => 'client',
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

        $this->command->info('Génération de 20 000 factures avec logique métier stricte...');

        // Boucle par lots de 500
        for ($i = 0; $i < 40; $i++) {
            Facture::factory()
                ->count(500)
                ->create([
                    'client_id' => fn() => $clientIds->random(),
                    'navire_id' => fn() => $navires->random()->id,
                    'created_by' => $admin->id,
                    // On initialise tout à "brouillon" pour le modifier dans le each()
                    'annuler' => false,
                    'imprimer' => false,
                    'montant_paye' => 0,
                ])
                ->each(function ($facture) use ($admin) {
                    
                    // --- ETAPE 1 : PRESTATIONS (Toujours présentes) ---
                    Prestation::factory()->count(3)->create([
                        'facture_id' => $facture->id,
                        'total_ht' => $facture->total_ht / 3
                    ]);

                    // --- ETAPE 2 : LOGIQUE METIER (Annulation vs Paiement) ---
                    
                    // On décide du sort de la facture : 10% de chance d'être annulée
                    $estAnnulee = rand(1, 100) <= 10;

                    if ($estAnnulee) {
                        // CAS 1 : FACTURE ANNULÉE
                        $facture->update([
                            'annuler' => true,
                            'imprimer' => false, // Impossible d'imprimer une annulée
                            'motif_annulation' => 'Erreur de saisie / Doublon',
                            'date_annulation' => now(),
                            'annule_par' => $admin->id,
                            'reste_a_payer' => $facture->total_ttc, // La dette reste affichée techniquement, mais exclue par les requêtes
                            'montant_paye' => 0 
                        ]);
                        // STOP ICI : Pas de paiement pour les annulées

                    } else {
                        // CAS 2 : FACTURE VALIDE (ET IMPRIMÉE)
                        $facture->update([
                            'annuler' => false,
                            'imprimer' => true, // La facture est validée
                            'date_impression' => now()->subDays(rand(1, 30)),
                            'imprime_par' => $admin->id,
                        ]);

                        // Gestion des paiements (Seulement si non annulée)
                        // 60% de chance d'être payée (totalement ou partiellement)
                        if (rand(1, 100) <= 60) {
                            $montantAPayer = $facture->total_ttc * (rand(50, 100) / 100);

                            Paiement::factory()->create([
                                'facture_id' => $facture->id,
                                'montant' => $montantAPayer,
                                'date_paiement' => now(),
                                'created_by' => $admin->id,
                                'mode_paiement' => rand(1, 2) // Chèque ou Virement
                            ]);

                            $facture->update([
                                'montant_paye' => $montantAPayer,
                                'reste_a_payer' => $facture->total_ttc - $montantAPayer
                            ]);
                        }
                    }
                });

            $this->command->info("Progrès : " . (($i + 1) * 500) . " / 20 000 factures traitées.");
        }
    }
}