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
        // Schema::create('navires', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('nom')->index();
        //     $table->string('pavillon')->nullable();
        //     $table->date('date_arrivee')->nullable();
        //     $table->date('date_sortie')->nullable();
        //     $table->timestamps();
        // });
        Schema::create('navires', function (Blueprint $table) {
            $table->id();

            // Données de base
            $table->string('nom')->default('Inconnu')->index();
            $table->string('numero_imo')->unique()->nullable(); // ⭐ Le vrai identifiant international
            $table->string('pavillon')->nullable();
            $table->string('type_navire')->nullable(); // Ex: Roulier, Vraquier, Porte-conteneurs

            // Dimensions (en mètres)
            $table->decimal('longueur_hors_tout', 8, 2)->nullable(); // LOA
            $table->decimal('largeur', 8, 2)->nullable();
            $table->decimal('tirant_eau_max', 5, 2)->nullable();

            // Capacité et Vétusté
            $table->integer('jauge_brute')->nullable(); // GT / UMS
            $table->integer('annee_construction')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navires');
    }
};
