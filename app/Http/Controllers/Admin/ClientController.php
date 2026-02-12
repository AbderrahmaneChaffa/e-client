<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        // Search by code or name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $clients = $query->paginate(15);

        return view('admins.clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        return view('admins.clients.create');
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:clients|max:50',
            'name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50',
            'rc' => 'nullable|string|max:50',
            'ai' => 'nullable|string|max:50',
        ]);

        Client::create($validated);

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client créé avec succès !');
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        $client->load('factures', 'users');
        return view('admins.clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client)
    {
        return view('admins.clients.edit', compact('client'));
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:clients,code,' . $client->id,
            'name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50',
            'rc' => 'nullable|string|max:50',
            'ai' => 'nullable|string|max:50',
        ]);

        $client->update($validated);

        return redirect()->route('admin.clients.show', $client)
            ->with('success', 'Client mis à jour avec succès !');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client supprimé avec succès !');
    }
}
