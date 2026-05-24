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
        $query = Client::with('users');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code_client', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%")
                    ->orWhere('rc', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if (!$request->filled('date_from') && !$request->filled('date_to') && $request->filled('period')) {
            match ($request->period) {
                'today' => $query->whereDate('created_at', today()),
                'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        $allowedSorts = ['name', 'code_client', 'created_at', 'email', 'nis', 'nif', 'rc'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'name';
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->input('per_page', 25), 100);
        $clients = $query->paginate($perPage)->withQueryString();

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
            'code_client' => 'required|string|unique:clients|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'nis' => 'nullable|string|max:50',
            'nif' => 'nullable|string|max:50',
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
            'code_client' => 'required|string|max:50|unique:clients,code_client,' . $client->id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'nis' => 'nullable|string|max:50',
            'nif' => 'nullable|string|max:50',
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
