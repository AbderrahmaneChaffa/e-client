<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('rule_code', 80);
            $table->string('severity', 20)->default('info');
            $table->unsignedInteger('affected_count')->default(0);
            $table->json('sample_ids')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'severity'], 'idx_import_verifications_batch_severity');
            $table->index(['rule_code', 'severity'], 'idx_import_verifications_rule_severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_verifications');
    }
};
