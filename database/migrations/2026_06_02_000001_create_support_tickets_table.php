<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->nullOnDelete();
            $table->string('sujet', 255);
            $table->text('message');
            $table->enum('statut', ['ouvert', 'en_cours', 'resolu'])->default('ouvert');
            $table->enum('priorite', ['normal', 'urgent'])->default('normal');
            $table->text('reponse_admin')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index('facture_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
