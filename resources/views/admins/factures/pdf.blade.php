<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture ?? $facture->id }}</title>
    <style>
        /* 1. Configuration des marges de la page pour laisser la place aux images */
        @page {
            margin: 150px 30px 120px 30px;
            /* Haut, Droite, Bas, Gauche */
        }

        /* 
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } */

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            color: #2c3e50;
            line-height: 1.6;
        }

        /* 2. Positionnement fixe de l'en-tête (répété sur chaque page) */
        header {
            position: fixed;
            top: -140px;
            left: 0;
            right: 0;
            height: 120px;
            width: 100%;
        }

        header img {
            width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: contain;
        }

        /* 3. Positionnement fixe du pied de page (répété sur chaque page) */
        footer {
            position: fixed;
            bottom: -110px;
            left: 0;
            right: 0;
            height: 90px;
            width: 100%;
        }

        footer img {
            width: 100%;
            height: auto;
            max-height: 90px;
            object-fit: contain;
        }

        /* Styles pour le contenu de la facture */
        main {
            padding-top: 10px;
        }

        /* 2. Positionnement fixe de l'en-tête (répété sur chaque page) */
        header {
            position: fixed;
            top: -130px;
            /* Remonte dans la marge du @page */
            left: 0;
            right: 0;
            height: 110px;
        }

        header img {
            width: 100%;
            /* S'adapte à la largeur de la page */
            height: auto;
        }

        /* 3. Positionnement fixe du pied de page (répété sur chaque page) */
        footer {
            position: fixed;
            bottom: -90px;
            /* Descend dans la marge basse du @page */
            left: 0;
            right: 0;
            height: 70px;
        }

        footer img {
            width: 100%;
            height: auto;
        }


        .facture-title {
            font-size: 18px;
            font-weight: bold;
            color: #003d99;
            margin-bottom: 5px;
        }

        .facture-number {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }

        .info-box {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-col.right {
            padding-right: 0;
            padding-left: 15px;
        }

        .section-title {
            font-weight: bold;
            color: #003d99;
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 2px solid #003d99;
            padding-bottom: 5px;
        }

        .client-box {
            border: 2px solid #003d99;
            padding: 12px;
            background-color: #f5f7fa;
            border-radius: 4px;
        }

        .client-box p {
            margin: 4px 0;
            font-size: 11px;
        }

        .client-box strong {
            color: #003d99;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .info-label {
            display: table-cell;
            width: 40%;
            font-weight: 600;
            color: #003d99;
            font-size: 11px;
        }

        .info-value {
            display: table-cell;
            width: 60%;
            color: #555;
            font-size: 11px;
        }

        /* Tableau des prestations */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
        }

        .items-table thead {
            background-color: #003d99;
            color: white;
        }

        .items-table th {
            /* padding: 10px 8px; */
            border: 1px solid #003d99;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }

        .items-table td {
            /* padding: 10px 8px; */
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .items-table tbody tr:hover {
            background-color: #f0f4ff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* Tableau des totaux */
        .totals-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .totals-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            padding-left: 15px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            /* padding: 10px; */
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .totals-table .label {
            width: 60%;
            background-color: #f5f7fa;
            font-weight: 600;
            color: #003d99;
            text-align: right;
        }

        .totals-table .value {
            width: 40%;
            text-align: right;
            background-color: #fff;
        }

        .totals-table .total-row .label {
            background-color: #003d99;
            color: white;
            font-size: 13px;
            font-weight: bold;
        }

        .totals-table .total-row .value {
            background-color: #003d99;
            color: white;
            font-size: 13px;
            font-weight: bold;
        }

        /* Section paiement */
        .payment-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #f5f7fa;
            border: 1px solid #003d99;
            border-radius: 4px;
        }

        .payment-section .section-title {
            margin-top: 0;
        }

        .payment-grid {
            display: table;
            width: 100%;
            gap: 15px;
        }

        .payment-col {
            display: table-cell;
            width: 33%;
            padding-right: 15px;
            font-size: 11px;
        }

        .payment-col.last {
            padding-right: 0;
        }

        .payment-item {
            margin-bottom: 12px;
        }

        .payment-label {
            font-weight: 600;
            color: #003d99;
            margin-bottom: 3px;
        }

        .payment-value {
            /* color: #555; */
            color: #ff0000;
            padding: 5px 8px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 3px;
        }

        /* Montant en lettres */
        .amount-letters {
            margin-top: 20px;
            padding: 12px;
            background-color: #fff3cd;
            border-left: 4px solid #ff9800;
            font-size: 11px;
            color: #555;
        }

        .amount-letters strong {
            color: #003d99;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <header>
        <img src="{{ public_path('storage/Logo/entete.png') }}" alt="En-tête de l'entreprise">
    </header>

    <footer>
        <img src="{{ public_path('storage/Logo/footer.png') }}" alt="Pied de page de l'entreprise">
    </footer>

    <main>

        <!-- En-tête avec numéro de facture -->
        <!-- <div class="header-content">
            <div class="header-left">
                <div class="facture-title">FACTURE DE PRESTATIONS PORTUAIRES</div>
                <div class="facture-number">N° {{ $facture->numero_facture ?? 'N/A' }}</div>
            </div>
            <div class="header-right">
                <div class="facture-number">
                    <strong>Date :</strong> {{ $facture->date_facture ? \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y') : date('d/m/Y') }}
                </div>
            </div>
        </div> -->

        <!-- Informations générales -->
        <div class="info-box">
            <div class="info-col">
                <div class="section-title">Informations Navire</div>
                <div class="info-row">
                    <div class="info-label">Nom du Navire :</div>
                    <div class="info-value">{{ $facture->navire->nom ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pavillon :</div>
                    <div class="info-value">{{ $facture->navire->pavillon ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">N° Bordereau :</div>
                    <div class="info-value">{{ $facture->bordereau ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Entrée au port :</div>
                    <div class="info-value">{{ $facture->navire->date_arrivee ? \Carbon\Carbon::parse($facture->navire->date_arrivee)->format('d/m/Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sortie du port :</div>
                    <div class="info-value">{{ $facture->navire->date_sortie ? \Carbon\Carbon::parse($facture->navire->date_sortie)->format('d/m/Y') : 'N/A' }}</div>
                </div>
            </div>

            <div class="info-col right">
                <div class="section-title">Informations Client</div>
                <div class="client-box">
                    <p><strong>{{ $facture->client->name ?? 'N/A' }}</strong></p>
                    <p><strong>Code Client :</strong> {{ $facture->client->code_client ?? 'N/A' }}</p>
                    <p style="margin-top: 8px;"><strong>Adresse :</strong> {{ $facture->client->adresse ?? 'Non renseignée' }}</p>
                    <p><strong>NIF :</strong> {{ $facture->client->nif ?? 'N/A' }}</p>
                    <p><strong>RC :</strong> {{ $facture->client->rc ?? 'N/A' }}</p>
                    @if($facture->client->artisan ?? null)
                    <p><strong>N° Artisan :</strong> {{ $facture->client->artisan }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tableau des prestations détaillé -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 6%;">Code</th>
                    <th style="width: 45%;">Produit / Prestation</th>
                    <th style="width: 10%;">Quantité</th>
                    <th style="width: 12%;">P.U. (DA)</th>
                    <th style="width: 12%;">Taux/Jrs</th>
                    <th style="width: 15%;">Total H.T (DA)</th>
                    <!-- <th style="width: 10%;">TVA %</th> -->
                </tr>
            </thead>
            <tbody>
                @php
                $total_ht = 0;
                $total_tva = 0;
                $total_ttc = 0;
                @endphp
                @forelse($facture->prestations as $index => $prestation)
                <tr>
                    <td class="text-center">{{ $prestation->code ?? 'PRE-' . ($index + 1) }}</td>
                    <td class="text-left">{{ $prestation->designation ?? $prestation->produit->nom ?? 'Prestation Portuaire' }}</td>
                    <td class="text-center">{{ $prestation->quantite ?? 1 }}</td>
                    <td class="text-right">{{ number_format($prestation->prix_unitaire ?? $prestation->pu ?? 0, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ $prestation->taux_jrs ?? '-' }}</td>
                    <td class="text-right"><strong>{{ number_format($prestation->total_ht ?? $prestation->montant ?? 0, 2, ',', ' ') }}</strong></td>
                    <!-- <td class="text-center">{{ $prestation->taux_tva ?? '19%' }}</td> -->
                </tr>
                @php
                $total_ht += $prestation->total_ht ?? $prestation->montant ?? 0;
                $tva_amount = ($prestation->total_ht ?? $prestation->montant ?? 0) * 0.19;
                $total_tva += $tva_amount;
                $total_ttc += ($prestation->total_ht ?? $prestation->montant ?? 0) + $tva_amount;
                @endphp
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Aucune prestation disponible</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totaux et section paiement -->
        <div class="totals-section">
            <div class="totals-left">
                <!-- Montant en lettres -->
                <div class="amount-letters">
                    <strong>Arrêtée la présente facture à la somme de :</strong><br>
                    <strong>{{ $montantEnLettres ?? 'À calculer' }}</strong>
                </div>
            </div>

            <div class="totals-right">
                <table class="totals-table">
                    <tr>
                        <td class="label">Total Hors Taxe :</td>
                        <td class="value">{{ number_format($total_ht ?? $facture->total_ht ?? 0, 2, ',', ' ') }} DA</td>
                    </tr>
                    <tr>
                        <td class="label">TVA (19%) :</td>
                        <td class="value">{{ number_format($total_tva ?? $facture->tva ?? 0, 2, ',', ' ') }} DA</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">TOTAL T.T.C :</td>
                        <td class="value">{{ number_format($total_ttc ?? $facture->total_ttc ?? 0, 2, ',', ' ') }} DA</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Section Paiement -->
        <div class="payment-section">
            <div class="section-title">Conditions de Paiement</div>
            <div class="payment-grid">
                <div class="payment-col">
                    <div class="payment-item">
                        <div class="payment-label">Règlement :</div>
                        <div class="payment-value">
                            @if($facture->type_reglement ?? null)
                            {{ $facture->type_reglement }}
                            @else
                            À déterminer
                            @endif
                        </div>
                    </div>
                </div>
                <div class="payment-col">
                    <div class="payment-item">
                        <div class="payment-label">Mode de Paiement :</div>
                        <div class="payment-value">
                            @php
                            $modes = [
                            1 => 'Virement',
                            2 => 'Chèque',
                            3 => 'Espèce'
                            ];
                            @endphp

                            {{ $modes[$facture->mode_paiement] ?? 'Virement Bancaire / Chèque' }}
                        </div>
                    </div>
                </div>
                <div class="payment-col last">
                    <div class="payment-item">
                        <div class="payment-label">Client en Compte :</div>
                        <div class="payment-value font-red-500">
                            @if($facture->client->en_compte ?? false)
                            Oui
                            @else
                            Non
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <!-- <div class="signatures">
            <div class="signature-col">
                <div class="signature-line">Responsable de Facturation</div>
            </div>
            <div class="signature-col">
                <div class="signature-line">Directeur Général</div>
            </div>
        </div> -->

        <!-- Notes -->
        <!-- <div class="notes">
            <p><strong>Notes importantes :</strong></p>
            <p>• La facture doit être réglée dans les délais convenus<br>
                • Pour toute réclamation, veuillez contacter le service facturation dans les 30 jours<br>
                • Les retards de paiement entraîneront des intérêts de 5% par an à partir de la date d'échéance</p>
        </div> -->

    </main>
</body>

</html>