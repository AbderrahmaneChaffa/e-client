<?php

namespace App\Exports\Client;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaiementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  Collection<int,object>  $paiementGroups
     * @param  array<int,string>  $modeLabels
     */
    public function __construct(
        private readonly Collection $paiementGroups,
        private readonly array $modeLabels = [],
    ) {
    }

    public function collection(): Collection
    {
        return $this->paiementGroups
            ->flatMap(function (object $group): Collection {
                return $group->paiements->map(fn ($paiement): array => [
                    $this->groupLabel($group),
                    $paiement->recu ?? '-',
                    $paiement->facture?->numero_facture ?? '-',
                    $paiement->banque ?? $group->banque ?? '-',
                    $paiement->date_paiement ? Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-',
                    number_format((float) $paiement->montant, 2, ',', ' ').' DA',
                    $this->modeLabels[(int) $paiement->mode_paiement] ?? '-',
                    $paiement->note ?? '-',
                    $this->invoiceStatusLabel($paiement->facture),
                ]);
            })
            ->values();
    }

    /**
     * @return array<int,string>
     */
    public function headings(): array
    {
        return [
            'Groupe / N° chèque',
            'N° reçu',
            'N° facture',
            'Banque',
            'Date paiement',
            'Montant',
            'Mode',
            'Note',
            'Statut facture liée',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('F:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function title(): string
    {
        return 'Paiements';
    }

    private function groupLabel(object $group): string
    {
        return $group->is_direct ? 'Direct' : (string) $group->numero_cheque;
    }

    private function invoiceStatusLabel($facture): string
    {
        if (! $facture) {
            return '-';
        }

        if ($facture->annuler) {
            return 'Annulée';
        }

        if ((float) $facture->reste_a_payer <= 0) {
            return 'Payée';
        }

        if ($facture->date_echeance && $facture->date_echeance->lt(today())) {
            return 'En retard';
        }

        return 'Impayée';
    }
}
