{{-- resources/views/admins/imports/index.blade.php --}}
@extends('admins.layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto py-8 px-4" x-data="importUploader()" x-init="init()">

        <h1 class="text-2xl font-medium text-gray-900 dark:text-white mb-6">
            Import depuis l'ERP BIG
        </h1>

        {{-- ── Formulaire multi-fichiers ──────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                Déposez un ou plusieurs fichiers. L'import s'exécute toujours dans l'ordre
                <strong class="text-gray-700 dark:text-gray-200">Factures → Prestations → Paiements</strong>.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">

                {{-- Zone Factures --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-4
                                                                    border-2 border-dashed rounded-xl cursor-pointer transition-colors
                                                                    border-amber-300 dark:border-amber-700
                                                                    hover:bg-amber-50 dark:hover:bg-amber-900/20"
                    @click="$refs.fileFactures.click()" :class="files.factures ? 'bg-amber-50 dark:bg-amber-900/20' : ''">

                    {{-- Icône --}}
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z" />
                    </svg>

                    <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Factures</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center"
                        x-text="files.factures ? files.factures.name : 'Cliquer ou glisser'">
                    </span>

                    {{-- Badge check --}}
                    <span x-show="files.factures"
                        class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <input x-ref="fileFactures" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.factures = $event.target.files[0]" />
                </div>

                {{-- Zone Prestations --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-4
                                                                    border-2 border-dashed rounded-xl cursor-pointer transition-colors
                                                                    border-teal-300 dark:border-teal-700
                                                                    hover:bg-teal-50 dark:hover:bg-teal-900/20"
                    @click="$refs.filePrestations.click()"
                    :class="files.prestations ? 'bg-teal-50 dark:bg-teal-900/20' : ''">

                    <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6h16M4 10h16M4 14h10M4 18h6" />
                    </svg>

                    <span class="text-xs font-medium text-teal-700 dark:text-teal-300">Prestations</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center"
                        x-text="files.prestations ? files.prestations.name : 'Cliquer ou glisser'">
                    </span>

                    <span x-show="files.prestations"
                        class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <input x-ref="filePrestations" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.prestations = $event.target.files[0]" />
                </div>

                {{-- Zone Paiements --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-4
                                                                    border-2 border-dashed rounded-xl cursor-pointer transition-colors
                                                                    border-blue-300 dark:border-blue-700
                                                                    hover:bg-blue-50 dark:hover:bg-blue-900/20"
                    @click="$refs.filePaiements.click()" :class="files.paiements ? 'bg-blue-50 dark:bg-blue-900/20' : ''">

                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>

                    <span class="text-xs font-medium text-blue-700 dark:text-blue-300">Paiements</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center"
                        x-text="files.paiements ? files.paiements.name : 'Cliquer ou glisser'">
                    </span>

                    <span x-show="files.paiements"
                        class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <input x-ref="filePaiements" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.paiements = $event.target.files[0]" />
                </div>
                {{-- Zone Factures Payées --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-4
                                            border-2 border-dashed rounded-xl cursor-pointer transition-colors
                                            border-green-300 dark:border-green-700
                                            hover:bg-green-50 dark:hover:bg-green-900/20"
                    @click="$refs.fileFacturesPayees.click()"
                    :class="files.factures_payees ? 'bg-green-50 dark:bg-green-900/20' : ''">

                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium text-green-700 dark:text-green-300">Factures Payées</span>
                    <span class="text-xs text-gray-400 text-center"
                        x-text="files.factures_payees ? files.factures_payees.name : 'Cliquer ou glisser'"></span>

                    <span x-show="files.factures_payees"
                        class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <input x-ref="fileFacturesPayees" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.factures_payees = $event.target.files[0]" />
                </div>

                {{-- Zone Prestations Payées --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-4
                                            border-2 border-dashed rounded-xl cursor-pointer transition-colors
                                            border-purple-300 dark:border-purple-700
                                            hover:bg-purple-50 dark:hover:bg-purple-900/20"
                    @click="$refs.filePrestationsPayees.click()"
                    :class="files.prestations_payees ? 'bg-purple-50 dark:bg-purple-900/20' : ''">

                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Prestations Payées</span>
                    <span class="text-xs text-gray-400 text-center"
                        x-text="files.prestations_payees ? files.prestations_payees.name : 'Cliquer ou glisser'"></span>

                    <span x-show="files.prestations_payees"
                        class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>

                    <input x-ref="filePrestationsPayees" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.prestations_payees = $event.target.files[0]" />
                </div>
            </div>

            <button @click="submit()" :disabled="!hasAnyFile || isProcessing"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium
                                                                   text-white bg-blue-600 hover:bg-blue-700 transition-colors
                                                                   disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed">

                <svg x-show="!isProcessing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <svg x-show="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>

                <span
                    x-text="isProcessing
                                                            ? 'Import en cours…'
                                                            : 'Lancer l\'import (' + fileCount + ' fichier' + (fileCount > 1 ? 's' : '') + ')'">
                </span>
            </button>
        </div>

        {{-- ── 3 barres de progression ─────────────────────────────────────────── --}}
        <template x-for="(prog, type) in progresses" :key="type">
            <div x-show="prog.visible" x-transition
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">

                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        {{-- Pastille couleur par type --}}
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="{
                    'bg-amber-400':  type === 'factures',
                    'bg-teal-400':   type === 'prestations',
                    'bg-blue-400':   type === 'paiements',
                    'bg-green-400':  type === 'factures_payees',
                    'bg-purple-400': type === 'prestations_payees',
                }"></span>

                        {{-- Label lisible (remplace capitalize qui affiche "factures_payees" brut) --}}
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="{
                          factures:           'Factures',
                          prestations:        'Prestations',
                          paiements:          'Paiements',
                          factures_payees:    'Factures Payées',
                          prestations_payees: 'Prestations Payées',
                      }[type] ?? type">
                        </span>

                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100 capitalize" x-text="type"></span>

                        {{-- Badge statut --}}
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="{
                                                                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': prog.status === 'pending',
                                                                          'bg-blue-100   text-blue-800   dark:bg-blue-900   dark:text-blue-200':   prog.status === 'processing',
                                                                          'bg-green-100  text-green-800  dark:bg-green-900  dark:text-green-200':  prog.status === 'completed',
                                                                          'bg-red-100    text-red-800    dark:bg-red-900    dark:text-red-200':    prog.status === 'failed',
                                                                      }" x-text="statusLabels[prog.status] ?? prog.status">
                        </span>

                        <span class="text-xs text-gray-400 dark:text-gray-500"
                            x-text="prog.started_at ? 'démarré ' + prog.started_at : 'en attente de la chaîne…'">
                        </span>
                    </div>

                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        x-text="prog.percentage + '%'"></span>
                </div>

                {{-- Barre --}}
                {{-- Barre de progression --}}
                <div class="h-2.5 rounded-full transition-all duration-500" :class="{
                'bg-amber-400':  type === 'factures'           && prog.status !== 'completed' && prog.status !== 'failed',
                'bg-teal-400':   type === 'prestations'        && prog.status !== 'completed' && prog.status !== 'failed',
                'bg-blue-400':   type === 'paiements'          && prog.status !== 'completed' && prog.status !== 'failed',
                'bg-green-400':  type === 'factures_payees'    && prog.status !== 'completed' && prog.status !== 'failed',
                'bg-purple-400': type === 'prestations_payees' && prog.status !== 'completed' && prog.status !== 'failed',
                'bg-green-500':  prog.status === 'completed',
                'bg-red-500':    prog.status === 'failed',
            }" :style="'width: ' + prog.percentage + '%'">
                </div>

                {{-- Compteurs --}}
                <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>
                        <strong class="text-gray-800 dark:text-gray-200"
                            x-text="prog.processed.toLocaleString('fr-DZ')"></strong>
                        <span x-text="' / ' + prog.total.toLocaleString('fr-DZ') + ' lignes'"></span>
                    </span>
                    <span x-show="prog.failed > 0" class="text-red-500">
                        <strong x-text="prog.failed"></strong> ignorées
                    </span>
                    <span x-show="prog.status === 'completed'" class="text-green-600 dark:text-green-400">
                        Terminé le <span x-text="prog.completed_at"></span>
                    </span>
                    <span x-show="prog.status === 'failed'" class="text-red-500">
                        Échec — vérifiez les logs
                    </span>
                </div>
            </div>
        </template>

        {{-- ── Historique ───────────────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-base font-medium text-gray-900 dark:text-white">Historique des imports</h2>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Fichier</th>
                        <th class="px-5 py-3 text-left">Type</th>
                        <th class="px-5 py-3 text-left">Statut</th>
                        <th class="px-5 py-3 text-right">Lignes</th>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($batches as $batch)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-5 py-3 text-gray-900 dark:text-white font-mono text-xs
                                                                                                                                               truncate max-w-[160px]"
                                            title="{{ $batch->original_filename }}">
                                            {{ $batch->original_filename }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @php
                                                $typeColors = [
                                                    'factures' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                                    'prestations' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
                                                    'paiements' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    'factures_payees' => 'bg-green-100  text-green-800  dark:bg-green-900  dark:text-green-200',
                                                    'prestations_payees' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',

                                                ];
                                                $typeLabels = [
                                                    'factures' => 'Factures',
                                                    'prestations' => 'Prestations',
                                                    'paiements' => 'Paiements',
                                                    'factures_payees' => 'Factures Payées',
                                                    'prestations_payees' => 'Prestations Payées',
                                                ];
                                            @endphp
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$batch->type] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ $typeLabels[$batch->type] ?? ucfirst($batch->type) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                    'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                ];
                                                $statusLabels = [
                                                    'pending' => 'En attente',
                                                    'processing' => 'En cours',
                                                    'completed' => 'Terminé',
                                                    'failed' => 'Échec',
                                                ];
                                            @endphp
                                            <span
                                                class="px-2 py-0.5 rounded-full text-xs font-medium
                                                                                                                                                     {{ $statusColors[$batch->status] ?? '' }}">
                                                {{ $statusLabels[$batch->status] ?? $batch->status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right text-gray-600 dark:text-gray-300 tabular-nums">
                                            {{ number_format($batch->processed_rows, 0, ',', ' ') }}
                                            / {{ number_format($batch->total_rows, 0, ',', ' ') }}
                                            @if($batch->failed_rows > 0)
                                                <span class="text-red-400 text-xs ml-1">({{ $batch->failed_rows }} ign.)</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400 tabular-nums">
                                            {{ $batch->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                            {{ $batch->creator?->name ?? '—' }}
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                                Aucun import effectué.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $batches->links() }}
            </div>
        </div>
    </div>


    <script>
        function importUploader() {
            return {
                // ── Fichiers sélectionnés ─────────────────────────────────────────
                files: {
                    factures: null,
                    prestations: null,
                    paiements: null,
                    factures_payees: null,   // ← nouveau
                    prestations_payees: null,   // ← nouveau
                },

                // ── État général ──────────────────────────────────────────────────
                isProcessing: false,
                pollingInterval: null,

                // ── Batch IDs retournés par le serveur ────────────────────────────
                batchIds: {
                    factures: null,
                    prestations: null,
                    paiements: null,
                    factures_payees: null,   // ← nouveau
                    prestations_payees: null,   // ← nouveau
                },

                // ── Progression par type ──────────────────────────────────────────
                progresses: {
                    factures: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    prestations: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    paiements: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    factures_payees: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    prestations_payees: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                },

                statusLabels: {
                    pending: 'En attente',
                    processing: 'En cours',
                    completed: 'Terminé',
                    failed: 'Échec',
                },

                // ── Computed ──────────────────────────────────────────────────────
                get hasAnyFile() {
                    return Object.values(this.files).some(f => f !== null);
                },

                get fileCount() {
                    return Object.values(this.files).filter(f => f !== null).length;
                },

                get allDone() {
                    return Object.entries(this.batchIds).every(([type, id]) => {
                        if (!id) return true; // pas envoyé = ignoré
                        const s = this.progresses[type].status;
                        return s === 'completed' || s === 'failed';
                    });
                },

                // ── Init ──────────────────────────────────────────────────────────
                init() { },

                // ── Soumission ────────────────────────────────────────────────────
                async submit() {
                    if (!this.hasAnyFile || this.isProcessing) return;

                    this.isProcessing = true;

                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');

                    // ✅ Les 5 fichiers dans l'ordre de la chaîne
                    if (this.files.factures) formData.append('file_factures', this.files.factures);
                    if (this.files.prestations) formData.append('file_prestations', this.files.prestations);
                    if (this.files.paiements) formData.append('file_paiements', this.files.paiements);
                    if (this.files.factures_payees) formData.append('file_factures_payees', this.files.factures_payees);
                    if (this.files.prestations_payees) formData.append('file_prestations_payees', this.files.prestations_payees);

                    try {
                        const res = await fetch('{{ route("admin.imports.store") }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        const text = await res.text();
                        let data;

                        try {
                            data = JSON.parse(text);
                        } catch {
                            console.error('Réponse brute reçue :', text.substring(0, 500));
                            const hint = res.status === 413
                                ? 'Fichier trop volumineux — augmentez upload_max_filesize dans php.ini'
                                : `Le serveur a retourné du HTML (status ${res.status}). Voir la console.`;
                            throw new Error(hint);
                        }

                        if (!res.ok) {
                            console.error('Réponse erreur complète :', data);
                            const msg = data.message
                                ?? Object.values(data.errors ?? {}).flat().join('\n')
                                ?? `Erreur ${res.status}`;
                            throw new Error(msg);
                        }

                        // ── Succès : initialiser les barres pour chaque batch retourné ───
                        for (const [type, id] of Object.entries(data.batch_ids)) {
                            this.batchIds[type] = id;
                            this.progresses[type].visible = true;
                            this.progresses[type].status = 'pending';
                            this.progresses[type].percentage = 0;
                        }

                        this.startPolling();

                    } catch (err) {
                        alert('Erreur lors de l\'upload :\n' + err.message);
                        this.isProcessing = false;
                    }
                },

                // ── Helper : message d'erreur selon le code HTTP ─────────────────────
                buildUploadErrorMessage(status, hint) {
                    const messages = {
                        413: 'Fichier trop volumineux (erreur 413).\n' +
                            'Contactez l\'administrateur serveur pour augmenter upload_max_filesize.',
                        422: 'Données invalides : ' + hint,
                        500: 'Erreur interne du serveur (500).\n' + hint,
                        502: 'Le serveur est temporairement indisponible (502).',
                        503: 'Service indisponible (503). Réessayez dans quelques instants.',
                    };
                    return messages[status] ??
                        `Erreur HTTP ${status} : ${hint}`;
                },
                // ── Polling ───────────────────────────────────────────────────────
                startPolling() {
                    // Poll toutes les 2s pour chaque batch soumis
                    this.pollingInterval = setInterval(() => this.fetchAllProgress(), 2000);
                },

                async fetchAllProgress() {
                    // ✅ Tous les 5 types surveillés
                    const types = [
                        'factures',
                        'prestations',
                        'paiements',
                        'factures_payees',
                        'prestations_payees',
                    ];

                    await Promise.all(
                        types
                            .filter(t => this.batchIds[t] !== null)
                            .map(t => this.fetchProgress(t))
                    );

                    if (this.allDone) {
                        clearInterval(this.pollingInterval);
                        this.isProcessing = false;
                        setTimeout(() => window.location.reload(), 2000);
                    }
                },

                async fetchProgress(type) {
                    const id = this.batchIds[type];
                    if (!id) return;

                    try {
                        const res = await fetch(`{{ url('admin/imports') }}/${id}/progress`);
                        const data = await res.json();

                        this.progresses[type] = {
                            ...this.progresses[type],
                            ...data,
                            visible: true,
                        };
                    } catch (err) {
                        console.error(`Erreur polling ${type}:`, err);
                    }
                },

                destroy() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                },
            };
        }
    </script>

@endsection