<?php

namespace App\Imports;

use App\Imports\Concerns\ParsesExcelData;
use App\Models\{Client, Facture, ImportBatch};
use App\Services\ImportRowHasher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Maatwebsite\Excel\Concerns\{
    Importable,
    SkipsErrors,
    SkipsFailures,
    SkipsOnError,
    SkipsOnFailure,
    ToCollection,
    WithChunkReading,
    WithCustomValueBinder,
    WithHeadingRow,
};
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class PaiementsImport extends StringValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithCustomValueBinder,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures, ParsesExcelData;

    private int $localCount = 0;
    private int $skippedCount = 0;
    private array $factureCache = [];
    private array $missingFactures = [];

    public function __construct(private readonly ImportBatch $batch)
    {
    }

    public function collection(Collection $rows): void
    {
        if ($this->batch->processed_rows === 0) {
            Log::channel('imports')->debug('PAIEMENTS colonnes détectées', [
                'keys' => array_keys($rows->first()?->toArray() ?? []),
            ]);
        }

        $numeros = $rows
            ->map(fn ($row) => trim((string) $this->cellValue($row, 'facture', '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $newNumeros = array_diff($numeros, array_keys($this->factureCache));

        if ($newNumeros !== []) {
            Facture::whereIn('numero_facture', $newNumeros)
                ->select('id', 'numero_facture')
                ->get()
                ->each(fn ($facture) => $this->factureCache[$facture->numero_facture] = $facture->id);
        }

        $factureIds = [];
        $recus = [];

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            $recu = trim((string) $this->cellValue($row, 'recu', ''));

            if ($numero !== '' && $recu !== '' && isset($this->factureCache[$numero])) {
                $factureIds[] = $this->factureCache[$numero];
                $recus[] = $recu;
            }
        }

        $existingHashes = [];
        if ($factureIds !== [] && $recus !== []) {
            DB::table('paiements')
                ->whereIn('facture_id', array_values(array_unique($factureIds)))
                ->whereIn('recu', array_values(array_unique($recus)))
                ->select('facture_id', 'recu', 'row_hash')
                ->get()
                ->each(function ($row) use (&$existingHashes) {
                    $existingHashes[$row->facture_id.'|'.$row->recu] = $row->row_hash ?? '__EXISTS_NO_HASH__';
                });
        }

        $now = now()->toDateTimeString();
        $userId = $this->batch->created_by;
        $paiements = [];
        $soldesMAJ = [];
        $createdRows = 0;
        $updatedRows = 0;
        $unchangedRows = 0;
        $missingRows = 0;

        foreach ($rows as $row) {
            $numero = trim((string) $this->cellValue($row, 'facture', ''));
            $recu = trim((string) $this->cellValue($row, 'recu', ''));

            if ($numero === '') {
                continue;
            }

            $factureId = $this->factureCache[$numero] ?? null;

            if (! $factureId) {
                $factureId = $this->createGhostFacture($numero, $row, $now, $userId);

                if (! $factureId) {
                    $this->missingFactures[$numero] = true;
                    $this->skippedCount++;
                    $missingRows++;
                    continue;
                }

                $this->factureCache[$numero] = $factureId;
            }

            $rowHash = ImportRowHasher::hash($this->batch->type, $row);
            $existingKey = $factureId.'|'.$recu;
            $existingHash = $existingHashes[$existingKey] ?? null;

            if (
                ! $this->batch->force_import
                && $existingHash
                && $existingHash !== '__EXISTS_NO_HASH__'
                && hash_equals((string) $existingHash, $rowHash)
            ) {
                $unchangedRows++;
                continue;
            }

            $existingHash ? $updatedRows++ : $createdRows++;

            $montant = $this->parseAmount($this->cellValue($row, 'paye', '0'));
            $reste = $this->parseAmount($this->cellValue($row, 'reste', '0'));

            $paiements[] = [
                'facture_id' => $factureId,
                'recu' => $recu,
                'montant' => $montant,
                'date_paiement' => $this->parseDate($this->cellValue($row, 'date', '')),
                'numero_cheque' => trim((string) $this->cellValue($row, 'cheque', '')),
                'banque' => trim((string) $this->cellValue($row, 'banque', '')),
                'facture_anterieur' => trim((string) $this->cellValue($row, 'facture_anterieur', '')),
                'row_hash' => $rowHash,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $soldesMAJ[$factureId] = $reste;
            $this->localCount++;
        }

        if ($paiements !== []) {
            DB::table('paiements')->upsert(
                $paiements,
                ['facture_id', 'recu'],
                [
                    'montant',
                    'date_paiement',
                    'numero_cheque',
                    'banque',
                    'facture_anterieur',
                    'row_hash',
                    'updated_at',
                ]
            );
        }

        if ($soldesMAJ !== []) {
            $cases = '';
            $ids = [];
            $bindings = [];

            foreach ($soldesMAJ as $factureId => $reste) {
                $cases .= ' WHEN ? THEN ?';
                $bindings[] = $factureId;
                $bindings[] = $reste;
                $ids[] = $factureId;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $allBindings = array_merge($bindings, $ids);

            DB::statement(
                "UPDATE factures SET reste_a_payer = CASE id {$cases} END WHERE id IN ({$placeholders})",
                $allBindings
            );
        }

        $processedRows = count($paiements) + $unchangedRows + $missingRows;
        Cache::increment("import_batch_{$this->batch->id}", $processedRows);
        $this->batch->increment('processed_rows', $processedRows);
        $this->batch->increment('created_rows', $createdRows);
        $this->batch->increment('updated_rows', $updatedRows);
        $this->batch->increment('skipped_rows', $unchangedRows);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function logMissingFactures(): void
    {
        if ($this->missingFactures === []) {
            return;
        }

        $count = count($this->missingFactures);
        $samples = array_slice(array_keys($this->missingFactures), 0, 20);

        Log::channel('imports')->warning("PaiementsImport [batch #{$this->batch->id}] missing invoices", [
            'samples' => $samples,
            'total' => $count,
        ]);

        $this->batch->increment('failed_rows', $this->skippedCount);
    }

    public function getLocalCount(): int
    {
        return $this->localCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    private function createGhostFacture(string $numero, mixed $row, string $now, ?int $userId): ?int
    {
        $codeClient = trim((string) $this->cellValue($row, 'code_client', ''));
        $totalTtc = $this->parseAmount($this->cellValue($row, 'total_ttc', '0'));

        if ($codeClient === '' || $totalTtc <= 0) {
            return null;
        }

        $client = Client::firstOrCreate(
            ['code_client' => $codeClient],
            ['name' => trim((string) $this->cellValue($row, 'nom_client', 'Client import paiement')) ?: 'Client import paiement']
        );

        $date = $this->parseDate($this->cellValue($row, 'date', ''))?->toDateString() ?? now()->toDateString();
        $reste = $this->parseAmount($this->cellValue($row, 'reste', '0'));
        $totalHt = round($totalTtc / 1.19, 2);

        $factureId = DB::table('factures')->insertGetId([
            'numero_facture' => $numero,
            'date_facture' => $date,
            'client_id' => $client->id,
            'escale_id' => null,
            'total_ht' => $totalHt,
            'total_tva' => round($totalTtc - $totalHt, 2),
            'total_ttc' => $totalTtc,
            'montant_paye' => max(0, $totalTtc - $reste),
            'reste_a_payer' => $reste,
            'description' => 'Facture minimale créée depuis le fichier paiements.',
            'devise' => 'DA',
            'taux_devise' => 1,
            'mode_paiement' => 1,
            'annuler' => 0,
            'needs_review' => 1,
            'verification_status' => 'warning',
            'verification_flags' => json_encode([[
                'code' => 'imported_from_payment',
                'label' => 'Facture créée depuis paiement',
                'severity' => 'warning',
            ]], JSON_UNESCAPED_SLASHES),
            'last_verified_at' => $now,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Log::channel('imports')->warning('Facture fantôme créée depuis PaiementsImport', [
            'batch_id' => $this->batch->id,
            'numero_facture' => $numero,
            'facture_id' => $factureId,
            'code_client' => $codeClient,
        ]);

        return (int) $factureId;
    }
}
