@extends('admins.layouts.admin')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mx-auto">
    <h2 class="text-xl font-bold text-gray-800 mb-6">
        @if(isset($client))
        Modifier le client
        @else
        Créer un nouveau client
        @endif
    </h2>

    <form action="@if(isset($client)){{ route('admin.clients.update', $client) }}@else{{ route('admin.clients.store') }}@endif" method="POST">
        @csrf
        @if(isset($client))
        @method('PUT')
        @endif

        <div class="space-y-6">
            <!-- Code Client -->
            <div>
                <label for="code_client" class="block text-sm font-medium text-gray-700 mb-1">Code Client <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="code_client"
                    id="code_client"
                    value="{{ old('code_client', $client->code_client ?? '') }}"
                    placeholder="Ex: C-001"
                    {{ isset($client) ? 'readonly' : '' }}
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('code_client') border-red-500 @enderror"
                    required>
                @error('code_client')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nom Client -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du client <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $client->name ?? '') }}"
                    placeholder="Ex: EPO Transport"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    required>
                @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- Adresse -->
            <div>
                <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                <input
                    type="text"
                    name="adresse"
                    id="adresse"
                    value="{{ old('adresse', $client->adresse ?? '') }}"
                    placeholder="Adresse du client"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('adresse') border-red-500 @enderror">
                @error('adresse')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $client->email ?? '') }}"
                    placeholder="Adresse email du client"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- Telephone -->
            <div>
                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input
                    type="text"
                    name="telephone"
                    id="telephone"
                    value="{{ old('telephone', $client->telephone ?? '') }}"
                    placeholder="Numéro de téléphone du client"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('telephone') border-red-500 @enderror">
                @error('telephone')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- NIS -->
            <div>
                <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
                <input
                    type="text"
                    name="nis"
                    id="nis"
                    value="{{ old('nis', $client->nis ?? '') }}"
                    placeholder="Numéro d'identification statistique"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nis') border-red-500 @enderror">
                @error('nis')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <!-- NIF -->
            <div>
                <label for="nif" class="block text-sm font-medium text-gray-700 mb-1">NIF</label>
                <input
                    type="text"
                    name="nif"
                    id="nif"
                    value="{{ old('nif', $client->nif ?? '') }}"
                    placeholder="Numéro d'identification fiscal"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nif') border-red-500 @enderror">
                @error('nif')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- RC -->
            <div>
                <label for="rc" class="block text-sm font-medium text-gray-700 mb-1">RC (Registre Commerce)</label>
                <input
                    type="text"
                    name="rc"
                    id="rc"
                    value="{{ old('rc', $client->rc ?? '') }}"
                    placeholder="Numéro du registre de commerce"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('rc') border-red-500 @enderror">
                @error('rc')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- AI -->
            <div>
                <label for="ai" class="block text-sm font-medium text-gray-700 mb-1">AI (Attestation Impôt)</label>
                <input
                    type="text"
                    name="ai"
                    id="ai"
                    value="{{ old('ai', $client->ai ?? '') }}"
                    placeholder="Numéro d'attestation d'impôt"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('ai') border-red-500 @enderror">
                @error('ai')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="mt-8 flex gap-4">
            <button
                type="submit"
                class="flex-1 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                <i class="fa-solid fa-save mr-2"></i>
                @if(isset($client))
                Mettre à jour
                @else
                Créer le client
                @endif
            </button>
            <a
                href="{{ route('admin.clients.index') }}"
                class="flex-1 bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium text-center">
                <i class="fa-solid fa-times mr-2"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection