@php
    use App\UserRole;

    $editing = isset($user) && $user->exists;
    $currentRole = old(
        'role',
        $editing && $user->role instanceof UserRole
            ? $user->role->value
            : strtolower((string) ($user?->getRawOriginal('role') ?: UserRole::CLIENT->value))
    );
    $codeClientValue = old('code_client', $user?->client?->code_client);
    $isValidated = old('is_validated', $editing ? (bool) $user->is_validated : false);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6"
    x-data="{ role: @js($currentRole) }"
>
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <label for="name" class="ui-label mb-1">Nom complet</label>
            <input
                id="name"
                name="name"
                type="text"
                class="ui-input"
                value="{{ old('name', $user?->name) }}"
                required
                autocomplete="name"
            >
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="ui-label mb-1">Adresse email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="ui-input"
                value="{{ old('email', $user?->email) }}"
                required
                autocomplete="email"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <label for="role" class="ui-label mb-1">Rôle</label>
            <select id="role" name="role" class="ui-input" x-model="role" required>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="ui-label mb-1">
                {{ $editing ? 'Nouveau mot de passe' : 'Mot de passe' }}
            </label>
            <input
                id="password"
                name="password"
                type="password"
                class="ui-input"
                {{ $editing ? '' : 'required' }}
                autocomplete="new-password"
                placeholder="{{ $editing ? 'Laisser vide pour conserver le mot de passe actuel' : 'Mot de passe temporaire' }}"
            >
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <label for="password_confirmation" class="ui-label mb-1">Confirmation du mot de passe</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="ui-input"
                autocomplete="new-password"
            >
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
            <p class="font-semibold text-gray-900 dark:text-gray-100">Règle d'accès</p>
            <p class="mt-1">Un compte client doit être lié à un code client unique. Les comptes admin et superadmin n'utilisent pas de client associé.</p>
        </div>
    </div>

    <div x-show="role === 'client'" x-cloak class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <label for="code_client" class="ui-label mb-1">Code client</label>
            <input
                id="code_client"
                name="code_client"
                type="text"
                class="ui-input"
                value="{{ $codeClientValue }}"
                x-bind:required="role === 'client'"
                list="clients-code-list"
                placeholder="Ex. CLT-1001"
                autocomplete="off"
            >
            <datalist id="clients-code-list">
                @foreach($clients as $client)
                    <option value="{{ $client->code_client }}">{{ $client->code_client }} - {{ $client->name }}</option>
                @endforeach
            </datalist>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Saisissez un code existant pour rattacher ce compte à un client.</p>
            <x-input-error :messages="$errors->get('code_client')" class="mt-2" />
        </div>

        <div class="rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800 dark:border-primary-800 dark:bg-primary-900/20 dark:text-primary-200">
            <p class="font-semibold">Relation 1:1</p>
            <p class="mt-1">Chaque client ne peut être lié qu'à un seul compte utilisateur. Toute tentative de doublon sera rejetée.</p>
        </div>
    </div>

    <div class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <input
            id="is_validated"
            name="is_validated"
            type="checkbox"
            value="1"
            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900"
            @checked($isValidated)
        >
        <div>
            <label for="is_validated" class="text-sm font-semibold text-gray-900 dark:text-gray-100">Compte validé</label>
            <p class="text-sm text-gray-600 dark:text-gray-400">Un compte non validé peut être créé, mais l'accès restera bloqué jusqu'à activation.</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="ui-btn-secondary">
            Annuler
        </a>
        <button type="submit" class="ui-btn-primary">
            <i data-lucide="{{ $editing ? 'save' : 'user-plus' }}" class="h-4 w-4" aria-hidden="true"></i>
            <span>{{ $submitLabel }}</span>
        </button>
    </div>
</form>
