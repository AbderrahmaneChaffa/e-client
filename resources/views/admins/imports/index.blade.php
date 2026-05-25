{{-- // VIEW: admin.imports.index --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-stat-card>, <x-search-input>, <x-date-range-picker>, <x-badge>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: search, status, type, date range, per_page, export query params --}}
@php
    $pageTitle = 'Imports ERP BIG';
    $activeFilters = collect(['search', 'status', 'type', 'date_from', 'date_to', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
    $statusCounts = $batches->getCollection()->groupBy('status')->map->count();
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="importUploader()" x-init="init()" class="space-y-6">
    <x-page-header
        title="Imports ERP BIG"
        subtitle="Deposez jusqu'a 5 fichiers Excel; la sequence Factures, Prestations, Paiements est orchestree en arriere-plan."
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Imports']]"
    />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Imports visibles" :value="number_format($batches->count(), 0, ',', ' ')" icon="upload-cloud" color="primary" />
        <x-stat-card title="Termines" :value="number_format($statusCounts->get('completed', 0), 0, ',', ' ')" icon="check-circle-2" color="success" />
        <x-stat-card title="En cours" :value="number_format($statusCounts->get('processing', 0) + $statusCounts->get('pending', 0), 0, ',', ' ')" icon="loader-circle" color="info" />
        <x-stat-card title="Echecs" :value="number_format($statusCounts->get('failed', 0), 0, ',', ' ')" icon="circle-alert" color="danger" />
    </section>

    <section class="ui-card p-5">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Nouvel import</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Selectionnez vos fichiers Excel dans n'importe quel ordre.</p>
                </div>

                <div
                    class="rounded-lg border-2 border-dashed border-primary-200 bg-primary-50/60 p-6 text-center transition hover:border-primary-400 dark:border-primary-900/60 dark:bg-primary-900/10"
                    @dragover.prevent
                    @drop.prevent="setDroppedFiles($event)"
                >
                    <input x-ref="filesInput" type="file" class="hidden" accept=".xlsx,.xls" multiple @change="setFiles($event)">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-white text-primary-600 shadow-sm dark:bg-gray-800 dark:text-primary-300">
                        <i data-lucide="file-spreadsheet" class="h-7 w-7" aria-hidden="true"></i>
                    </div>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Glissez-deposez vos fichiers ici</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Formats acceptes: .xlsx, .xls · maximum 5 fichiers</p>
                    <button type="button" class="ui-btn-primary mt-5" @click="$refs.filesInput.click()">
                        <i data-lucide="folder-open" class="h-4 w-4" aria-hidden="true"></i>
                        Choisir fichiers
                    </button>
                </div>

                <template x-if="files.length">
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <template x-for="file in files" :key="file.name + file.size">
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="file-spreadsheet" class="h-5 w-5 text-success-600" aria-hidden="true"></i>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold" x-text="file.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes(file.size)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-info-200 bg-info-50 p-4 text-info-800 dark:border-info-900/60 dark:bg-info-900/20 dark:text-info-200">
                    <div class="flex items-start gap-3">
                        <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true"></i>
                        <p class="text-sm">Ordre garanti: Factures, Prestations, Paiements, Factures Payees, Prestations Payees.</p>
                    </div>
                </div>

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <input type="checkbox" x-model="forceImport" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900">
                    Forcer l'import meme si un doublon est detecte
                </label>

                <div class="grid grid-cols-1 gap-3">
                    <button type="button" class="ui-btn-secondary w-full" :disabled="!files.length || processing" @click="preview()">
                        <i data-lucide="scan-search" class="h-4 w-4" aria-hidden="true"></i>
                        Previsualiser
                    </button>
                    <button type="button" class="ui-btn-primary w-full" :disabled="!files.length || processing" @click="startImport()">
                        <i data-lucide="loader-circle" x-show="processing" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                        <span x-text="processing ? 'Traitement...' : 'Lancer import'"></span>
                    </button>
                </div>
            </aside>
        </div>

        <div x-show="message" x-cloak class="mt-5 rounded-lg border p-4 text-sm" :class="messageType === 'error' ? 'border-danger-200 bg-danger-50 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200' : 'border-success-200 bg-success-50 text-success-800 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200'">
            <p x-text="message"></p>
        </div>

        <div x-show="previewRows.length" x-cloak class="mt-5 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <h3 class="font-semibold">Previsualisation</h3>
            </div>
            <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                <template x-for="row in previewRows" :key="row.filename || row.type">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold" x-text="row.filename || row.original_filename || 'Fichier'"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="row.type || row.detected_type || 'Type detecte'"></p>
                            </div>
                            <span class="rounded-full bg-primary-100 px-2 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-200" x-text="row.rows || row.total_rows || 'OK'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="Object.keys(progress).length" x-cloak class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
            <template x-for="item in Object.values(progress)" :key="item.id">
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold" x-text="item.type"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.status"></p>
                        </div>
                        <span class="text-sm font-bold" x-text="`${item.percentage || 0}%`"></span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                        <div class="h-2 rounded-full bg-primary-600 transition-all" :style="`width: ${item.percentage || 0}%`"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-text="`${item.processed || 0}/${item.total || 0} lignes · ${item.eta || 'ETA indisponible'}`"></p>
                </div>
            </template>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.imports.index') }}" class="ui-card p-4">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-4"><x-search-input name="search" placeholder="Fichier, type, statut..." /></div>
            <div class="lg:col-span-2">
                <select name="status" class="ui-input" aria-label="Statut">
                    <option value="">Tous statuts</option>
                    @foreach(['pending' => 'En attente', 'processing' => 'En cours', 'completed' => 'Termine', 'failed' => 'Echec'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <select name="type" class="ui-input" aria-label="Type">
                    <option value="">Tous types</option>
                    @foreach(['factures', 'prestations', 'paiements', 'factures_payees', 'prestations_payees'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <select name="per_page" class="ui-input" aria-label="Entrees">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }} / page</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 lg:col-span-2">
                <button type="submit" class="ui-btn-primary flex-1">Filtrer</button>
                @if($activeFilters)
                    <a href="{{ route('admin.imports.index') }}" class="ui-btn-secondary"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></a>
                @endif
            </div>
        </div>
        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <x-date-range-picker />
        </div>
    </form>

    <section class="ui-card overflow-hidden">
        @if($batches->isEmpty())
            <div class="p-4">
                <x-empty-state icon="upload-cloud" title="Aucun import" message="L'historique des imports apparaitra ici apres le premier depot." />
            </div>
        @else
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Fichier</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Statut</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Lignes</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Cree par</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700" x-ref="historyTable">
                        @foreach($batches as $batch)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-4">
                                    <p class="max-w-xs truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $batch->original_filename }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">#{{ $batch->id }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm">{{ ucfirst(str_replace('_', ' ', $batch->type)) }}</td>
                                <td class="px-4 py-4"><x-badge :status="$batch->status" /></td>
                                <td class="px-4 py-4 text-right text-sm tabular-nums">{{ number_format($batch->processed_rows, 0, ',', ' ') }} / {{ number_format($batch->total_rows, 0, ',', ' ') }}</td>
                                <td class="px-4 py-4 text-sm">{{ $batch->creator?->name ?? '-' }}</td>
                                <td class="px-4 py-4 text-sm">{{ optional($batch->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.imports.show', $batch) }}" class="ui-icon-btn" aria-label="Voir details"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                        @if($batch->status !== 'processing')
                                            <button type="button" class="ui-icon-btn" @click="resume({{ $batch->id }})" aria-label="Relancer l'import"><i data-lucide="refresh-cw" class="h-4 w-4"></i></button>
                                        @endif
                                        @if(in_array($batch->status, ['completed', 'failed', 'pending'], true))
                                            <button type="button" class="ui-icon-btn text-danger-600 dark:text-danger-300" @click="destroy({{ $batch->id }})" aria-label="Supprimer l'import"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @foreach($batches as $batch)
                    <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $batch->original_filename }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $batch->type)) }}</p>
                            </div>
                            <x-badge :status="$batch->status" />
                        </div>
                        <div class="mt-4 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                            @php
                                $percent = $batch->total_rows > 0 ? min(100, round($batch->processed_rows / $batch->total_rows * 100)) : ($batch->status === 'completed' ? 100 : 0);
                            @endphp
                            <div class="h-2 rounded-full bg-primary-600" style="width: {{ $percent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ number_format($batch->processed_rows, 0, ',', ' ') }} / {{ number_format($batch->total_rows, 0, ',', ' ') }} lignes</p>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $batches->appends(request()->query())->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    function importUploader() {
        return {
            files: [],
            forceImport: false,
            processing: false,
            message: '',
            messageType: 'success',
            previewRows: [],
            progress: {},
            batchIds: [],
            poller: null,
            init() {},
            csrf() {
                return document.querySelector('meta[name="csrf-token"]').content;
            },
            setFiles(event) {
                this.files = Array.from(event.target.files || []).slice(0, 5);
                this.previewRows = [];
                this.message = '';
                this.$nextTick(() => window.lucide?.createIcons());
            },
            setDroppedFiles(event) {
                this.files = Array.from(event.dataTransfer.files || []).filter(file => /\.(xlsx|xls)$/i.test(file.name)).slice(0, 5);
                this.previewRows = [];
                this.message = '';
                this.$nextTick(() => window.lucide?.createIcons());
            },
            formData() {
                const data = new FormData();
                this.files.forEach(file => data.append('files[]', file));
                data.append('force_import', this.forceImport ? '1' : '0');
                return data;
            },
            async preview() {
                await this.postFiles(@js(route('admin.imports.preview')), true);
            },
            async startImport() {
                await this.postFiles(@js(route('admin.imports.store')), false);
            },
            async postFiles(url, previewOnly) {
                if (!this.files.length) return;
                this.processing = true;
                this.message = '';
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                        body: this.formData(),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'Operation impossible.');
                    this.messageType = 'success';
                    this.message = payload.message || (previewOnly ? 'Previsualisation terminee.' : 'Import lance.');
                    if (previewOnly) {
                        this.previewRows = Array.isArray(payload.files) ? payload.files : (Array.isArray(payload.preview) ? payload.preview : Object.values(payload));
                    } else {
                        this.batchIds = Object.values(payload.batch_ids || {});
                        this.progress = {};
                        this.startPolling();
                    }
                } catch (error) {
                    this.messageType = 'error';
                    this.message = error.message;
                } finally {
                    this.processing = false;
                    this.$nextTick(() => window.lucide?.createIcons());
                }
            },
            startPolling() {
                clearInterval(this.poller);
                this.fetchProgress();
                this.poller = setInterval(() => this.fetchProgress(), 2500);
            },
            async fetchProgress() {
                const ids = this.batchIds.join(',');
                const url = ids ? `${@js(route('admin.imports.progress-many'))}?ids=${encodeURIComponent(ids)}` : @js(route('admin.imports.progress-many'));
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                this.progress = payload.progress || {};
                const items = Object.values(this.progress);
                if (items.length && items.every(item => ['completed', 'failed'].includes(item.status))) {
                    clearInterval(this.poller);
                }
            },
            async resume(id) {
                if (!confirm('Relancer cet import ?')) return;
                await fetch(@js(url('/admin/imports')) + `/${id}/resume`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                });
                window.location.reload();
            },
            async destroy(id) {
                if (!confirm('Supprimer cet import ?')) return;
                await fetch(@js(url('/admin/imports')) + `/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                });
                window.location.reload();
            },
            formatBytes(bytes) {
                if (!bytes) return '0 o';
                const units = ['o', 'Ko', 'Mo', 'Go'];
                const index = Math.floor(Math.log(bytes) / Math.log(1024));
                return `${(bytes / Math.pow(1024, index)).toFixed(1)} ${units[index]}`;
            }
        }
    }
</script>
@endpush
