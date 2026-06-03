<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_a_client_code(): void
    {
        $client = Client::factory()->create([
            'code_client' => 'CLT-REG-001',
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'code_client' => $client->code_client,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', "Votre compte a été créé. Il sera activé après validation par l'administrateur EPO.");

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame($client->id, (int) $user->client_id);
        $this->assertFalse($user->is_validated);
        $this->assertSame('client', $user->getRawOriginal('role'));
    }

    public function test_registration_rejects_unknown_client_code(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'code_client' => 'INCONNU',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('code_client');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
