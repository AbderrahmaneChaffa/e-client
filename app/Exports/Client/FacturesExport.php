<?php

namespace App\Exports\Client;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacturesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int,\App\Models\Facture>  $factures
     */
    public function __construct(private readonly Collection $factures)
    {
    }

    /**
     * @return Collection<int,\App\Models\Facture>
     */
    public function collection(): Collection
    {
        return $this->factures;
    }

    /**
     * @return array<int,string>
     */
    public function headings(): array
    {
        return [
            'N° facture',
            'Date facture',
            'Statut',
            'Désignation',
            'Total HT',
            'TVA',
            'Total TTC',
            'Reste à payer',
            'Échéance',
        ];
    }

    /**
     * @param  \App\Models\Facture  $facture
     * @return array<int,string>
     */
    public function map($facture): array
    {
        return [
            $facture->numero_facture ?? '-',
            $facture->date_facture?->format('d/m/Y') ?? '-',
            $this->statusLabel($facture),
            $facture->pour ?? $facture->description ?? $facture->escale?->numero_escale ?? '-',
            $this->formatAmount((float) $facture->total_ht),
            $this->formatAmount((float) $facture->total_tva),
            $this->formatAmount((float) $facture->total_ttc),
            $this->formatAmount((float) $facture->reste_a_payer),
            $facture->date_echeance?->format('d/m/Y') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('E:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function title(): string
    {
        return 'Factures';
    }

    private function statusLabel($facture): string
    {
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

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' DA';
    }
}
