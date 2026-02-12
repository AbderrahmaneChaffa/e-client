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
        <canvas
            id="paymentChart"
            data-labels='@json($labels)'
            data-amounts='@json($amounts)'>
        </canvas>

        <script>

            document.addEventListener("DOMContentLoaded", () => {
                const canvas = document.getElementById('paymentChart');

                if (canvas) {
                    new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: JSON.parse(canvas.dataset.labels),
                            datasets: [{
                                label: 'Paiements Reçus',
                                data: JSON.parse(canvas.dataset.amounts),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        }
                    });
                }
            });
        </script>

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
@endsection