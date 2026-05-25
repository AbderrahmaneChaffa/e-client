<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_diffs')) {
            Schema::create('import_diffs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
                $table->foreignId('facture_id')->nullable()->constrained('factures')->cascadeOnDelete();
                $table->string('entity_type', 32);
                $table->string('entity_key')->nullable();
                $table->string('change_type', 32);
                $table->string('severity', 20)->default('info');
                $table->string('label');
                $table->json('differences')->nullable();
                $table->json('context')->nullable();
                $table->timestamps();

                $table->index(['facture_id', 'created_at'], 'idx_import_diffs_facture_created');
                $table->index(['import_batch_id', 'change_type'], 'idx_import_diffs_batch_change');
                $table->index(['entity_type', 'entity_key'], 'idx_import_diffs_entity_key');
            });
        }

        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'import_diff_status')) {
                $table->string('import_diff_status', 32)->nullable()->index();
            }

            if (! Schema::hasColumn('factures', 'last_import_diff_type')) {
                $table->string('last_import_diff_type', 32)->nullable()->index();
            }

            if (! Schema::hasColumn('factures', 'import_diff_count')) {
                $table->unsignedInteger('import_diff_count')->default(0);
            }

            if (! Schema::hasColumn('factures', 'import_diff_summary')) {
                $table->json('import_diff_summary')->nullable();
            }

            if (! Schema::hasColumn('factures', 'last_import_batch_id')) {
                $table->unsignedBigInteger('last_import_batch_id')->nullable()->index();
            }

            if (! Schema::hasColumn('factures', 'last_import_diff_at')) {
                $table->timestamp('last_import_diff_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            foreach ([
                'last_import_diff_at',
                'last_import_batch_id',
                'import_diff_summary',
                'import_diff_count',
                'last_import_diff_type',
                'import_diff_status',
            ] as $column) {
                if (Schema::hasColumn('factures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('import_diffs');
    }
};
