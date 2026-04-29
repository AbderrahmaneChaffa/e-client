<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Factures ────────────────────────────────────────────────────────
        Schema::table('factures', function (Blueprint $table) {
            // Lookup principal dans tous les imports
            $table->index('numero_facture', 'idx_factures_numero');
            $table->index('client_id', 'idx_factures_client');
            $table->index('annuler', 'idx_factures_annuler');
        });

        // ── Clients ─────────────────────────────────────────────────────────
        Schema::table('clients', function (Blueprint $table) {
            $table->index('code_client', 'idx_clients_code');
        });

        // ── Navires ──────────────────────────────────────────────────────────
        Schema::table('navires', function (Blueprint $table) {
            $table->index('nom', 'idx_navires_nom');
        });

        // ── Prestations ──────────────────────────────────────────────────────
        Schema::table('prestations', function (Blueprint $table) {
            $table->index(['facture_id', 'article'], 'idx_prestations_facture_article');
        });

        // ── Paiements ────────────────────────────────────────────────────────
        Schema::table('paiements', function (Blueprint $table) {
            $table->index(['facture_id', 'recu'], 'idx_paiements_facture_recu');
        });
    }

    public function down(): void
    {
        Schema::table('factures', fn($t) => $t->dropIndex('idx_factures_numero'));
        Schema::table('clients', fn($t) => $t->dropIndex('idx_clients_code'));
        Schema::table('navires', fn($t) => $t->dropIndex('idx_navires_nom'));
        Schema::table('prestations', fn($t) => $t->dropIndex('idx_prestations_facture_article'));
        Schema::table('paiements', fn($t) => $t->dropIndex('idx_paiements_facture_recu'));
    }

};

