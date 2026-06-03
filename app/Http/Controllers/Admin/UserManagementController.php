<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Affiche la liste des utilisateurs administrables.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->with('client')
            ->leftJoin('clients', 'clients.id', '=', 'users.client_id')
            ->select('users.*');

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('clients.code_client', 'like', "%{$search}%")
                    ->orWhere('clients.name', 'like', "%{$search}%");
            });
        }

        $role = strtolower((string) $request->input('role', ''));
        $validRoles = array_map(fn (UserRole $case) => $case->value, UserRole::cases());

        if ($role !== '' && in_array($role, $validRoles, true)) {
            $query->where('users.role', $role);
        }

        if ($request->filled('validation')) {
            match ($request->input('validation')) {
                'validated' => $query->where('users.is_validated', true),
                'pending' => $query->where('users.is_validated', false),
                default => null,
            };
        }

        $allowedSorts = ['name', 'email', 'role', 'is_validated', 'created_at', 'client'];
        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : 'created_at';
        $direction = $request->input('direction', $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'client') {
            $query->orderByRaw("COALESCE(clients.code_client, '') {$direction}")
                ->orderBy('users.name');
        } else {
            $query->orderBy("users.{$sort}", $direction);
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $users = $query->paginate($perPage)->withQueryString();

        $summary = [
            'total' => User::count(),
            'validated' => User::validated()->count(),
            'pending' => User::query()->where('is_validated', false)->count(),
            'superadmins' => User::role(UserRole::SUPERADMIN)->count(),
        ];

        return view('admins.users.index', [
            'users' => $users,
            'summary' => $summary,
        ]);
    }

    /**
     * Formulaire de creation d'un utilisateur.
     */
    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admins.users.create', [
            'roles' => $this->roleOptions(),
            'clients' => Client::query()
                ->orderBy('code_client')
                ->get(['id', 'code_client', 'name']),
            'user' => new User(),
        ]);
    }

    /**
     * Enregistre un nouvel utilisateur.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $this->validatePayload($request);
        $user = $this->persistUser(new User(), $validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    /**
     * Formulaire d'edition.
     */
    public function edit(User $user): View
    {
        $this->ensureSuperAdmin();

        return view('admins.users.edit', [
            'roles' => $this->roleOptions(),
            'clients' => Client::query()
                ->orderBy('code_client')
                ->get(['id', 'code_client', 'name']),
            'user' => $user->load('client'),
        ]);
    }

    /**
     * Met a jour un utilisateur.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $this->validatePayload($request, $user);
        $this->assertSafeRoleChange($user, $validated['role']);

        $this->persistUser($user, $validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur {$user->name} mis à jour avec succès.");
    }

    /**
     * Supprime un utilisateur.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $currentUser = Auth::user();

        if ($currentUser?->is($user)) {
            throw ValidationException::withMessages([
                'delete' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        if ($user->isSuperAdmin() && User::role(UserRole::SUPERADMIN)->count() <= 1) {
            throw ValidationException::withMessages([
                'delete' => 'Le dernier superadministrateur ne peut pas être supprimé.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Bascule la validation d'un utilisateur.
     */
    public function toggleValidation(User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            abort(403);
        }

        if ($currentUser->is($user) && $user->is_validated) {
            throw ValidationException::withMessages([
                'validation' => 'Vous ne pouvez pas désactiver votre propre compte.',
            ]);
        }

        if ($user->isSuperAdmin() && $user->is_validated && User::role(UserRole::SUPERADMIN)->validated()->count() <= 1) {
            throw ValidationException::withMessages([
                'validation' => 'Le dernier superadministrateur ne peut pas être désactivé.',
            ]);
        }

        $user->forceFill([
            'is_validated' => ! $user->is_validated,
        ])->save();

        return back()->with(
            'success',
            $user->is_validated
                ? 'Utilisateur validé avec succès.'
                : 'Utilisateur désactivé avec succès.'
        );
    }

    /**
     * Retourne les options de role autorisees.
     *
     * @return array<string, string>
     */
    private function roleOptions(): array
    {
        return [
            UserRole::CLIENT->value => 'Client',
            UserRole::ADMIN->value => 'Admin',
            UserRole::SUPERADMIN->value => 'Superadmin',
        ];
    }

    /**
     * Valide le formulaire utilisateur.
     *
     * @return array{name:string,email:string,role:string,password?:string,code_client?:string|null,is_validated:bool}
     */
    private function validatePayload(Request $request, ?User $user = null): array
    {
        $role = strtolower((string) $request->input('role', UserRole::CLIENT->value));
        $ruleInRoles = Rule::in(array_map(fn (UserRole $case) => $case->value, UserRole::cases()));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', 'string', $ruleInRoles],
            'code_client' => ['nullable', 'string', 'max:50'],
            'is_validated' => ['sometimes', 'boolean'],
        ];

        $rules['code_client'] = $role === UserRole::CLIENT->value
            ? ['required', 'string', 'exists:clients,code_client', 'max:50']
            : ['nullable', 'string', 'max:50'];

        if ($user) {
            $rules['password'] = ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()];
        } else {
            $rules['password'] = ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);
        $validated['role'] = $role;
        $validated['is_validated'] = $request->boolean('is_validated');

        return $validated;
    }

    /**
     * Persiste les champs utilisateur en appliquant les regles 1:1 client/utilisateur.
     */
    private function persistUser(User $user, array $validated): User
    {
        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_validated' => $validated['is_validated'],
        ];

        if (! empty($validated['password'] ?? null)) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if ($validated['role'] === UserRole::CLIENT->value) {
            $client = Client::query()
                ->where('code_client', $validated['code_client'] ?? null)
                ->firstOrFail();

            $hasOtherUser = User::query()
                ->where('client_id', $client->id)
                ->when($user->exists, fn (Builder $query) => $query->whereKeyNot($user->id))
                ->exists();

            if ($hasOtherUser) {
                throw ValidationException::withMessages([
                    'code_client' => 'Un compte existe déjà pour ce code client.',
                ]);
            }

            $payload['client_id'] = $client->id;
        } else {
            $payload['client_id'] = null;
        }

        $user->fill($payload);
        $user->save();

        return $user->refresh();
    }

    /**
     * Verifie que l'action modifiante est reservee au superadmin.
     */
    private function ensureSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403);
        }
    }

    /**
     * Interdit la perte du dernier superadministrateur.
     */
    private function assertSafeRoleChange(User $user, string $incomingRole): void
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            abort(403);
        }

        if ($user->isSuperAdmin() && $incomingRole !== UserRole::SUPERADMIN->value && User::role(UserRole::SUPERADMIN)->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Le dernier superadministrateur ne peut pas être rétrogradé.',
            ]);
        }

        if ($currentUser->is($user) && $currentUser->isSuperAdmin() && $incomingRole !== UserRole::SUPERADMIN->value) {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas changer votre propre rôle depuis ce formulaire.',
            ]);
        }
    }
}
