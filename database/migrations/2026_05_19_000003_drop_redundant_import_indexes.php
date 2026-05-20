<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('factures', 'idx_factures_numero');
        $this->dropIndexIfExists('clients', 'idx_clients_code');
        $this->dropIndexIfExists('prestations', 'idx_prestations_facture_article');
        $this->dropIndexIfExists('paiements', 'idx_paiements_facture_recu');
        $this->dropIndexIfExists('navires', 'idx_navires_nom');
    }

    public function down(): void
    {
        if (Schema::hasTable('factures') && ! Schema::hasIndex('factures', 'idx_factures_numero')) {
            Schema::table('factures', fn (Blueprint $table) => $table->index('numero_facture', 'idx_factures_numero'));
        }

        if (Schema::hasTable('clients') && ! Schema::hasIndex('clients', 'idx_clients_code')) {
            Schema::table('clients', fn (Blueprint $table) => $table->index('code_client', 'idx_clients_code'));
        }

        if (Schema::hasTable('prestations') && ! Schema::hasIndex('prestations', 'idx_prestations_facture_article')) {
            Schema::table('prestations', fn (Blueprint $table) => $table->index(['facture_id', 'article'], 'idx_prestations_facture_article'));
        }

        if (Schema::hasTable('paiements') && ! Schema::hasIndex('paiements', 'idx_paiements_facture_recu')) {
            Schema::table('paiements', fn (Blueprint $table) => $table->index(['facture_id', 'recu'], 'idx_paiements_facture_recu'));
        }

        if (Schema::hasTable('navires') && ! Schema::hasIndex('navires', 'idx_navires_nom')) {
            Schema::table('navires', fn (Blueprint $table) => $table->index('nom', 'idx_navires_nom'));
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
