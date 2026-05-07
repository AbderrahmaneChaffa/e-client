<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'row_hash')) {
                $table->string('row_hash', 32)->nullable()->index();
            }
        });

        Schema::table('prestations', function (Blueprint $table) {
            if (! Schema::hasColumn('prestations', 'row_hash')) {
                $table->string('row_hash', 32)->nullable()->index();
            }
        });

        Schema::table('paiements', function (Blueprint $table) {
            if (! Schema::hasColumn('paiements', 'row_hash')) {
                $table->string('row_hash', 32)->nullable()->index();
            }
        });

        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'created_rows')) {
                $table->unsignedInteger('created_rows')->default(0);
            }

            if (! Schema::hasColumn('import_batches', 'updated_rows')) {
                $table->unsignedInteger('updated_rows')->default(0);
            }

            if (! Schema::hasColumn('import_batches', 'skipped_rows')) {
                $table->unsignedInteger('skipped_rows')->default(0);
            }

            if (! Schema::hasColumn('import_batches', 'force_import')) {
                $table->boolean('force_import')->default(false);
            }

            if (! Schema::hasColumn('import_batches', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            foreach (['metadata', 'force_import', 'skipped_rows', 'updated_rows', 'created_rows'] as $column) {
                if (Schema::hasColumn('import_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('paiements', function (Blueprint $table) {
            if (Schema::hasColumn('paiements', 'row_hash')) {
                $table->dropColumn('row_hash');
            }
        });

        Schema::table('prestations', function (Blueprint $table) {
            if (Schema::hasColumn('prestations', 'row_hash')) {
                $table->dropColumn('row_hash');
            }
        });

        Schema::table('factures', function (Blueprint $table) {
            if (Schema::hasColumn('factures', 'row_hash')) {
                $table->dropColumn('row_hash');
            }
        });
    }
};
