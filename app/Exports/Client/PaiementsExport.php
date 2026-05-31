<?php

namespace App\Exports\Client;

use App\Models\Paiement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class PaiementsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $paiements;

    public function __construct(Collection $paiements)
    {
        $this->paiements = $paiements;
    }

    public function collection()
    {
        return $this->paiements;
    }

    public function headings(): array
    {
        return [
            'N° Chèque',
            'N° Reçu',
            'N° Facture',
            'Banque',
            'Date Paiement',
            'Montant (DA)',
            'Mode Paiement',
            'Note'
        ];
    }

    public function map($paiement): array
    {
        return [
            $paiement->numero_cheque ?? '-',
            $paiement->recu ?? '-',
            $paiement->facture?->numero_facture ?? '-',
            $paiement->banque ?? '-',
            $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-',
            number_format($paiement->montant, 2, ',', ' '),
            $paiement->mode_paiement ?? '-',
            $paiement->note ?? '-'
        ];
    }

    public function title(): string
    {
        return 'Paiements';
    }
}