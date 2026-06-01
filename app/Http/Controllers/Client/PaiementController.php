<?php

namespace App\Http\Controllers\Client;

use App\Exports\Client\PaiementsExport;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaiementController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateFilters($request);

        $query = $this->filteredQuery($request);
        $stats = $this->statsFor(clone $query);
        $paiementGroups = $this->groupedPaiements($request, $query);

        return view('clients.paiements.index', [
            'paiementGroups' => $paiementGroups,
            'stats' => $stats,
            'banques' => $this->availableBanks(),
            'modeLabels' => $this->modeLabels(),
            'sort' => $this->sortColumn($request),
            'direction' => $this->direction($request),
            'perPage' => $this->perPage($request),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->validateFilters($request);

        $paiementGroups = $this->groupedPaiementsForExport($request);

        if ($paiementGroups->isEmpty()) {
            return back()->with('error', 'Aucune donnée à exporter pour les critères sélectionnés.');
        }

        return Excel::download(
            new PaiementsExport($paiementGroups, $this->modeLabels()),
            'paiements_client_'.now()->format('Ymd_Hi').'.xlsx'
        );
    }

    public function exportPdf(Request $request): Response
    {
        $this->validateFilters($request);

        return $this->paymentsPdf($request)->download('paiements_client_'.now()->format('Ymd_Hi').'.pdf');
    }

    public function print(Request $request): Response
    {
        $this->validateFilters($request);

        return $this->paymentsPdf($request)->stream('paiements_client_'.now()->format('Ymd_Hi').'.pdf');
    }

    private function paymentsPdf(Request $request): \Barryvdh\DomPDF\PDF
    {
        $query = $this->filteredQuery($request);
        $paiementGroups = $this->groupedPaiementsForExport($request);
        $stats = $this->statsFor($query);

        return Pdf::loadView('clients.paiements.exports.pdf', [
            'paiementGroups' => $paiementGroups,
            'stats' => $stats,
            'modeLabels' => $this->modeLabels(),
            'client' => Auth::user()?->loadMissing('client')->client,
            'dateExport' => now()->format('d/m/Y H:i'),
            'periodLabel' => $this->periodLabel($request),
        ])->setPaper('a4', 'landscape');
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Paiement::query()
            ->whereHas('facture', fn (Builder $query) => $query->where('client_id', $this->clientId()));

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('recu', 'like', "%{$search}%")
                    ->orWhere('numero_cheque', 'like', "%{$search}%")
                    ->orWhereHas('facture', fn (Builder $invoice) => $invoice->where('numero_facture', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('banque')) {
            $query->where('banque', $request->banque);
        }

        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', (int) $request->mode_paiement);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_paiement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_paiement', '<=', $request->date_to);
        }

        return $query;
    }

    private function groupedPaiements(Request $request, Builder $query): LengthAwarePaginator
    {
        $groups = $this->applyGroupSort(
            $this->groupSummaryQuery($query),
            $request
        )
            ->paginate($this->perPage($request))
            ->withQueryString();

        $groups->setCollection($this->hydrateGroups($request, $groups->getCollection()));

        return $groups;
    }

    /**
     * @return Collection<int,object>
     */
    private function groupedPaiementsForExport(Request $request): Collection
    {
        $groups = $this->applyGroupSort(
            $this->groupSummaryQuery($this->filteredQuery($request)),
            $request
        )->get();

        return $this->hydrateGroups($request, $groups);
    }

    private function groupSummaryQuery(Builder $query): Builder
    {
        $groupExpression = $this->groupKeyExpression();

        return (clone $query)
            ->selectRaw("
                {$groupExpression} AS group_key,
                MAX(NULLIF(paiements.numero_cheque, '')) AS numero_cheque,
                MIN(paiements.id) AS first_payment_id,
                MIN(paiements.recu) AS recu,
                MAX(paiements.date_paiement) AS date_paiement,
                COALESCE(SUM(paiements.montant), 0) AS total_montant,
                COUNT(*) AS paiements_count,
                COUNT(DISTINCT paiements.facture_id) AS factures_count,
                MAX(paiements.banque) AS banque,
                MAX(paiements.mode_paiement) AS mode_paiement
            ")
            ->groupBy('group_key');
    }

    /**
     * @param  Collection<int,object>  $groups
     * @return Collection<int,object>
     */
    private function hydrateGroups(Request $request, Collection $groups): Collection
    {
        $groupKeys = $groups->pluck('group_key')->filter()->values();

        if ($groupKeys->isEmpty()) {
            return $groups;
        }

        $groupExpression = $this->groupKeyExpression();

        $paiements = $this->filteredQuery($request)
            ->with('facture')
            ->whereIn(DB::raw($groupExpression), $groupKeys->all())
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();

        $paiementsByGroup = $paiements->groupBy(fn (Paiement $paiement): string => $this->groupKeyForPayment($paiement));

        return $groups->map(function ($group) use ($paiementsByGroup) {
            $items = $paiementsByGroup->get($group->group_key, collect());

            return (object) [
                'key' => $group->group_key,
                'numero_cheque' => $group->numero_cheque,
                'is_direct' => blank($group->numero_cheque),
                'recu' => $group->recu,
                'date_paiement' => $group->date_paiement,
                'total_montant' => (float) $group->total_montant,
                'paiements_count' => (int) $group->paiements_count,
                'factures_count' => (int) $group->factures_count,
                'banque' => $group->banque,
                'mode_paiement' => (int) $group->mode_paiement,
                'paiements' => $items,
            ];
        })->values();
    }

    private function applyGroupSort(Builder $query, Request $request): Builder
    {
        $direction = $this->direction($request);

        match ($this->sortColumn($request)) {
            'numero_cheque' => $query
                ->orderByRaw("CASE WHEN numero_cheque IS NULL THEN 1 ELSE 0 END {$direction}")
                ->orderBy('numero_cheque', $direction),
            'recu' => $query->orderBy('recu', $direction),
            'montant' => $query->orderBy('total_montant', $direction),
            'banque' => $query->orderBy('banque', $direction),
            default => $query->orderBy('date_paiement', $direction),
        };

        return $query->orderByDesc('first_payment_id');
    }

    private function statsFor(Builder $query): object
    {
        return (object) [
            'total_count' => (clone $query)->count(),
            'total_montant' => (float) (clone $query)->sum('montant'),
            'total_cheques' => (int) (clone $query)
                ->whereNotNull('numero_cheque')
                ->where('numero_cheque', '<>', '')
                ->distinct()
                ->count('numero_cheque'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int,string>
     */
    private function availableBanks(): \Illuminate\Support\Collection
    {
        return Paiement::query()
            ->whereHas('facture', fn (Builder $query) => $query->where('client_id', $this->clientId()))
            ->whereNotNull('banque')
            ->where('banque', '<>', '')
            ->distinct()
            ->orderBy('banque')
            ->pluck('banque')
            ->values();
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'banque' => ['nullable', 'string', 'max:120'],
            'mode_paiement' => ['nullable', 'integer', 'in:1,2,3,4'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:numero_cheque,recu,date_paiement,montant,banque'],
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
        $allowed = ['numero_cheque', 'recu', 'date_paiement', 'montant', 'banque'];
        $sort = (string) $request->input('sort', 'date_paiement');

        return in_array($sort, $allowed, true) ? $sort : 'date_paiement';
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

    private function groupKeyExpression(): string
    {
        return "COALESCE(NULLIF(paiements.numero_cheque, ''), CONCAT('direct-', paiements.id))";
    }

    private function groupKeyForPayment(Paiement $paiement): string
    {
        $numeroCheque = trim((string) $paiement->numero_cheque);

        return $numeroCheque !== '' ? $numeroCheque : 'direct-'.$paiement->id;
    }

    private function periodLabel(Request $request): string
    {
        $from = $request->filled('date_from')
            ? $request->date('date_from')?->format('d/m/Y')
            : null;
        $to = $request->filled('date_to')
            ? $request->date('date_to')?->format('d/m/Y')
            : null;

        return match (true) {
            $from && $to => "Du {$from} au {$to}",
            (bool) $from => "Depuis le {$from}",
            (bool) $to => "Jusqu’au {$to}",
            default => 'Toutes périodes',
        };
    }

    /**
     * @return array<int,string>
     */
    private function modeLabels(): array
    {
        return [
            1 => 'Virement',
            2 => 'Chèque',
            3 => 'Espèce',
            4 => 'Versement',
        ];
    }
}
