<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_batch_factures')) {
            return;
        }

        Schema::create('import_batch_factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['import_batch_id', 'facture_id'], 'uq_import_batch_facture');
            $table->index('facture_id', 'idx_import_batch_factures_facture');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_factures');
    }
};
