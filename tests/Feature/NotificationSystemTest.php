<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Facture;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\Notifications\AlertNotificationService;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_invoice_alerts_are_deduplicated(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::CLIENT->value,
            'client_id' => $client->id,
        ]);

        $facture = Facture::factory()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'annuler' => false,
            'devise' => 'DA',
            'total_ttc' => 1000,
            'reste_a_payer' => 250,
            'date_echeance' => now()->subDay()->toDateString(),
        ]);

        $alerts = app(AlertNotificationService::class);
        $alerts->notifyClientInvoiceStatus($facture);
        $alerts->notifyClientInvoiceStatus($facture);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('notification_deduplications', 2);

        $alertTypes = $user->notifications()
            ->get()
            ->pluck('data.alert_type')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['overdue_invoice', 'unpaid_invoice'], $alertTypes);
    }

    public function test_admin_import_alerts_are_deduplicated(): void
    {
        User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $batch = ImportBatch::create([
            'type' => 'factures',
            'original_filename' => 'factures.xlsx',
            'stored_path' => 'imports/factures/factures.xlsx',
            'status' => 'pending',
            'created_by' => null,
        ]);

        $alerts = app(AlertNotificationService::class);
        $alerts->notifyQueuedImport($batch);
        $alerts->notifyQueuedImport($batch);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notification_deduplications', [
            'dedupe_key' => "admin:import:queued:{$batch->id}",
        ]);

        $this->actingAs(User::where('role', UserRole::ADMIN->value)->first())
            ->get(route('admin.imports.show', $batch))
            ->assertOk();
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $batch = ImportBatch::create([
            'type' => 'paiements',
            'original_filename' => 'paiements.xlsx',
            'stored_path' => 'imports/paiements/paiements.xlsx',
            'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        app(AlertNotificationService::class)->notifyQueuedImport($batch);

        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)
            ->patchJson(route('notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk();
    }
}
