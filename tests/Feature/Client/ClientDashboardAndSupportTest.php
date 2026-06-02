<?php

namespace Tests\Feature\Client;

use App\Models\Client;
use App\Models\Facture;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDashboardAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_dashboard_renders(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'client_id' => $client->id,
            'role' => 'client',
            'is_validated' => true,
        ]);

        Facture::factory()->count(2)->create([
            'client_id' => $client->id,
            'escale_id' => null,
            'annuler' => false,
            'devise' => 'DA',
            'reste_a_payer' => 5000,
            'montant_paye' => 1000,
            'date_echeance' => now()->addDays(15),
        ]);

        $response = $this->actingAs($user)->get(route('client.dashboard'));

        $response->assertOk();
        $response->assertSee('Taux de recouvrement');
        $response->assertSee('Répartition des factures');
        $response->assertSee('Support');
    }

    public function test_client_can_create_support_ticket_for_its_invoice_only(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'client_id' => $client->id,
            'role' => 'client',
            'is_validated' => true,
        ]);

        $facture = Facture::factory()->create([
            'client_id' => $client->id,
            'escale_id' => null,
            'annuler' => false,
            'devise' => 'DA',
            'reste_a_payer' => 1200,
            'montant_paye' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('client.support.store'), [
            'facture_id' => $facture->id,
            'sujet' => 'Demande de clarification',
            'message' => 'Bonjour, merci de vérifier cette facture.',
            'priorite' => 'normal',
        ]);

        $response->assertRedirect(route('client.support.index', false));
        $this->assertDatabaseHas('support_tickets', [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'facture_id' => $facture->id,
            'sujet' => 'Demande de clarification',
            'statut' => 'ouvert',
            'priorite' => 'normal',
        ]);

        $otherClient = Client::factory()->create();
        $otherFacture = Facture::factory()->create([
            'client_id' => $otherClient->id,
            'escale_id' => null,
            'annuler' => false,
            'devise' => 'DA',
            'reste_a_payer' => 800,
            'montant_paye' => 0,
        ]);

        $invalidResponse = $this->actingAs($user)->post(route('client.support.store'), [
            'facture_id' => $otherFacture->id,
            'sujet' => 'Accès non autorisé',
            'message' => 'Cette facture ne doit pas être accessible.',
            'priorite' => 'urgent',
        ]);

        $invalidResponse->assertSessionHasErrors('facture_id');

        $this->assertDatabaseMissing('support_tickets', [
            'facture_id' => $otherFacture->id,
            'sujet' => 'Accès non autorisé',
        ]);
    }
}
