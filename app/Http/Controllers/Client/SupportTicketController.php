<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    /**
     * Liste paginée des tickets de support du client.
     */
    public function index(Request $request): View
    {
        $clientId = $this->clientId();
        $status = (string) $request->input('statut', '');

        $query = SupportTicket::query()
            ->where('client_id', $clientId)
            ->with('facture')
            ->latest();

        if (in_array($status, ['ouvert', 'en_cours', 'resolu'], true)) {
            $query->where('statut', $status);
        }

        $tickets = $query->paginate($this->perPage($request))->withQueryString();

        $stats = SupportTicket::query()
            ->where('client_id', $clientId)
            ->selectRaw('
                COUNT(*) AS total_count,
                SUM(CASE WHEN statut = "ouvert" THEN 1 ELSE 0 END) AS ouverts,
                SUM(CASE WHEN statut = "en_cours" THEN 1 ELSE 0 END) AS en_cours,
                SUM(CASE WHEN statut = "resolu" THEN 1 ELSE 0 END) AS resolus,
                SUM(CASE WHEN priorite = "urgent" THEN 1 ELSE 0 END) AS urgents
            ')
            ->first();

        return view('clients.support.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'statusOptions' => $this->statusOptions(),
            'selectedStatus' => $status,
            'perPage' => $this->perPage($request),
        ]);
    }

    /**
     * Formulaire de création d'un ticket.
     */
    public function create(Request $request): View
    {
        $clientId = $this->clientId();
        $facture = null;

        if ($request->filled('facture_id')) {
            $facture = Facture::query()
                ->where('client_id', $clientId)
                ->with(['escale.navire'])
                ->findOrFail((int) $request->integer('facture_id'));
        }

        $factures = Facture::query()
            ->where('client_id', $clientId)
            ->active()
            ->with(['escale.navire'])
            ->orderByDesc('date_facture')
            ->orderByDesc('id')
            ->take(30)
            ->get();

        return view('clients.support.create', compact('facture', 'factures'));
    }

    /**
     * Enregistre un ticket de support lié à une facture client.
     */
    public function store(Request $request): RedirectResponse
    {
        $clientId = $this->clientId();

        $validated = $request->validate([
            'facture_id' => [
                'nullable',
                'integer',
                Rule::exists('factures', 'id')->where(fn ($query) => $query->where('client_id', $clientId)),
            ],
            'sujet' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'priorite' => ['required', 'in:normal,urgent'],
        ]);

        SupportTicket::create([
            'client_id' => $clientId,
            'user_id' => (int) Auth::id(),
            'facture_id' => $validated['facture_id'] ?? null,
            'sujet' => $validated['sujet'],
            'message' => $validated['message'],
            'statut' => 'ouvert',
            'priorite' => $validated['priorite'],
        ]);

        return redirect()
            ->route('client.support.index')
            ->with('success', 'Votre ticket de support a bien été créé. Notre équipe vous répondra dès que possible.');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            '' => 'Tous les tickets',
            'ouvert' => 'Ouverts',
            'en_cours' => 'En cours',
            'resolu' => 'Résolus',
        ];
    }

    private function clientId(): int
    {
        $clientId = (int) Auth::user()?->client_id;

        abort_if($clientId <= 0, 403, 'Aucun client n’est associé à ce compte.');

        return $clientId;
    }
}
