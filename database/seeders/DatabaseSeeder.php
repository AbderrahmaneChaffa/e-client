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
        $admin = User::where('email', 'admin@e-client.com')->first();

        // 2. Créer 1000 clients
        // $this->command->info('Création des clients...');
        // Client::factory()->count(1000)->create([
        //     'created_by' => $admin->id
        // ]);
        // $clientIds = Client::pluck('id');

        // 3. Créer 500 Navires
        // $this->command->info('Création des navires...');
        // $navires = Navire::factory()->count(500)->create();

        // $this->command->info('Génération de 20 000 factures avec logique métier stricte...');
        // Boucle par lots pour la performance
        // for ($i = 0; $i < 40; $i++) {
        //     Facture::factory()
        //         ->count(500)
        //         ->create([
        //             'client_id' => fn() => $clientIds->random(),
        //             'navire_id' => fn() => $navires->random()->id,
        //             'created_by' => $admin->id,
        //             'total_ht' => 0,  // On initialise à 0 pour calculer après
        //             'total_tva' => 0,
        //             'total_ttc' => 0,
        //             'annuler' => false,
        //         ])
        //         ->each(function ($facture) use ($admin) {

        //             // --- ETAPE 1 : CRÉATION DES PRESTATIONS RÉELLES ---
        //             // On crée entre 2 et 5 prestations par facture
        //             $nbPrestations = rand(2, 5);
        //             $sommeHT = 0;

        //             $prestations = Prestation::factory()->count($nbPrestations)->create([
        //                 'facture_id' => $facture->id,
        //             ]);

        //             // Calcul de la somme réelle des prestations créées
        //             $sommeHT = $prestations->sum('total_ht');

        //             // --- ETAPE 2 : CALCULS FINANCIERS (Stricts) ---
        //             $tauxTVA = 0.19; // 19%
        //             $montantTVA = $sommeHT * $tauxTVA;
        //             $totalTTC = $sommeHT + $montantTVA;

        //             // --- ETAPE 3 : MISE À JOUR DE LA FACTURE ---
        //             $estAnnulee = rand(1, 100) <= 10;

        //             if ($estAnnulee) {
        //                 $facture->update([
        //                     'total_ht' => $sommeHT,
        //                     'total_tva' => $montantTVA,
        //                     'total_ttc' => $totalTTC,
        //                     'annuler' => true,
        //                     'motif_annulation' => 'Erreur de saisie',
        //                     'date_annulation' => now(),
        //                     'annule_par' => $admin->id,
        //                     'montant_paye' => 0,
        //                     'reste_a_payer' => $totalTTC
        //                 ]);
        //             } else {
        //                 // Facture Valide
        //                 $facture->update([
        //                     'total_ht' => $sommeHT,
        //                     'total_tva' => $montantTVA,
        //                     'total_ttc' => $totalTTC,
        //                     'imprimer' => true,
        //                     'date_impression' => now()->subDays(rand(1, 30)),
        //                 ]);

        //                 // --- ETAPE 4 : GESTION DES PAIEMENTS COHÉRENTS ---
        //                 if (rand(1, 100) <= 60) {
        //                     // On paye soit tout, soit une partie
        //                     $pourcentagePaye = rand(50, 100) / 100;
        //                     $montantAPayer = round($totalTTC * $pourcentagePaye, 2);

        //                     Paiement::factory()->create([
        //                         'facture_id' => $facture->id,
        //                         'montant' => $montantAPayer,
        //                         'created_by' => $admin->id,
        //                     ]);

        //                     $facture->update([
        //                         'montant_paye' => $montantAPayer,
        //                         'reste_a_payer' => $totalTTC - $montantAPayer
        //                     ]);
        //                 } else {
        //                     $facture->update([
        //                         'montant_paye' => 0,
        //                         'reste_a_payer' => $totalTTC
        //                     ]);
        //                 }
        //             }
        //         });

        //     $this->command->info("Progrès : " . (($i + 1) * 500) . " / 20 000 factures.");
        // }
    }
}
