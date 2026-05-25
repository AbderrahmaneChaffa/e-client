@php
    $pageTitle = 'Import #'.$batch->id;
    $diffSummary = collect(data_get($batch->metadata, 'import_diffs', []));
    $statusColor = [
        'completed' => 'success',
        'failed' => 'danger',
        'processing' => 'info',
        'pending' => 'warning',
    ][$batch->status] ?? 'info';
@endphp

@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6">
    <x-page-header
        :title="$pageTitle"
        :subtitle="$batch->original_filename"
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Imports', 'url' => route('admin.imports.index')], ['label' => '#'.$batch->id]]"
    />

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Statut" :value="ucfirst($batch->status)" icon="clock" :color="$statusColor" />
        <x-stat-card title="Lignes traitees" :value="number_format($batch->processed_rows, 0, ',', ' ').' / '.number_format($batch->total_rows, 0, ',', ' ')" icon="file-text" color="info" />
        <x-stat-card title="Creees" :value="number_format($batch->created_rows, 0, ',', ' ')" icon="plus" color="success" />
        <x-stat-card title="Modifiees" :value="number_format($batch->updated_rows, 0, ',', ' ')" icon="refresh-cw" color="warning" />
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <article class="ui-card p-5 lg:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Details import</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $batch->type)) }}</p>
                </div>
                <x-badge :status="$batch->status" />
            </div>

            <div class="mt-5 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                <div class="h-2 rounded-full bg-primary-600" style="width: {{ $progress['percentage'] ?? 0 }}%"></div>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Cree par</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ $batch->creator?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Cree le</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ optional($batch->created_at)->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Debut</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ optional($batch->started_at)->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fin</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ optional($batch->completed_at)->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Lignes ignorees</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ number_format($batch->skipped_rows, 0, ',', ' ') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Lignes en erreur</dt>
                    <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ number_format($batch->failed_rows, 0, ',', ' ') }}</dd>
                </div>
            </dl>

            @if($batch->error_summary)
                <div class="mt-5 rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-900/60 dark:bg-danger-900/20 dark:text-danger-200">
                    {{ data_get($batch->error_summary, 'message', json_encode($batch->error_summary)) }}
                </div>
            @endif
        </article>

        <aside class="ui-card p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Synthese ecarts</h2>
            <div class="mt-4 space-y-3">
                @forelse(['new' => 'Nouveaux', 'modified' => 'Modifies', 'missing' => 'Manquants', 'inconsistent' => 'Incoherents'] as $key => $label)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900/60">
                        <span class="text-gray-600 dark:text-gray-300">{{ $label }}</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format((int) $diffSummary->get($key, 0), 0, ',', ' ') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucun ecart.</p>
                @endforelse
            </div>
        </aside>
    </section>

    <section class="ui-card overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Alertes de verification</h2>
        </div>
        @if($verifications->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-900/30 dark:text-success-300">
                    <i data-lucide="shield-check" class="h-7 w-7" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Aucune alerte</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">Aucune alerte de verification pour cet import.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($verifications as $verification)
                    <article class="p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ str_replace('_', ' ', $verification->rule_code) }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ number_format($verification->affected_count, 0, ',', ' ') }} facture(s) concernee(s)</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-xs font-bold uppercase {{ $verification->severity === 'critical' ? 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200' : 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-200' }}">
                                {{ $verification->severity }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="ui-card overflow-hidden">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Derniers ecarts</h2>
        </div>
        @if($diffs->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-900/30 dark:text-success-300">
                    <i data-lucide="circle-check" class="h-7 w-7" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Aucun ecart</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">Aucun ecart detaille pour cet import.</p>
            </div>
        @else
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Facture</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Criticite</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($diffs as $diff)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $diff->change_type }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $diff->label }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $diff->entity_type }} - {{ $diff->entity_key }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($diff->facture)
                                        <a href="{{ route('admin.factures.show', $diff->facture) }}" class="font-semibold text-primary-700 hover:underline dark:text-primary-200">
                                            {{ $diff->facture->numero_facture }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $diff->severity }}</td>
                                <td class="px-4 py-3 text-sm">{{ optional($diff->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @foreach($diffs as $diff)
                    <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $diff->label }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $diff->entity_type }} - {{ $diff->entity_key }}</p>
                            </div>
                            <span class="text-xs font-bold uppercase">{{ $diff->severity }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
