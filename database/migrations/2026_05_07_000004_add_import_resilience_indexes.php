<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->index();
            }

        });

        Schema::table('paiements', function (Blueprint $table) {
            if (! Schema::hasIndex('paiements', 'idx_paiements_recu')) {
                $table->index('recu', 'idx_paiements_recu');
            }
        });

        Schema::table('navires', function (Blueprint $table) {
            if (! Schema::hasIndex('navires', 'idx_navires_nom_pavillon')) {
                $table->index(['nom', 'pavillon'], 'idx_navires_nom_pavillon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('navires', function (Blueprint $table) {
            if (Schema::hasIndex('navires', 'idx_navires_nom_pavillon')) {
                $table->dropIndex('idx_navires_nom_pavillon');
            }
        });

        Schema::table('paiements', function (Blueprint $table) {
            if (Schema::hasIndex('paiements', 'idx_paiements_recu')) {
                $table->dropIndex('idx_paiements_recu');
            }
        });

        Schema::table('factures', function (Blueprint $table) {
            if (Schema::hasColumn('factures', 'needs_review')) {
                $table->dropColumn('needs_review');
            }
        });
    }
};
