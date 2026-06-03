<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_user_index(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'is_validated' => true,
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Gestion des utilisateurs');
    }

    public function test_admin_cannot_access_superadmin_only_crud_routes(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_validated' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nouveau',
                'email' => 'nouveau@example.com',
                'role' => 'client',
                'password' => 'password',
                'password_confirmation' => 'password',
                'code_client' => 'CLT-ADMIN',
            ])
            ->assertForbidden();
    }

    public function test_superadmin_can_create_client_user_from_code_client(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'is_validated' => true,
        ]);
        $client = Client::factory()->create([
            'code_client' => 'CLT-USER-001',
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.users.store'), [
                'name' => 'Client Portail',
                'email' => 'client.portail@example.com',
                'role' => 'client',
                'code_client' => $client->code_client,
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_validated' => false,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'client.portail@example.com',
            'client_id' => $client->id,
            'role' => UserRole::CLIENT->value,
            'is_validated' => 0,
        ]);
    }

    public function test_duplicate_client_code_is_rejected(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'is_validated' => true,
        ]);
        $client = Client::factory()->create([
            'code_client' => 'CLT-USER-002',
        ]);

        User::factory()->create([
            'role' => UserRole::CLIENT,
            'client_id' => $client->id,
            'email' => 'existing@example.com',
            'is_validated' => true,
        ]);

        $this->actingAs($superadmin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Nouveau Client',
                'email' => 'nouveau.client@example.com',
                'role' => 'client',
                'code_client' => $client->code_client,
                'password' => 'password',
                'password_confirmation' => 'password',
                'is_validated' => false,
            ])
            ->assertSessionHasErrors('code_client');

        $this->assertSame(1, User::query()->where('client_id', $client->id)->count());
    }

    public function test_admin_can_toggle_validation(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_validated' => true,
        ]);
        $target = User::factory()->create([
            'role' => UserRole::CLIENT,
            'is_validated' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-validation', $target))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_validated' => 0,
        ]);
    }

    public function test_self_disable_is_blocked_for_superadmin(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'is_validated' => true,
        ]);

        $this->actingAs($superadmin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.toggle-validation', $superadmin))
            ->assertSessionHasErrors('validation');

        $this->assertDatabaseHas('users', [
            'id' => $superadmin->id,
            'is_validated' => 1,
        ]);
    }

    public function test_last_superadmin_cannot_be_disabled_by_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_validated' => true,
        ]);
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'is_validated' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.toggle-validation', $superadmin))
            ->assertSessionHasErrors('validation');

        $this->assertDatabaseHas('users', [
            'id' => $superadmin->id,
            'is_validated' => 1,
        ]);
    }
}
