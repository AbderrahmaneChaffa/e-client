@php
    $pageTitle = 'Mon espace client';
    $recoveryClass = match ($recoveryTone) {
        'success' => 'text-success-700 dark:text-success-300',
        'primary' => 'text-primary-700 dark:text-primary-300',
        default => 'text-warning-700 dark:text-warning-300',
    };
    $recoveryRing = match ($recoveryTone) {
        'success' => 'bg-success-500',
        'primary' => 'bg-primary-500',
        default => 'bg-warning-500',
    };
    $nextDueBadge = $nextDueInvoice && $nextDueInvoice->date_echeance && $nextDueInvoice->date_echeance->lt(today())
        ? 'en_retard'
        : 'en_attente';
@endphp

@extends('clients.layouts.app')
@section('title', $pageTitle)

@section('content')
    <div class="space-y-6">
        <x-page-header title="Mon espace client" :subtitle="now()->locale('fr')->translatedFormat('l d F Y') . ' · Client ' . (auth()->user()->client?->code_client ?? '-')" :breadcrumbs="[['label' => 'Client'], ['label' => 'Dashboard']]">
            <a href="{{ route('client.factures.export.excel') }}" class="ui-btn-secondary min-h-11"
                aria-label="Exporter les factures en Excel">
                <i data-lucide="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>
                Excel
            </a>
            <a href="{{ route('client.factures.export.pdf') }}" class="ui-btn-danger min-h-11"
                aria-label="Exporter les factures en PDF">
                <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                PDF
            </a>
            <a href="{{ route('client.support.create') }}" class="ui-btn-secondary min-h-11"
                aria-label="Contacter le support">
                <i data-lucide="message-circle" class="h-4 w-4" aria-hidden="true"></i>
                Support
            </a>
        </x-page-header>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Factures" :value="number_format($totalCount, 0, ',', ' ')" icon="file-text"
                color="primary" />
            <x-stat-card title="Total TTC" :value="number_format($totalFacture, 0, ',', ' ') . ' DA'" icon="coins"
                color="info" />
            <x-stat-card title="Total payé" :value="number_format($totalPaye, 0, ',', ' ') . ' DA'" icon="circle-check"
                color="success" trend="up" trend-value="{{ number_format($recoveryRate, 1) }}%" />
            <x-stat-card title="Reste à payer" :value="number_format($totalDue, 0, ',', ' ') . ' DA'" icon="badge-alert"
                color="{{ $totalDue > 0 ? 'danger' : 'success' }}" />
        </section>

        <section class="grid grid-cols-1 gap-6 ">
            <article class="ui-card p-5 xl:col-span-2">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Factures sur 12 mois</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Évolution des montants réglés et restants à
                            payer.</p>
                    </div>
                    <a href="{{ route('client.factures.index') }}"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200">
                        Voir les factures
                    </a>
                </div>

                <div x-data="{ ready: false }" x-init="requestAnimationFrame(() => ready = true)">
                    <div x-show="!ready" x-cloak>
                        <x-loading-skeleton rows="3" />
                    </div>

                    <div x-show="ready" x-cloak class="h-80">
                        <canvas id="clientInvoicesChart" class="cursor-pointer"
                            aria-label="Graphique des factures sur 12 mois" role="img"></canvas>
                    </div>
                </div>
            </article>


        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="ui-card p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Taux de recouvrement</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Montant encaissé par rapport au total facturé.
                        </p>
                    </div>
                    <a href="{{ $recoveryRoute }}"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200">
                        Voir les paiements
                    </a>
                </div>

                <a href="{{ $recoveryRoute }}"
                    class="block rounded-2xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:border-primary-700 dark:hover:bg-primary-900/20">
                    <div class="relative mx-auto flex h-52 w-52 items-center justify-center">
                        <canvas id="clientRecoveryChart" class="cursor-pointer"
                            aria-label="Graphique du taux de recouvrement" role="img"></canvas>
                        <div
                            class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span
                                class="text-4xl font-extrabold {{ $recoveryClass }}">{{ number_format($recoveryRate, 1) }}%</span>
                            <span
                                class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">recouvré</span>
                        </div>
                    </div>
                </a>
            </article>
            <article class="ui-card p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Répartition des factures</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Payées, impayées et annulées.</p>
                    </div>
                </div>

                <div class="h-64">
                    <canvas id="clientDistributionChart" class="cursor-pointer" aria-label="Répartition des factures"
                        role="img"></canvas>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    @foreach($distributionCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="rounded-lg border border-gray-200 px-3 py-3 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $card['label'] }}</span>
                                <x-badge :status="$card['color']" :label="number_format($card['value'], 0, ',', ' ')" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="ui-card p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Actions rapides</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Accès direct aux principales actions du client.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('client.factures.export.excel') }}" class="ui-btn-secondary min-h-12 justify-start">
                    <i data-lucide="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>
                    Export Excel
                </a>
                <a href="{{ route('client.factures.export.pdf') }}" class="ui-btn-danger min-h-12 justify-start">
                    <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                    Export PDF
                </a>
                <a href="{{ route('client.factures.index') }}" class="ui-btn-secondary min-h-12 justify-start">
                    <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                    Voir toutes les factures
                </a>
                <a href="{{ route('client.support.create') }}" class="ui-btn-secondary min-h-12 justify-start">
                    <i data-lucide="message-circle" class="h-4 w-4" aria-hidden="true"></i>
                    Contacter le support
                </a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const dashboard = {
                recoveryRate: @js($recoveryRate),
                recoveryRoute: @js($recoveryRoute),
                recoveryTone: @js($recoveryTone),
                distribution: @js($distributionCards),
                monthlyLabels: @js($monthlyLabels),
                monthlyPaidValues: @js($monthlyPaidValues),
                monthlyRemainingValues: @js($monthlyRemainingValues),
                monthlyPaidRoutes: @js($monthlyPaidRoutes),
                monthlyRemainingRoutes: @js($monthlyRemainingRoutes),
            };

            let charts = [];

            const css = (name, fallback) => {
                const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                return value || fallback;
            };

            const palette = () => ({
                primary: css('--color-primary-600', '#4f46e5'),
                success: css('--color-success-600', '#16a34a'),
                warning: css('--color-warning-600', '#d97706'),
                danger: css('--color-danger-600', '#dc2626'),
                info: css('--color-info-600', '#0891b2'),
                slate: css('--color-slate-500', '#64748b'),
                text: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#334155',
                muted: document.documentElement.classList.contains('dark') ? '#475569' : '#cbd5e1',
                grid: document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,.18)' : 'rgba(148,163,184,.32)',
            });

            const toneColor = (tone) => {
                const map = {
                    success: palette().success,
                    primary: palette().primary,
                    warning: palette().warning,
                    danger: palette().danger,
                };

                return map[tone] || palette().primary;
            };

            const destroyCharts = () => {
                charts.forEach((chart) => chart.destroy());
                charts = [];
            };

            const renderCharts = () => {
                if (!window.Chart) {
                    return;
                }

                const colors = palette();
                destroyCharts();

                const recoveryCanvas = document.getElementById('clientRecoveryChart');
                if (recoveryCanvas) {
                    charts.push(new Chart(recoveryCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Recouvré', 'Restant'],
                            datasets: [{
                                data: [dashboard.recoveryRate, Math.max(0, 100 - dashboard.recoveryRate)],
                                backgroundColor: [toneColor(dashboard.recoveryTone), colors.muted],
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '74%',
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => `${ctx.label}: ${Number(ctx.raw).toFixed(1)}%`,
                                    },
                                },
                            },
                            onClick: () => {
                                window.location.href = dashboard.recoveryRoute;
                            },
                        },
                    }));
                }

                const distributionCanvas = document.getElementById('clientDistributionChart');
                if (distributionCanvas) {
                    const distributionRoutes = dashboard.distribution.map((item) => item.route);
                    const distributionColors = dashboard.distribution.map((item) => ({
                        success: colors.success,
                        warning: colors.warning,
                        danger: colors.danger,
                        slate: colors.slate,
                    }[item.color] || colors.primary));

                    charts.push(new Chart(distributionCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: dashboard.distribution.map((item) => item.label),
                            datasets: [{
                                data: dashboard.distribution.map((item) => item.value),
                                backgroundColor: distributionColors,
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: colors.text, usePointStyle: true, boxWidth: 8 },
                                },
                            },
                            onClick: (_, elements) => {
                                if (!elements.length) {
                                    return;
                                }

                                const index = elements[0].index;
                                const target = distributionRoutes[index];

                                if (target) {
                                    window.location.href = target;
                                }
                            },
                        },
                    }));
                }

                const invoicesCanvas = document.getElementById('clientInvoicesChart');
                if (invoicesCanvas) {
                    const paidRoutes = dashboard.monthlyPaidRoutes;
                    const remainingRoutes = dashboard.monthlyRemainingRoutes;

                    charts.push(new Chart(invoicesCanvas, {
                        type: 'bar',
                        data: {
                            labels: dashboard.monthlyLabels,
                            datasets: [
                                {
                                    label: 'Réglé',
                                    data: dashboard.monthlyPaidValues,
                                    backgroundColor: colors.success,
                                    borderRadius: 8,
                                    stack: 'invoices',
                                },
                                {
                                    label: 'Restant',
                                    data: dashboard.monthlyRemainingValues,
                                    backgroundColor: colors.warning,
                                    borderRadius: 8,
                                    stack: 'invoices',
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'nearest', intersect: true },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: colors.text, usePointStyle: true, boxWidth: 10 },
                                },
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: { color: colors.text },
                                    grid: { display: false },
                                },
                                y: {
                                    stacked: true,
                                    ticks: { color: colors.text },
                                    grid: { color: colors.grid },
                                },
                            },
                            onClick: (_, elements) => {
                                if (!elements.length) {
                                    return;
                                }

                                const element = elements[0];
                                const index = element.index;
                                const datasetIndex = element.datasetIndex;
                                const target = datasetIndex === 0 ? paidRoutes[index] : remainingRoutes[index];

                                if (target) {
                                    window.location.href = target;
                                }
                            },
                        },
                    }));
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderCharts, { once: true });
            } else {
                renderCharts();
            }

            window.addEventListener('theme-changed', renderCharts);
        })();
    </script>
@endpush