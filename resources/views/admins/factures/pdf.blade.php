<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture ?? $facture->id }}</title>
    <style>
        /* 1. Configuration des marges de la page pour laisser la place aux images */
        @page {
            margin: 140px 25px 100px 25px;
            /* Haut, Droite, Bas, Gauche */
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #333;
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

        /* Styles pour le contenu de la facture */
        main {
            padding-top: 10px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-section td {
            vertical-align: top;
            padding: 5px;
        }

        .client-box {
            border: 1px solid #000;
            padding: 15px;
            border-radius: 5px;
            background-color: #fcfcfc;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #000;
        }

        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table th,
        .totals-table td {
            padding: 8px;
            border: 1px solid #000;
        }

        .totals-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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

        <table class="info-section">
            <tr>
                <td style="width: 50%;">
                    <h2>FACTURE N° {{ $facture->numero_facture }}</h2>
                    <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y') }}</p>
                    <p><strong>Navire :</strong> {{ $facture->navire->nom ?? 'N/A' }}</p>
                </td>
                <td style="width: 50%;">
                    <div class="client-box">
                        <strong style="font-size: 16px;">Client : {{ $facture->client->name }}</strong><br><br>
                        <strong>Adresse :</strong> {{ $facture->client->adresse ?? 'Non renseignée' }}<br>
                        <strong>NIF :</strong> {{ $facture->client->nif ?? 'N/A' }}<br>
                        <strong>RC :</strong> {{ $facture->client->rc ?? 'N/A' }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%;">N°</th>
                    <th style="width: 60%;">Description de la prestation</th>
                    <th style="width: 30%;">Montant HT (DA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facture->prestations as $index => $prestation)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $prestation->designation ?? 'Prestation Portuaire' }}</td>
                    <td class="text-right">{{ number_format($prestation->total_ht, 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <th>Total HT</th>
                <td class="text-right">{{ number_format($facture->total_ht, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <th>TVA (19%)</th>
                <td class="text-right">{{ number_format($facture->tva, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <th><strong style="font-size: 14px;">Total TTC</strong></th>
                <td class="text-right"><strong style="font-size: 14px;">{{ number_format($facture->total_ttc, 2, ',', ' ') }} DA</strong></td>
            </tr>
        </table>

        <div style="clear: both; margin-top: 60px;">
            <p>Arrêtée la présente facture à la somme de : <br>
                <strong style="text-transform: uppercase; font-size: 14px;">
                    {{ $montantEnLettres }}
                </strong>
            </p>
        </div>

    </main>
</body>

</html>