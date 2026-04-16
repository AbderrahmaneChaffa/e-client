<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();

            // Lien facture
            $table->foreignId('facture_id')->constrained()->cascadeOnDelete();

            // Détails du paiement
            $table->string('recu')->nullable();
            $table->date('date_paiement');
            $table->decimal('montant', 15, 2); // Le montant payé cette fois-ci

            // Mode de ce paiement spécifique (ex: l'acompte en virement, le solde en chèque)
            $table->tinyInteger('mode_paiement')->default(1)->comment('1: Virement, 2: Chèque, 3: Espèce, 4: Versement');

            // Infos Chèque / Virement
            $table->string('numero_cheque')->nullable();
            $table->string('banque')->nullable();
            $table->string('image_recu')->nullable()->comment('Chemin vers scan du chèque ou ordre de virement');

            // Infos complémentaires
            $table->string('facture_anterieur')->nullable()->comment('Ancienne référence ou facture antérieur');
            $table->text('note')->nullable()->comment('Commentaire libre sur ce paiement');

            // Traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Un reçu ne peut régler une facture donnée qu'une seule fois
            $table->unique(['facture_id', 'recu'], 'uq_paiement_facture_recu');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
