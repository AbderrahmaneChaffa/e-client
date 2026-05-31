<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Paiements - {{ date('d/m/Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th {
            background: #f3f4f6;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }

        .cheque-header {
            background: #dbeafe;
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            background: #dcfce7;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>

<body>
    <h2 style="text-align: center;">État des Paiements</h2>
    <p style="text-align: center; font-size: 9pt;">Exporté le : {{ $dateExport }}</p>

    <table>
        <thead>
            <tr>
                <th width="15%">N° Chèque</th>
                <th width="35%">Factures</th>
                <th width="15%">Banque</th>
                <th width="15%" style="text-align: right;">Montant</th>
                <th width="20%">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedPaiements as $key => $group)
                @php
                    $isSans = str_starts_with($key, 'sans_cheque_');
                    $cheque = $isSans ? 'Paiement direct' : '#' . $key;
                    $total = $group->sum('montant');
                    $first = $group->first();
                @endphp
                <tr class="cheque-header">
                    <td>{{ $cheque }}</td>
                    <td>
                        @foreach($group as $p)
                            {{ $p->facture?->numero_facture ?? 'N/A' }}
                            ({{ number_format($p->montant, 0, ',', ' ') }} DA)@if(!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>{{ $first->banque ?? '-' }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($total, 2, ',', ' ') }} DA</td>
                    <td>{{ $first->date_paiement ? \Carbon\Carbon::parse($first->date_paiement)->format('d/m/Y') : '-' }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL GÉNÉRAL :</td>
                <td style="text-align: right;">{{ number_format($totalMontant, 2, ',', ' ') }} DA</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Page <span style="page-break-after: always;"></span>
    </div>
</body>

</html>