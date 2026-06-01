<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Factures client - {{ $dateExport }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        .muted {
            color: #6b7280;
        }

        .summary {
            border-collapse: collapse;
            margin: 18px 0;
            width: 100%;
        }

        .summary td {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 8px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            font-size: 9px;
            padding: 7px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #e5e7eb;
            padding: 6px 7px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .status {
            font-weight: bold;
        }

        .footer {
            bottom: 0;
            color: #6b7280;
            font-size: 8px;
            position: fixed;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <h1>État des factures</h1>
    <div class="muted">Exporté le {{ $dateExport }}</div>

    <table class="summary">
        <tr>
            <td><strong>Total factures :</strong> {{ number_format((int) ($stats->total_count ?? 0), 0, ',', ' ') }}</td>
            <td><strong>Total TTC :</strong> {{ number_format((float) ($stats->total_ttc ?? 0), 2, ',', ' ') }} DA</td>
            <td><strong>Reste à payer :</strong> {{ number_format((float) ($stats->reste_total ?? 0), 2, ',', ' ') }} DA</td>
            <td><strong>En retard :</strong> {{ number_format((int) ($stats->en_retard ?? 0), 0, ',', ' ') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 11%;">N° facture</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 11%;">Statut</th>
                <th>Désignation</th>
                <th class="right" style="width: 13%;">Total TTC</th>
                <th class="right" style="width: 13%;">Reste à payer</th>
                <th style="width: 10%;">Échéance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($factures as $facture)
                @php
                    $status = 'Impayée';

                    if ($facture->annuler) {
                        $status = 'Annulée';
                    } elseif ((float) $facture->reste_a_payer <= 0) {
                        $status = 'Payée';
                    } elseif ($facture->date_echeance && $facture->date_echeance->lt(today())) {
                        $status = 'En retard';
                    }
                @endphp
                <tr>
                    <td>{{ $facture->numero_facture ?? '-' }}</td>
                    <td>{{ $facture->date_facture?->format('d/m/Y') ?? '-' }}</td>
                    <td class="status">{{ $status }}</td>
                    <td>{{ $facture->pour ?? $facture->description ?? $facture->escale?->numero_escale ?? '-' }}</td>
                    <td class="right">{{ number_format((float) $facture->total_ttc, 2, ',', ' ') }} DA</td>
                    <td class="right">{{ number_format((float) $facture->reste_a_payer, 2, ',', ' ') }} DA</td>
                    <td>{{ $facture->date_echeance?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Aucune facture ne correspond aux filtres actifs.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">E-Client EPO - export factures client</div>
</body>
</html>
