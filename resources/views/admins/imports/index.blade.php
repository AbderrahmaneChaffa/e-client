@extends('admins.layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto py-8 px-4" x-data="importUploader()" x-init="init()">

        <h1 class="text-2xl font-medium text-gray-900 dark:text-white mb-6">
            Import depuis l'ERP BIG
        </h1>

        {{-- ── Formulaire d'upload ─────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                {{-- Type de fichier --}}
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Type de fichier</label>
                    <select x-model="type"
                        :disabled="isProcessing"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                        <option value="factures">Factures</option>
                        <option value="prestations">Prestations</option>
                        <option value="paiements">Paiements</option>
                    </select>
                </div>

                {{-- Fichier --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Fichier Excel (.xlsx)</label>
                    <input type="file"
                        accept=".xlsx,.xls"
                        :disabled="isProcessing"
                        @change="onFileChange($event)"
                        class="w-full text-sm text-gray-600 dark:text-gray-400
                              file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                              dark:file:bg-blue-900 dark:file:text-blue-300
                              disabled:opacity-50 cursor-pointer" />
                </div>
            </div>

            <button @click="submit()"
                :disabled="!file || isProcessing"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                       disabled:bg-gray-300 dark:disabled:bg-gray-600
                       text-white text-sm font-medium rounded-lg transition-colors">
                <svg x-show="!isProcessing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <svg x-show="isProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>
                <span x-text="isProcessing ? 'Traitement en cours…' : 'Lancer l\'import'"></span>
            </button>
        </div>

        {{-- ── Barre de progression ─────────────────────────────────────────── --}}
        <div x-show="batchId" x-transition class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8">

            {{-- En-tête statut --}}
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    {{-- Badge statut --}}
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        :class="{
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': progress.status === 'pending',
                          'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200':   progress.status === 'processing',
                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': progress.status === 'completed',
                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200':    progress.status === 'failed',
                      }">
                        <span class="w-1.5 h-1.5 rounded-full"
                            :class="{
                              'bg-yellow-500': progress.status === 'pending',
                              'bg-blue-500 animate-pulse': progress.status === 'processing',
                              'bg-green-500': progress.status === 'completed',
                              'bg-red-500':   progress.status === 'failed',
                          }"></span>
                        <span x-text="statusLabel"></span>
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400"
                        x-text="progress.started_at ? 'Démarré ' + progress.started_at : ''"></span>
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    x-text="progress.percentage + '%'"></span>
            </div>

            {{-- Barre --}}
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden mb-3">
                <div class="h-3 rounded-full transition-all duration-500"
                    :class="{
                     'bg-blue-500': progress.status !== 'completed' && progress.status !== 'failed',
                     'bg-green-500': progress.status === 'completed',
                     'bg-red-500':   progress.status === 'failed',
                 }"
                    :style="'width: ' + progress.percentage + '%'">
                </div>
            </div>

            {{-- Compteurs --}}
            <div class="flex gap-6 text-sm text-gray-600 dark:text-gray-400">
                <span>
                    <strong class="text-gray-900 dark:text-white" x-text="progress.processed.toLocaleString('fr-DZ')"></strong>
                    / <span x-text="progress.total.toLocaleString('fr-DZ')"></span> lignes traitées
                </span>
                <span x-show="progress.failed > 0" class="text-red-600 dark:text-red-400">
                    <strong x-text="progress.failed"></strong> erreurs
                </span>
                <span x-show="progress.status === 'completed'" class="text-green-600 dark:text-green-400">
                    Terminé le <span x-text="progress.completed_at"></span>
                </span>
            </div>
        </div>

        {{-- ── Historique des imports ───────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-base font-medium text-gray-900 dark:text-white">Historique des imports</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Fichier</th>
                        <th class="px-6 py-3 text-left">Type</th>
                        <th class="px-6 py-3 text-left">Statut</th>
                        <th class="px-6 py-3 text-right">Lignes</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-3 text-gray-900 dark:text-white font-mono text-xs truncate max-w-[180px]"
                            title="{{ $batch->original_filename }}">
                            {{ $batch->original_filename }}
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300 capitalize">{{ $batch->type }}</td>
                        <td class="px-6 py-3">
                            @php
                            $colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            ];
                            $labels = [
                            'pending' => 'En attente', 'processing' => 'En cours',
                            'completed' => 'Terminé', 'failed' => 'Échec',
                            ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$batch->status] ?? '' }}">
                                {{ $labels[$batch->status] ?? $batch->status }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-300">
                            {{ number_format($batch->processed_rows, 0, ',', ' ') }}
                            / {{ number_format($batch->total_rows, 0, ',', ' ') }}
                            @if($batch->failed_rows > 0)
                            <span class="text-red-500 text-xs">({{ $batch->failed_rows }} err.)</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">
                            {{ $batch->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">
                            {{ $batch->creator?->name ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                            Aucun import effectué.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $batches->links() }}
            </div>
        </div>
    </div>

   
    <script>
        function importUploader() {
            return {
                type: 'factures',
                file: null,
                batchId: null,
                isProcessing: false,
                pollingInterval: null,
                progress: {
                    status: 'pending',
                    processed: 0,
                    total: 0,
                    failed: 0,
                    percentage: 0,
                    started_at: null,
                    completed_at: null,
                },

                get statusLabel() {
                    const labels = {
                        pending: 'En attente',
                        processing: 'En cours',
                        completed: 'Terminé',
                        failed: 'Échec',
                    };
                    return labels[this.progress.status] ?? this.progress.status;
                },

                init() {},

                onFileChange(event) {
                    this.file = event.target.files[0] ?? null;
                },

                async submit() {
                    if (!this.file || this.isProcessing) return;

                    this.isProcessing = true;
                    this.batchId = null;

                    const formData = new FormData();
                    formData.append('type', this.type);
                    formData.append('file', this.file);
                    formData.append('_token', '{{ csrf_token() }}');

                    try {
                        const res = await fetch('{{ route("admin.imports.store") }}', {
                            method: 'POST',
                            body: formData,
                        });

                        if (!res.ok) throw new Error(await res.text());

                        const data = await res.json();
                        this.batchId = data.batch_id;
                        this.startPolling();

                    } catch (err) {
                        alert('Erreur lors de l\'upload : ' + err.message);
                        this.isProcessing = false;
                    }
                },

                startPolling() {
                    this.pollingInterval = setInterval(() => this.fetchProgress(), 2000);
                },

                async fetchProgress() {
                    if (!this.batchId) return;

                    try {
                        const res = await fetch(`{{ url('admin/imports') }}/${this.batchId}/progress`);
                        this.progress = await res.json();

                        if (['completed', 'failed'].includes(this.progress.status)) {
                            clearInterval(this.pollingInterval);
                            this.isProcessing = false;
                            // Recharger le tableau d'historique
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    } catch (err) {
                        console.error('Erreur polling progression:', err);
                    }
                },

                destroy() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                },
            };
        }
    </script>
   
@endsection