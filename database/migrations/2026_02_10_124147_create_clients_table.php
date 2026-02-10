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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index(); // Indexé pour les recherches rapides lors de l'import Excel
            $table->string('name');
            $table->string('nis')->nullable();
            $table->string('rc')->nullable();
            $table->string('ai')->nullable();
            // // On ajoute le lien vers la table clients
            // $table->foreignId('client_id')
            //     ->nullable()
            //     ->constrained('clients')
            //     ->onDelete('cascade');

            // // Optionnel : un champ pour définir le rôle rapidement si vous n'utilisez pas Spatie
            // $table->string('role')->default('client');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
