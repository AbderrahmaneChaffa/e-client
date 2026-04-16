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
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained()->onDelete('cascade');
            $table->string('article');
            $table->string('libelle');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('taux_ht', 15, 2)->default(0);
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('taux_tva', 15, 2)->default(0);
            $table->decimal('total_tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);
             // Relations
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
