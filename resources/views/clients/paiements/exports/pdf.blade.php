<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Paiements client - {{ $dateExport }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        h1 {
            font-size: 20px;
            margin: 0;
        }

        h2 {
            font-size: 12px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            font-size: 8.5px;
            padding: 6px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
        }

        .brand {
            align-items: center;
            border-bottom: 2px solid #1d4ed8;
            display: flex;
            gap: 12px;
            padding-bottom: 10px;
        }

        .brand img {
            height: 42px;
            width: 42px;
        }

        .muted {
            color: #6b7280;
        }

        .summary {
            margin: 16px 0;
        }

        .summary td {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            padding: 8px;
        }

        .group {
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .group-heading {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 8px 10px;
        }

        .right {
            text-align: right;
        }

        .total-row td {
            background: #ecfdf5;
            font-weight: bold;
        }

        .empty {
            border: 1px solid #e5e7eb;
            margin-top: 18px;
            padding: 20px;
            text-align: center;
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
    @php
        $logoPath = public_path('storage/Logo/logo_epo.png');
    @endphp

    <div class="brand">
        @if(file_exists($logoPath))
            <img src="{{ $logoPath }}" alt="Logo EPO">
        @endif
        <div>
            <h1>État des paiements</h1>
            <div class="muted">
                Client : {{ $client?->name ?? 'Client' }}
                @if($client?->code_client)
                    - Code {{ $client->code_client }}
                @endif
            </div>
            <div class="muted">Exporté le {{ $dateExport }} - {{ $periodLabel }}</div>
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><strong>Total paiements :</strong> {{ number_format((int) ($stats->total_count ?? 0), 0, ',', ' ') }}</td>
            <td><strong>Groupes :</strong> {{ number_format($paiementGroups->count(), 0, ',', ' ') }}</td>
            <td><strong>Chèques :</strong> {{ number_format((int) ($stats->total_cheques ?? 0), 0, ',', ' ') }}</td>
            <td><strong>Total général :</strong> {{ number_format((float) ($stats->total_montant ?? 0), 2, ',', ' ') }} DA</td>
        </tr>
    </table>

    @forelse($paiementGroups as $group)
        <section class="group">
            <div class="group-heading">
                <h2>
                    {{ $group->is_direct ? 'Paiement direct' : 'Chèque N° '.$group->numero_cheque }}
                    - {{ number_format((float) $group->total_montant, 2, ',', ' ') }} DA
                </h2>
                <div class="muted">
                    Banque : {{ $group->banque ?? '-' }}
                    - Date récente : {{ $group->date_paiement ? \Carbon\Carbon::parse($group->date_paiement)->format('d/m/Y') : '-' }}
                    - {{ $group->factures_count }} facture(s)
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 13%;">N° reçu</th>
                        <th style="width: 14%;">N° facture</th>
                        <th>Banque</th>
                        <th style="width: 13%;">Mode</th>
                        <th style="width: 10%;">Date</th>
                        <th style="width: 13%;">Statut facture</th>
                        <th class="right" style="width: 14%;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->paiements as $paiement)
                        @php
                            $facture = $paiement->facture;
                            $statutFacture = '-';

                            if ($facture?->annuler) {
                                $statutFacture = 'Annulée';
                            } elseif ($facture && (float) $facture->reste_a_payer <= 0) {
                                $statutFacture = 'Payée';
                            } elseif ($facture?->date_echeance && $facture->date_echeance->lt(today())) {
                                $statutFacture = 'En retard';
                            } elseif ($facture) {
                                $statutFacture = 'Impayée';
                            }
                        @endphp
                        <tr>
                            <td>{{ $paiement->recu ?? '-' }}</td>
                            <td>{{ $facture?->numero_facture ?? '-' }}</td>
                            <td>{{ $paiement->banque ?? $group->banque ?? '-' }}</td>
                            <td>{{ $modeLabels[(int) $paiement->mode_paiement] ?? '-' }}</td>
                            <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $statutFacture }}</td>
                            <td class="right">{{ number_format((float) $paiement->montant, 2, ',', ' ') }} DA</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" class="right">Total groupe</td>
                        <td class="right">{{ number_format((float) $group->total_montant, 2, ',', ' ') }} DA</td>
                    </tr>
                </tbody>
            </table>
        </section>
    @empty
        <div class="empty">Aucune donnée pour les critères sélectionnés.</div>
    @endforelse

    <div class="footer">E-Client EPO - export paiements client</div>
</body>
</html>
