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
        Schema::create('navires', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->index();
            $table->string('pavillon')->nullable();
            $table->date('date_arrivee')->nullable();
            $table->date('date_sortie')->nullable();
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
