@extends('admins.layouts.admin')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 max-w-2xl">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Modifier le client</h2>

    <form action="{{ route('admin.clients.update', $client) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Code Client (Read Only) -->
            <div>
                <label for="code_client" class="block text-sm font-medium text-gray-700 mb-1">Code Client</label>
                <input 
                    type="text" 
                    name="code_client" 
                    id="code_client" 
                    value="{{ $client->code_client }}" 
                    readonly
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-gray-50 text-gray-600">
            </div>

            <!-- Nom Client -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du client <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name', $client->name) }}" 
                    placeholder="Ex: EPO Transport"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    required>
                @error('name')
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
                    value="{{ old('nis', $client->nis) }}" 
                    placeholder="Numéro d'identification statistique"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nis') border-red-500 @enderror">
                @error('nis')
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
                    value="{{ old('rc', $client->rc) }}" 
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
                    value="{{ old('ai', $client->ai) }}" 
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
                <i class="fa-solid fa-save mr-2"></i> Mettre à jour
            </button>
            <a 
                href="{{ route('admin.clients.show', $client) }}" 
                class="flex-1 bg-gray-400 text-white px-6 py-2 rounded-lg hover:bg-gray-500 transition font-medium text-center">
                <i class="fa-solid fa-times mr-2"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection
