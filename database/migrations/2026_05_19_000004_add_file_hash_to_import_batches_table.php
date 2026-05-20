<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_batches')) {
            return;
        }

        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('stored_path');
            }

            if (! Schema::hasIndex('import_batches', 'idx_import_batches_sync_lookup')) {
                $table->index(['type', 'file_hash', 'status', 'completed_at'], 'idx_import_batches_sync_lookup');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('import_batches')) {
            return;
        }

        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasIndex('import_batches', 'idx_import_batches_sync_lookup')) {
                $table->dropIndex('idx_import_batches_sync_lookup');
            }

            if (Schema::hasColumn('import_batches', 'file_hash')) {
                $table->dropColumn('file_hash');
            }
        });
    }
};
