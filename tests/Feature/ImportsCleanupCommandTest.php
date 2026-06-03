<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportsCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_keeps_completed_import_files(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('imports/factures/batch-1.xlsx', 'excel-content');

        $batch = ImportBatch::create([
            'type' => 'factures',
            'original_filename' => 'batch-1.xlsx',
            'stored_path' => 'imports/factures/batch-1.xlsx',
            'status' => 'completed',
            'completed_at' => now()->subDays(45),
            'metadata' => [],
        ]);

        $this->artisan('imports:cleanup', [
            '--days' => 30,
            '--dry-run' => true,
        ])->assertExitCode(0);

        Storage::disk('local')->assertExists('imports/factures/batch-1.xlsx');
        $this->assertNull($batch->fresh()->metadata['cleanup_status'] ?? null);
    }

    public function test_force_cleanup_deletes_file_and_marks_batch_cleaned(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('imports/paiements/batch-2.xlsx', 'excel-content');

        $batch = ImportBatch::create([
            'type' => 'paiements',
            'original_filename' => 'batch-2.xlsx',
            'stored_path' => 'imports/paiements/batch-2.xlsx',
            'status' => 'completed',
            'completed_at' => now()->subDays(45),
            'metadata' => [],
        ]);

        $this->artisan('imports:cleanup', [
            '--days' => 30,
            '--force' => true,
        ])->assertExitCode(0);

        Storage::disk('local')->assertMissing('imports/paiements/batch-2.xlsx');

        $fresh = $batch->fresh();
        $this->assertSame('cleaned', $fresh->metadata['cleanup_status'] ?? null);
        $this->assertSame('automatic', $fresh->metadata['cleanup_mode'] ?? null);
    }

    public function test_audit_storage_reports_orphans(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('imports/factures/orphan-file.xlsx', 'orphan');

        ImportBatch::create([
            'type' => 'factures',
            'original_filename' => 'missing.xlsx',
            'stored_path' => 'imports/factures/missing.xlsx',
            'status' => 'completed',
            'completed_at' => now()->subDays(45),
            'metadata' => [],
        ]);

        $this->artisan('imports:audit-storage')
            ->expectsOutputToContain('Lots complets sans fichier')
            ->expectsOutputToContain('Fichiers sur disque sans lot')
            ->assertExitCode(1);
    }
}
