<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Facture;
use App\Models\ImportVerification;
use App\Models\Paiement;
use App\Services\ImportVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flags_invoice_with_invalid_vat_total(): void
    {
        $facture = $this->createFacture([
            'total_ht' => 1000,
            'total_tva' => 190,
            'total_ttc' => 1200,
            'reste_a_payer' => 1200,
        ]);

        app(ImportVerificationService::class)->verify();

        $facture->refresh();

        $this->assertSame('critical', $facture->verification_status);
        $this->assertDatabaseHas('import_verifications', [
            'rule_code' => 'tva_coherence',
            'severity' => 'critical',
            'affected_count' => 1,
        ]);
    }

    public function test_it_flags_payment_balance_and_overpayment(): void
    {
        $facture = $this->createFacture([
            'total_ht' => 1000,
            'total_tva' => 190,
            'total_ttc' => 1190,
            'reste_a_payer' => 0,
        ]);

        Paiement::create([
            'facture_id' => $facture->id,
            'recu' => 'REC-1',
            'date_paiement' => now()->toDateString(),
            'montant' => 1300,
            'created_by' => null,
        ]);

        app(ImportVerificationService::class)->verify();

        $facture->refresh();
        $codes = collect($facture->verification_flags)->pluck('code')->all();

        $this->assertSame('critical', $facture->verification_status);
        $this->assertContains('payment_balance', $codes);
        $this->assertContains('overpaid_invoices', $codes);
        $this->assertSame(2, ImportVerification::where('severity', 'critical')->count());
    }

    private function createFacture(array $overrides = []): Facture
    {
        $client = Client::factory()->create();

        return Facture::create([
            'numero_facture' => '2026T'.str_pad((string) random_int(1, 999999), 9, '0', STR_PAD_LEFT),
            'date_facture' => now()->subDay()->toDateString(),
            'client_id' => $client->id,
            'escale_id' => null,
            'mode_paiement' => 1,
            'devise' => 'DA',
            'taux_devise' => 1,
            'annuler' => 0,
            'created_by' => null,
            ...$overrides,
        ]);
    }
}
