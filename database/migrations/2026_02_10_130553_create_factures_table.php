<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('numero_facture')->unique()->index();
            $table->date('date_facture');
            $table->date('date_mise_en_ligne')->nullable();
            $table->date('date_echeance')->nullable();

            // Infos Paiement
            // CORRECTION : tinyInteger est mieux pour 1, 2, 3... decimal(2,2) ne marche pas pour ça.
            $table->tinyInteger('mode_paiement')->default(1)->comment('1: Virement, 2: Chèque, 3: Espèce');
            $table->string('bordereau')->nullable();

            // Détails
            $table->text('description')->nullable(); // 'text' est mieux que 'string' pour les descriptions longues
            $table->string('pour')->nullable()->comment('Client Final / Beneficiaire');

            // Devises
            $table->enum('devise', ['DA', 'DR', 'EUR'])->default('DA');
            // CORRECTION : Precision de 4 chiffres après la virgule pour les taux (ex: 1.0856)
            $table->decimal('taux_devise', 10, 4)->default(1)->comment('Taux de conversion par rapport au DA');

            // Gestion Annulation
            $table->boolean('annuler')->default(false);
            $table->string('motif_annulation')->nullable();
            $table->date('date_annulation')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();

            // Gestion Impression
            $table->boolean('imprimer')->default(false);
            $table->date('date_impression')->nullable();
            $table->foreignId('imprime_par')->nullable()->constrained('users')->nullOnDelete();

            // Relations
            // CORRECTION : Il faut nullable() pour que 'set null' fonctionne
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // SECURITE COMPTABLE : On ne supprime pas les factures en cascade ! On empêche la suppression du client tant qu'il a des factures (restrict)
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            // On pointe vers l'ESCALE et non plus le NAVIRE directement
            $table->foreignId('escale_id')->nullable()->constrained('escales')->nullOnDelete();
            // Montants (Precision 15, 2 classique pour la finance)
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);

            // Suivi Paiement
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('reste_a_payer', 15, 2)->default(0);

            // Index pour performance
            $table->index(['client_id', 'reste_a_payer']);

            $table->timestamps();
            $table->softDeletes(); // Ajout vital pour la traçabilité (created_at, updated_at, deleted_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
