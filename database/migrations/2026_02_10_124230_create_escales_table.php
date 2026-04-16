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
        Schema::create('escales', function (Blueprint $table) {
            $table->id();

            // ⭐ On empêche la suppression d'un navire s'il a déjà des escales (restrictOnDelete)
            $table->foreignId('navire_id')->constrained('navires')->restrictOnDelete();

            // ⭐ Identifiant métier de l'escale (ex: 2026-00124)
            $table->string('numero_escale')->unique()->nullable();

            // Dates et Heures
          //  $table->dateTime('eta')->nullable(); // Arrivée prévue
            $table->date('date_arrivee')->nullable(); // Arrivée réelle
            $table->date('date_sortie')->nullable(); // Départ réel

            // Opérations
            $table->string('poste_quai')->nullable();
            $table->string('motif')->nullable();

            // ⭐ Détails supplémentaires très utiles
            $table->string('consignataire')->nullable(); // L'agent maritime qui s'occupe du navire
            $table->decimal('tirant_eau_arrivee', 5, 2)->nullable(); // Le tirant d'eau réel ce jour-là
            //$table->string('statut')->default('prevue'); // prevue, a_quai, partie, annulee

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escales');
    }
};
