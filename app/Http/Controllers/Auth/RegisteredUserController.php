<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'code_client' => ['required', 'string', 'exists:clients,code_client'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $client = Client::query()
                ->where('code_client', $validated['code_client'])
                ->firstOrFail();

            if (User::query()->where('client_id', $client->id)->exists()) {
                throw ValidationException::withMessages([
                    'code_client' => 'Un compte existe déjà pour ce code client.',
                ]);
            }

            return User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'client_id' => $client->id,
                'role' => 'client',
                'is_validated' => false,
            ]);
        });

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('status', "Votre compte a été créé. Il sera activé après validation par l'administrateur EPO.");
    }
}
