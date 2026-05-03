<?php
// database/seeders/ClientAccountSeeder.php
namespace Database\Seeders;

use App\Models\{Client, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;

class ClientAccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('Password123');
        $created = 0;
        $skipped = 0;

        $this->command->info('Génération des comptes clients…');

        $bar = $this->command->getOutput()->createProgressBar(
            Client::count()
        );
        $bar->start();

        // Traitement par lots de 200 pour éviter la saturation mémoire
        Client::orderBy('id')->chunk(200, function ($clients) use ($password, &$created, &$skipped, $bar) {
            foreach ($clients as $client) {

                // Générer un email unique et propre
                $email = $this->buildEmail($client);

                // Ne pas écraser un compte existant
                $exists = User::where('client_id', $client->id)->exists()
                    || User::where('email', $email)->exists();

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                User::create([
                    'name' => $client->name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'client',
                    'client_id' => $client->id,
                    'email_verified_at' => now(),
                ]);

                $created++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->command->newLine(2);
        $this->command->table(
            ['Résultat', 'Nombre'],
            [
                ['Comptes créés', $created],
                ['Déjà existants', $skipped],
                ['Total clients', $created + $skipped],
            ]
        );
        $this->command->info("Mot de passe par défaut : pass1234");
    }

    private function buildEmail(Client $client): string
    {
        // Nettoyer le nom : accents, espaces, caractères spéciaux
        $slug = Str::slug($client->name, '.');

        // Tronquer si trop long
        $slug = Str::limit($slug, 40, '');

        // Utiliser le code client comme suffixe pour garantir l'unicité
        $code = Str::lower(preg_replace('/[^a-zA-Z0-9]/', '', $client->code_client ?? ''));

        $base = empty($code) ? $slug : "{$slug}.{$code}";
        $email = "{$base}@epo.dz";

        // Si collision, ajouter l'ID client
        if (User::where('email', $email)->exists()) {
            $email = "{$slug}.{$client->id}@epo.dz";
        }

        return $email;
    }
}