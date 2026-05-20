<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prestations') || Schema::hasIndex('prestations', 'uq_prestations_facture_article')) {
            return;
        }

        $duplicates = DB::table('prestations')
            ->select('facture_id', 'article', DB::raw('COUNT(*) as total'))
            ->groupBy('facture_id', 'article')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Impossible de creer uq_prestations_facture_article: des doublons existent deja. '.
                'Lancez php artisan epo:diagnose-import-db puis nettoyez les doublons avant de migrer.'
            );
        }

        Schema::table('prestations', function (Blueprint $table) {
            $table->unique(['facture_id', 'article'], 'uq_prestations_facture_article');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prestations') || ! Schema::hasIndex('prestations', 'uq_prestations_facture_article')) {
            return;
        }

        Schema::table('prestations', function (Blueprint $table) {
            $table->dropUnique('uq_prestations_facture_article');
        });
    }
};
