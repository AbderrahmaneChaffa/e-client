<?php

namespace Tests\Feature;

use App\Imports\FacturesImport;
use App\Imports\FacturesPayeesImport;
use App\Imports\PrestationsImport;
use App\Jobs\ProcessImportJob;
use App\Jobs\VerifyImportJob;
use App\Models\Client;
use App\Models\Facture;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\ImportDeltaService;
use App\Services\ImportVerificationService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factures_import_skips_identical_rows_after_first_sync(): void
    {
        $row = $this->factureRow();

        $firstBatch = $this->batch('factures');
        (new FacturesImport($firstBatch))->collection(new Collection([$row]));

        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('navires', 1);
        $this->assertDatabaseCount('escales', 1);
        $this->assertDatabaseCount('factures', 1);
        $this->assertDatabaseHas('factures', [
            'numero_facture' => 'FAC-OPT-1',
            'total_ttc' => 1190,
        ]);

        $secondBatch = $this->batch('factures');
        (new FacturesImport($secondBatch))->collection(new Collection([$row]));

        $this->assertDatabaseCount('factures', 1);
        $this->assertSame(0, (int) $secondBatch->fresh()->created_rows);
        $this->assertSame(1, (int) $secondBatch->fresh()->skipped_rows);
    }

    public function test_factures_import_records_and_clears_invoice_differences(): void
    {
        $client = Client::factory()->create(['code_client' => 'CLT-OPT']);
        $this->createFacture($client->id, 'FAC-OPT-1', 1000, 190, 1190);
        $row = $this->factureRow();

        (new FacturesImport($this->batch('factures')))->collection(new Collection([$row]));

        $facture = Facture::where('numero_facture', 'FAC-OPT-1')->firstOrFail();

        $this->assertSame('modified', $facture->import_diff_status);
        $this->assertSame(1, (int) $facture->import_diff_count);
        $this->assertDatabaseHas('import_diffs', [
            'facture_id' => $facture->id,
            'entity_type' => 'facture',
            'change_type' => 'modified',
        ]);

        (new FacturesImport($this->batch('factures')))->collection(new Collection([$row]));

        $facture->refresh();

        $this->assertNull($facture->import_diff_status);
        $this->assertSame(0, (int) $facture->import_diff_count);
    }

    public function test_import_diff_summary_refresh_never_creates_partial_factures(): void
    {
        $service = app(ImportDeltaService::class);
        $method = new \ReflectionMethod($service, 'updateExistingFactureSummaries');
        $method->setAccessible(true);

        $method->invoke($service, [[
            'id' => 999999,
            'import_diff_status' => 'inconsistent',
            'last_import_diff_type' => 'facture',
            'import_diff_count' => 1,
            'import_diff_summary' => '[{"label":"Total facture incoherent"}]',
            'last_import_batch_id' => 123,
            'last_import_diff_at' => now(),
            'updated_at' => now(),
        ]]);

        $this->assertDatabaseMissing('factures', ['id' => 999999]);
    }

    public function test_prestations_import_upserts_existing_business_key_without_duplicate(): void
    {
        $client = Client::factory()->create(['code_client' => 'CLT-OPT']);
        Facture::create([
            'numero_facture' => 'FAC-OPT-2',
            'date_facture' => '2026-05-01',
            'client_id' => $client->id,
            'escale_id' => null,
            'mode_paiement' => 1,
            'devise' => 'DA',
            'taux_devise' => 1,
            'annuler' => 0,
            'total_ht' => 100,
            'total_tva' => 19,
            'total_ttc' => 119,
            'reste_a_payer' => 119,
            'created_by' => null,
        ]);

        (new PrestationsImport($this->batch('prestations')))->collection(new Collection([
            $this->prestationRow('FAC-OPT-2', 'ART-1', 'Pilotage', 100),
        ]));

        (new PrestationsImport($this->batch('prestations')))->collection(new Collection([
            $this->prestationRow('FAC-OPT-2', 'ART-1', 'Pilotage modifie', 250),
        ]));

        $this->assertDatabaseCount('prestations', 1);
        $this->assertDatabaseHas('prestations', [
            'article' => 'ART-1',
            'libelle' => 'Pilotage modifie',
            'total_ht' => 250,
        ]);
    }

    public function test_factures_import_accepts_textual_cancellation_flags(): void
    {
        $row = $this->factureRow();
        $row->forget('annule');
        $row->put('ANNUL'."\u{00C9}", 'Annul'."\u{00E9}".'e');

        (new FacturesImport($this->batch('factures')))->collection(new Collection([$row]));

        $facture = Facture::where('numero_facture', 'FAC-OPT-1')->firstOrFail();

        $this->assertTrue($facture->annuler);
        $this->assertSame(1, Facture::canceled()->count());
    }

    public function test_factures_payees_import_does_not_force_canceled_flag_to_zero(): void
    {
        $row = $this->factureRow();
        $row->put('annule', 'cancelled');

        (new FacturesPayeesImport($this->batch('factures')))->collection(new Collection([$row]));

        $this->assertTrue(Facture::where('numero_facture', 'FAC-OPT-1')->firstOrFail()->annuler);
    }

    public function test_factures_payees_import_preserves_canceled_flag_when_annule_column_is_missing(): void
    {
        $client = Client::factory()->create(['code_client' => 'CLT-EXISTING']);
        $this->createFacture($client->id, 'FAC-OPT-1', 1000, 190, 1190)
            ->update(['annuler' => true]);

        $row = $this->factureRow();
        $row->forget('annule');

        (new FacturesPayeesImport($this->batch('factures')))->collection(new Collection([$row]));

        $this->assertTrue(Facture::where('numero_facture', 'FAC-OPT-1')->firstOrFail()->annuler);
    }

    public function test_import_verification_is_scoped_to_touched_factures(): void
    {
        $client = Client::factory()->create(['code_client' => 'CLT-SCOPE']);
        $touched = $this->createFacture($client->id, 'FAC-SCOPE-1', 1000, 190, 1190);
        $untouchedInvalid = $this->createFacture($client->id, 'FAC-SCOPE-2', 1000, 190, 1200);
        $batch = $this->batch('factures');

        DB::table('import_batch_factures')->insert([
            'import_batch_id' => $batch->id,
            'facture_id' => $touched->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = app(ImportVerificationService::class)->verify($batch, [$batch->id]);

        $this->assertSame(0, $summary['critical']);
        $this->assertDatabaseMissing('import_verifications', [
            'rule_code' => 'tva_coherence',
            'import_batch_id' => $batch->id,
        ]);
        $this->assertSame('ok', $untouchedInvalid->fresh()->verification_status);
    }

    public function test_admin_preview_accepts_real_excel_file_with_disabled_excel_transactions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $file = $this->xlsxUpload('factures-preview.xlsx', [$this->factureRow()->all()]);

        $response = $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.imports.preview'), [
                'files' => [$file],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('summary.files', 1)
            ->assertJsonPath('summary.rows', 1)
            ->assertJsonPath('files.0.type', 'factures');
    }

    public function test_identical_completed_file_is_skipped_without_reprocessing_excel(): void
    {
        $previous = ImportBatch::create([
            'type' => 'prestations',
            'original_filename' => 'prestations.xlsx',
            'stored_path' => 'imports/prestations/previous.xlsx',
            'file_hash' => 'same-file-hash',
            'status' => 'completed',
            'total_rows' => 42,
            'processed_rows' => 42,
            'completed_at' => now()->subMinute(),
            'force_import' => false,
            'created_by' => null,
        ]);

        $current = ImportBatch::create([
            'type' => 'prestations',
            'original_filename' => 'prestations.xlsx',
            'stored_path' => 'imports/prestations/current.xlsx',
            'file_hash' => 'same-file-hash',
            'status' => 'pending',
            'force_import' => false,
            'created_by' => null,
        ]);

        (new ProcessImportJob($current->id))->handle();

        $current->refresh();

        $this->assertSame('completed', $current->status);
        $this->assertSame(42, (int) $current->total_rows);
        $this->assertSame(42, (int) $current->processed_rows);
        $this->assertSame(42, (int) $current->skipped_rows);
        $this->assertSame(0, (int) $current->created_rows);
        $this->assertSame(0, (int) $current->updated_rows);
        $this->assertSame('file_hash_skip', $current->metadata['sync_mode'] ?? null);
        $this->assertSame($previous->id, $current->metadata['skipped_duplicate_of'] ?? null);
    }

    public function test_progress_many_returns_progress_and_history_payloads(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = ImportBatch::create([
            'type' => 'factures',
            'original_filename' => 'factures.xlsx',
            'stored_path' => 'imports/factures/factures.xlsx',
            'status' => 'processing',
            'total_rows' => 100,
            'processed_rows' => 25,
            'force_import' => false,
            'created_by' => $admin->id,
            'started_at' => now()->subMinute(),
        ]);

        $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->get(route('admin.imports.progress-many', ['ids' => (string) $batch->id]))
            ->assertOk()
            ->assertJsonPath("progress.{$batch->id}.percentage", 25)
            ->assertJsonPath('history.0.id', $batch->id)
            ->assertJsonPath('history.0.type_label', 'Factures');
    }

    public function test_factures_index_statistics_count_canceled_invoices(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = Client::factory()->create(['code_client' => 'CLT-CANCEL']);

        $paid = $this->createFacture($client->id, 'FAC-CANCEL-1', 100, 19, 119);
        $paid->update(['reste_a_payer' => 0]);
        $this->createFacture($client->id, 'FAC-CANCEL-2', 200, 38, 238);
        $canceled = $this->createFacture($client->id, 'FAC-CANCEL-3', 300, 57, 357);
        $canceled->update(['annuler' => true]);

        $this
            ->actingAs($admin)
            ->get(route('admin.factures.index'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) =>
                $stats['count_payees'] === 1
                && $stats['count_impayees'] === 1
                && $stats['count_annulees'] === 1
            );
    }

    public function test_admin_dashboard_counts_canceled_invoices(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = Client::factory()->create(['code_client' => 'CLT-DASH-CANCEL']);
        $canceled = $this->createFacture($client->id, 'FAC-DASH-CANCEL-1', 100, 19, 119);
        $canceled->update(['annuler' => true]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('canceledInvoices', 1);
    }

    public function test_admin_can_launch_global_verification_and_read_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.imports.verify-global'))
            ->assertOk()
            ->assertJsonPath('status.status', 'completed')
            ->assertJsonPath('status.score', 100);

        $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->get(route('admin.imports.verify-global.status'))
            ->assertOk()
            ->assertJsonPath('status.status', 'completed')
            ->assertJsonPath('health.score', 100);
    }

    public function test_global_verification_rejects_duplicate_launch_while_queued(): void
    {
        Queue::fake();
        Cache::forget(VerifyImportJob::GLOBAL_LOCK_CACHE_KEY);
        Cache::forget(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.imports.verify-global'))
            ->assertOk()
            ->assertJsonPath('status.status', 'queued');

        Queue::assertPushed(VerifyImportJob::class);

        $this
            ->actingAs($admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('admin.imports.verify-global'))
            ->assertStatus(409)
            ->assertJsonPath('status.status', 'queued');
    }

    private function batch(string $type): ImportBatch
    {
        return ImportBatch::create([
            'type' => $type,
            'original_filename' => "{$type}.xlsx",
            'stored_path' => "imports/{$type}/test.xlsx",
            'status' => 'pending',
            'force_import' => false,
            'created_by' => null,
        ]);
    }

    private function factureRow(): Collection
    {
        return new Collection([
            'facture' => 'FAC-OPT-1',
            'date' => '01/05/2026',
            'code_client' => 'CLT-OPT',
            'nom_client' => 'Client Optimise',
            'adresse' => 'Port',
            'rc' => 'RC1',
            'nis' => 'NIS1',
            'ai' => 'AI1',
            'nif' => 'NIF1',
            'paiement' => '1',
            'entree' => '01/05/2026',
            'sortie' => '02/05/2026',
            'bordereau' => 'B-1',
            'description' => 'Facture test',
            'navire' => 'Navire A',
            'pavillon' => 'DZ',
            'pour' => 'Client final',
            'total_ht' => '1000',
            'total_tva' => '190',
            'total_ttc' => '1190',
            'reste' => '1190',
            'devise' => 'DA',
            'taux_devise' => '1',
            'user' => 'admin',
            'annule' => '0',
        ]);
    }

    private function prestationRow(string $facture, string $article, string $libelle, int $totalHt): Collection
    {
        return new Collection([
            'facture' => $facture,
            'article' => $article,
            'libelle' => $libelle,
            'quantite' => '1',
            'prix' => (string) $totalHt,
            'taux_ht' => '0',
            'total_ht' => (string) $totalHt,
            'total_tva' => '0',
            'total_ttc' => (string) $totalHt,
        ]);
    }

    private function createFacture(int $clientId, string $numero, int $ht, int $tva, int $ttc): Facture
    {
        return Facture::create([
            'numero_facture' => $numero,
            'date_facture' => '2026-05-01',
            'client_id' => $clientId,
            'escale_id' => null,
            'mode_paiement' => 1,
            'devise' => 'DA',
            'taux_devise' => 1,
            'annuler' => 0,
            'total_ht' => $ht,
            'total_tva' => $tva,
            'total_ttc' => $ttc,
            'reste_a_payer' => $ttc,
            'created_by' => null,
        ]);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function xlsxUpload(string $name, array $rows): UploadedFile
    {
        $path = storage_path('framework/testing/'.$name);
        File::ensureDirectoryExists(dirname($path));

        $headers = array_keys($rows[0] ?? []);
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([$headers, ...array_map('array_values', $rows)]);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
