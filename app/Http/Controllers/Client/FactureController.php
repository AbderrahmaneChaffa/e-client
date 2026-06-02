<?php

namespace App\Http\Controllers\Client;

use App\Exports\Client\FacturesExport;
use App\Helpers\NumberHelper;
use App\Http\Controllers\Controller;
use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FactureController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateFilters($request);

        $query = $this->filteredQuery($request)->with('escale.navire');
        $stats = $this->statsFor(clone $query);
        $factures = $this->applySort($query, $request)
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('clients.factures.index', [
            'factures' => $factures,
            'stats' => $stats,
            'sort' => $this->sortColumn($request),
            'direction' => $this->direction($request),
            'perPage' => $this->perPage($request),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(Facture $facture): View
    {
        $this->authorizeFacture($facture);
        $facture->load(['escale.navire', 'prestations', 'paiements']);

        return view('clients.factures.show', compact('facture'));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->validateFilters($request);

        $factures = $this->applySort($this->filteredQuery($request)->with('escale.navire'), $request)->get();

        return Excel::download(
            new FacturesExport($factures),
            'factures_client_'.now()->format('Ymd').'.xlsx'
        );
    }

    public function exportPdf(Request $request): Response
    {
        $this->validateFilters($request);

        $query = $this->filteredQuery($request)->with('escale.navire');
        $factures = $this->applySort(clone $query, $request)->get();
        $stats = $this->statsFor($query);

        $pdf = Pdf::loadView('clients.factures.exports.pdf', [
            'factures' => $factures,
            'stats' => $stats,
            'dateExport' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('factures_client_'.now()->format('Ymd').'.pdf');
    }

    public function print(Facture $facture): Response
    {
        $this->authorizeFacture($facture);

        if ($facture->annuler) {
            abort(422, 'Impossible d’imprimer une facture annulée.');
        }

        $facture->load(['client', 'escale.navire', 'prestations', 'paiements']);

        if (! $facture->imprimer) {
            $facture->update([
                'imprimer' => true,
                'date_impression' => now(),
                'imprime_par' => Auth::id(),
            ]);
        }

        $montantEnLettres = NumberHelper::enLettres($facture->total_ttc);

        return Pdf::loadView('shared.prints.factures.pdf', compact('facture', 'montantEnLettres'))
            ->setPaper('a4', 'portrait')
            ->stream('Facture_'.$facture->numero_facture.'.pdf');
    }

    protected function authorizeFacture(Facture $facture): void
    {
        if ((int) $facture->client_id !== $this->clientId()) {
            abort(403);
        }
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Facture::query()->where('client_id', $this->clientId());
        $search = trim((string) $request->input('search', $request->input('numero', '')));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('numero_facture', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('pour', 'like', "%{$search}%");
            });
        }

        if ($request->filled('annee')) {
            $query->whereYear('date_facture', (int) $request->input('annee'));
        }

        if ($request->filled('mois')) {
            $query->whereMonth('date_facture', (int) $request->input('mois'));
        }

        match ($request->input('statut')) {
            'payee', 'paye' => $query->where('annuler', false)->where('reste_a_payer', '<=', 0),
            'impayee', 'impaye' => $query->where('annuler', false)->where('reste_a_payer', '>', 0),
            'annulee' => $query->where('annuler', true),
            'en_retard' => $query->where('annuler', false)
                ->where('reste_a_payer', '>', 0)
                ->whereNotNull('date_echeance')
                ->whereDate('date_echeance', '<', today()),
            default => null,
        };

        if ($request->filled('date_from')) {
            $query->whereDate('date_facture', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_facture', '<=', $request->date_to);
        }

        if (! $request->filled('date_from') && ! $request->filled('date_to') && $request->filled('period')) {
            match ($request->period) {
                'today' => $query->whereDate('date_facture', today()),
                'week' => $query->whereBetween('date_facture', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('date_facture', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        $amountMin = $request->input('montant_min', $request->input('amount_min'));
        $amountMax = $request->input('montant_max', $request->input('amount_max'));

        if ($amountMin !== null && $amountMin !== '') {
            $query->where('total_ttc', '>=', (float) $amountMin);
        }

        if ($amountMax !== null && $amountMax !== '') {
            $query->where('total_ttc', '<=', (float) $amountMax);
        }

        return $query;
    }

    private function applySort(Builder $query, Request $request): Builder
    {
        $direction = $this->direction($request);

        if ($this->sortColumn($request) === 'statut') {
            return $query
                ->orderByRaw(
                    "CASE
                        WHEN annuler = 1 THEN 4
                        WHEN reste_a_payer <= 0 THEN 1
                        WHEN date_echeance IS NOT NULL AND date_echeance < ? THEN 3
                        ELSE 2
                    END {$direction}",
                    [today()->toDateString()]
                )
                ->orderByDesc('date_facture')
                ->orderByDesc('id');
        }

        return $query
            ->orderBy($this->sortColumn($request), $direction)
            ->orderByDesc('id');
    }

    private function statsFor(Builder $query): object
    {
        return $query->selectRaw(
            '
                COUNT(*) AS total_count,
                COALESCE(SUM(CASE WHEN annuler = 0 THEN total_ttc ELSE 0 END), 0) AS total_ttc,
                COALESCE(SUM(CASE WHEN annuler = 0 THEN reste_a_payer ELSE 0 END), 0) AS reste_total,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer <= 0 THEN 1 ELSE 0 END) AS payees,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer > 0 THEN 1 ELSE 0 END) AS impayees,
                SUM(CASE WHEN annuler = 1 THEN 1 ELSE 0 END) AS annulees,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer > 0 AND date_echeance IS NOT NULL AND date_echeance < ? THEN 1 ELSE 0 END) AS en_retard
            ',
            [today()->toDateString()]
        )->first();
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:120'],
            'statut' => ['nullable', 'in:payee,paye,impayee,impaye,annulee,en_retard'],
            'period' => ['nullable', 'in:today,week,month,custom'],
            'annee' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'mois' => ['nullable', 'integer', 'min:1', 'max:12'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'montant_min' => ['nullable', 'numeric', 'min:0'],
            'montant_max' => ['nullable', 'numeric', 'min:0'],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:numero_facture,date_facture,total_ttc,reste_a_payer,statut'],
            'direction' => ['nullable', 'in:asc,desc'],
            'dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($request->filled('date_from') && $request->filled('date_to') && $request->date('date_to')->lt($request->date('date_from'))) {
            throw ValidationException::withMessages([
                'date_to' => 'La date de fin doit être postérieure ou égale à la date de début.',
            ]);
        }
    }

    private function sortColumn(Request $request): string
    {
        $allowed = ['numero_facture', 'date_facture', 'total_ttc', 'reste_a_payer', 'statut'];
        $sort = (string) $request->input('sort', 'date_facture');

        return in_array($sort, $allowed, true) ? $sort : 'date_facture';
    }

    private function direction(Request $request): string
    {
        $direction = (string) $request->input('direction', $request->input('dir', 'desc'));

        return $direction === 'asc' ? 'asc' : 'desc';
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }

    private function clientId(): int
    {
        $clientId = (int) Auth::user()?->client_id;

        abort_if($clientId <= 0, 403, 'Aucun client n’est associé à ce compte.');

        return $clientId;
    }

    /**
     * @return array<string,string>
     */
    private function statusOptions(): array
    {
        return [
            '' => 'Tous les statuts',
            'payee' => 'Payées',
            'impayee' => 'Impayées',
            'annulee' => 'Annulées',
            'en_retard' => 'En retard',
        ];
    }
}
