@extends('admins.layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800">Vue d'ensemble</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold mb-4 text-gray-700">Évolution des encaissements (DA)</h3>
        <canvas id="paymentChart" height="200"></canvas>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold mb-4 text-gray-700">Top 5 Clients Débiteurs</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-gray-400 border-b">
                        <th class="pb-3">Client</th>
                        <th class="pb-3 text-right">Reste à payer</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($topDebiteurs as $client)
                    <tr>
                        <td class="py-4 font-medium">{{ $client->name }}</td>
                        <td class="py-4 text-right text-red-500 font-bold">
                            {{ number_format($client->factures_sum_reste_a_payer, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
<script>
    const ctx = document.getElementById('paymentChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [12, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map(m => `Mois ${m}`),
            datasets: [{
                label: 'Paiements Reçus',
                data: [12000, 15000, 8000, 20000, 18000, 22000, 25000, 24000, 21000, 23000, 26000],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                fill: true,
                tension: 0.4
            }]
        }
    });
</script>
@endsection