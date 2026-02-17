<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Identification unique pour l'import Excel
            $table->string('code_client')->unique()->comment('Reference unique importée');

            // Informations légales
            $table->string('name')->index(); // Indexé pour la recherche textuelle
            $table->text('adresse')->nullable(); // 'text' est plus sûr pour les longues adresses

            // Identifiants Fiscaux (Algérie context)
            // On indexe ces champs car on fait souvent des recherches par NIF/RC
            $table->string('rc')->nullable()->index()->comment('Registre de Commerce');
            $table->string('nif')->nullable()->index()->comment('Numéro Identification Fiscale');
            $table->string('nis')->nullable()->comment('Numéro Identification Statistique');
            $table->string('ai')->nullable()->comment('Article d\'Imposition');

            // Contacts (Optionnel mais recommandé)
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Vital pour ne pas perdre l'historique
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
