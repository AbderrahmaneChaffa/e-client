<?php
// database/migrations/xxxx_update_type_enum_in_import_batches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // MySQL ne supporte pas la modification d'ENUM via Blueprint::change()
        // directement — on utilise une instruction SQL brute.
        DB::statement("
            ALTER TABLE import_batches
            MODIFY COLUMN type ENUM(
                'factures',
                'prestations',
                'paiements',
                'factures_payees',
                'prestations_payees'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Retour à l'ancien ENUM (les lignes avec les nouveaux types seront perdues)
        DB::statement("
            ALTER TABLE import_batches
            MODIFY COLUMN type ENUM(
                'factures',
                'prestations',
                'paiements'
            ) NOT NULL
        ");
    }
};
