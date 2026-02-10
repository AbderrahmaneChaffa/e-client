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
            $table->string('numero_facture')->unique()->index();
            $table->date('date_facture');
            $table->date('date_mise_en_ligne')->nullable();
            $table->date('date_echeance')->nullable();

            // Relations
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('navire_id')->nullable()->constrained()->onDelete('set null');

            // Montants (Utilisation de decimal pour la précision financière)
            $table->decimal('total_ht', 15, 2);
            $table->decimal('total_tva', 15, 2);
            $table->decimal('total_ttc', 15, 2);

            // État de paiement pour éviter des calculs lourds à chaque affichage
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('reste_a_payer', 15, 2);

            $table->index(['client_id', 'reste_a_payer']); // Index composé pour filtrer les impayés par client instantanément
            $table->timestamps();
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
