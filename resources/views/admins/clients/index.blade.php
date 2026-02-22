@extends('admins.layouts.admin')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Gestion des Clients</h2>
        <a href="{{ route('admin.clients.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
            <i class="fa-solid fa-plus mr-2"></i> Nouveau Client
        </a>
    </div>

    <form method="GET" class="mb-6 p-4 bg-gray-50 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Code, nom, ou NIS..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Nom</option>
                    <option value="code_client" {{ request('sort') == 'code_client' ? 'selected' : '' }}>Code</option>
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date de création</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition py-2 text-sm font-medium">
                    <i class="fa-solid fa-search mr-2"></i> Rechercher
                </button>
                @if(request('search'))
                <a href="{{ route('admin.clients.index') }}" class="flex-1 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition py-2 text-sm font-medium text-center">
                    <i class="fa-solid fa-rotate-left mr-2"></i> Réinitialiser
                </a>
                @endif
            </div>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="table w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
                    <th class="p-4 border-b">Code</th>
                    <th class="p-4 border-b">Nom</th>
                    <th class="p-4 border-b">NIS</th>
                    <th class="p-4 border-b">NIF</th>
                    <th class="p-4 border-b">RC</th>
                    <th class="p-4 border-b">AI</th>
                    <th class="p-4 border-b">Factures</th>
                    <th class="p-4 border-b">Utilisateurs</th>
                    <th class="p-4 border-b text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50 transition border-b">
                    <td class="p-4 font-semibold text-blue-600">{{ $client->code_client }}</td>
                    <td class="p-4 font-medium">{{ $client->name }}</td>
                    <td class="p-4 text-gray-600">{{ $client->nis ?? '-' }}</td>
                    <td class="p-4 text-gray-600">{{ $client->nif ?? '-' }}</td>
                    <td class="p-4 text-gray-600">{{ $client->rc ?? '-' }}</td>
                    <td class="p-4 text-gray-600">{{ $client->ai ?? '-' }}</td>
                    <td class="p-4 text-center">
                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">
                            {{ $client->factures()->count() }}
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-semibold">
                            {{ $client->users()->count() }}
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <a href="{{ route('admin.clients.show', $client) }}" class="text-gray-400 hover:text-blue-600 mx-1 inline-block" title="Voir">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.clients.edit', $client) }}" class="text-gray-400 hover:text-green-600 mx-1 inline-block" title="Modifier">
                            <i class="fa-solid fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 mx-1" title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-gray-500">
                        <i class="fa-solid fa-inbox text-4xl mb-2 opacity-50"></i>
                        <p class="text-lg font-semibold">Aucun client trouvé</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $clients->links() }}
    </div>
</div>
@endsection