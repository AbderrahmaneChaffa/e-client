{{-- resources/views/admins/imports/index.blade.php --}}
@extends('admins.layouts.admin')

@section('content')
    <div class="max-w-5xl mx-auto py-8 px-4" x-data="importUploader()" x-init="init()">

        {{-- ── En-tête ──────────────────────────────────────────────────────────── --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Import ERP BIG</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                Importez vos exports Excel dans l'ordre correct. La chaîne s'exécute automatiquement en arrière-plan.
            </p>
        </div>

        {{-- ── Bannière ordre d'exécution ─────────────────────────────────────────── --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800
                                            rounded-xl p-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Ordre d'exécution garanti :</strong>
                Factures
                <span class="mx-1 text-blue-400">→</span> Prestations
                <span class="mx-1 text-blue-400">→</span> Paiements
                <span class="mx-1 text-blue-400">→</span> Factures Payées
                <span class="mx-1 text-blue-400">→</span> Prestations Payées.
                Chaque fichier est optionnel — seuls les fichiers déposés seront traités.
            </div>
        </div>

        {{-- ── Zones de dépôt ──────────────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">

            <div class="mb-5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Upload automatique</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selectionnez 1 a 5 fichiers, dans n'importe quel ordre.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <input x-ref="adaptiveFiles" type="file" accept=".xlsx,.xls" multiple class="hidden"
                            @change="setAdaptiveFiles($event)" />
                        <button type="button" @click="$refs.adaptiveFiles.click()"
                            class="px-3 py-2 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Choisir fichiers
                        </button>
                        <button type="button" @click="preview()" :disabled="!hasAnyFile || isProcessing"
                            class="px-3 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 disabled:bg-gray-300 dark:disabled:bg-gray-600">
                            Previsualiser
                        </button>
                    </div>
                </div>
                <div x-show="adaptiveFiles.length" x-cloak class="mt-3 flex flex-wrap gap-2">
                    <template x-for="file in adaptiveFiles" :key="file.name + file.size">
                        <span class="px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300"
                            x-text="file.name"></span>
                    </template>
                </div>
            </div>

            {{-- Ligne 1 : 3 zones --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">

                {{-- Factures --}}
                {{-- <x-import-drop-zone ref="fileFactures" model="factures" label="Factures" color="amber"
                    icon="document" />

                @php
                $zones = [
                [
                'ref' => 'fileFactures',
                'model' => 'factures',
                'label' => 'Factures',
                'color' => 'amber',
                'icon' => 'document',
                ],
                [
                'ref' => 'filePrestations',
                'model' => 'prestations',
                'label' => 'Prestations',
                'color' => 'teal',
                'icon' => 'list',
                ],
                [
                'ref' => 'filePaiements',
                'model' => 'paiements',
                'label' => 'Paiements',
                'color' => 'blue',
                'icon' => 'payment',
                ],
                ];
                @endphp --}}

                {{-- Factures --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-5
                                                    border-2 border-dashed rounded-xl cursor-pointer select-none
                                                    border-amber-300 dark:border-amber-700 transition-colors duration-200
                                                    hover:bg-amber-50 dark:hover:bg-amber-900/20"
                    @click="$refs.fileFactures.click()"
                    :class="files.factures ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-400' : ''">
                    <svg class="w-9 h-9 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">Factures</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight"
                        x-text="files.factures ? files.factures.name : 'Cliquer ou glisser un fichier .xlsx'"></span>
                    <span x-show="files.factures" x-cloak class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full
                                                         flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <input x-ref="fileFactures" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.factures = $event.target.files[0]" />
                </div>

                {{-- Prestations --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-5
                                                    border-2 border-dashed rounded-xl cursor-pointer select-none
                                                    border-teal-300 dark:border-teal-700 transition-colors duration-200
                                                    hover:bg-teal-50 dark:hover:bg-teal-900/20"
                    @click="$refs.filePrestations.click()"
                    :class="files.prestations ? 'bg-teal-50 dark:bg-teal-900/20 border-teal-400' : ''">
                    <svg class="w-9 h-9 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6h16M4 10h16M4 14h10M4 18h6" />
                    </svg>
                    <span class="text-xs font-semibold text-teal-700 dark:text-teal-300">Prestations</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight"
                        x-text="files.prestations ? files.prestations.name : 'Cliquer ou glisser un fichier .xlsx'"></span>
                    <span x-show="files.prestations" x-cloak class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full
                                                         flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <input x-ref="filePrestations" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.prestations = $event.target.files[0]" />
                </div>

                {{-- Paiements --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-5
                                                    border-2 border-dashed rounded-xl cursor-pointer select-none
                                                    border-blue-300 dark:border-blue-700 transition-colors duration-200
                                                    hover:bg-blue-50 dark:hover:bg-blue-900/20"
                    @click="$refs.filePaiements.click()"
                    :class="files.paiements ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-400' : ''">
                    <svg class="w-9 h-9 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Paiements</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight"
                        x-text="files.paiements ? files.paiements.name : 'Cliquer ou glisser un fichier .xlsx'"></span>
                    <span x-show="files.paiements" x-cloak class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full
                                                         flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <input x-ref="filePaiements" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.paiements = $event.target.files[0]" />
                </div>
            </div>

            {{-- Séparateur avec label --}}
            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide px-2">
                    Historique des paiements
                </span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            {{-- Ligne 2 : 2 zones centrées --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto mb-6">

                {{-- Factures Payées --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-5
                                                    border-2 border-dashed rounded-xl cursor-pointer select-none
                                                    border-green-300 dark:border-green-700 transition-colors duration-200
                                                    hover:bg-green-50 dark:hover:bg-green-900/20"
                    @click="$refs.fileFacturesPayees.click()"
                    :class="files.factures_payees ? 'bg-green-50 dark:bg-green-900/20 border-green-400' : ''">
                    <svg class="w-9 h-9 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-semibold text-green-700 dark:text-green-300">Factures Payées</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight"
                        x-text="files.factures_payees ? files.factures_payees.name : 'Cliquer ou glisser un fichier .xlsx'"></span>
                    <span x-show="files.factures_payees" x-cloak class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full
                                                         flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <input x-ref="fileFacturesPayees" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.factures_payees = $event.target.files[0]" />
                </div>

                {{-- Prestations Payées --}}
                <div class="relative flex flex-col items-center justify-center gap-2 p-5
                                                    border-2 border-dashed rounded-xl cursor-pointer select-none
                                                    border-purple-300 dark:border-purple-700 transition-colors duration-200
                                                    hover:bg-purple-50 dark:hover:bg-purple-900/20"
                    @click="$refs.filePrestationsPayees.click()"
                    :class="files.prestations_payees ? 'bg-purple-50 dark:bg-purple-900/20 border-purple-400' : ''">
                    <svg class="w-9 h-9 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                                         M9 5a2 2 0 002 2h2a2 2 0 002-2
                                                         M9 5a2 2 0 012-2h2a2 2 0 012 2
                                                         m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">Prestations Payées</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 text-center leading-tight"
                        x-text="files.prestations_payees ? files.prestations_payees.name : 'Cliquer ou glisser un fichier .xlsx'"></span>
                    <span x-show="files.prestations_payees" x-cloak class="absolute top-2 right-2 w-5 h-5 bg-green-500 rounded-full
                                                         flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <input x-ref="filePrestationsPayees" type="file" accept=".xlsx,.xls" class="hidden"
                        @change="files.prestations_payees = $event.target.files[0]" />
                </div>
            </div>

            {{-- Bouton lancer --}}
            <div class="flex flex-wrap items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" x-model="forceImport"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Import force
                </label>
                <button @click="submit()" :disabled="!hasAnyFile || isProcessing" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold
                                                       text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                                                       disabled:bg-gray-300 dark:disabled:bg-gray-600 disabled:cursor-not-allowed
                                                       transition-colors shadow-sm">
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
                                                ? 'Traitement en cours…'
                                                : 'Lancer l\'import (' + fileCount + ' fichier' + (fileCount > 1 ? 's' : '') + ')'">
                    </span>
                </button>

                {{-- Compteur fichiers sélectionnés --}}
                <span x-show="hasAnyFile && !isProcessing" class="text-sm text-gray-500 dark:text-gray-400">
                    <span x-text="fileCount"></span> fichier(s) prêt(s)
                </span>
            </div>
        </div>

        {{-- ── Barres de progression ────────────────────────────────────────────────── --}}
        <div x-show="previewReport" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Apercu pre-import</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="previewReport?.summary?.rows ?? 0"></span> lignes detectees,
                        <span x-text="previewReport?.summary?.created ?? 0"></span> nouvelles,
                        <span x-text="previewReport?.summary?.updated ?? 0"></span> mises a jour,
                        <span x-text="previewReport?.summary?.skipped ?? 0"></span> ignorees.
                    </p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                    :class="previewReport?.valid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    x-text="previewReport?.valid ? 'Format valide' : 'A corriger'"></span>
            </div>

            <div class="grid gap-3">
                <template x-for="file in previewReport?.files ?? []" :key="file.filename">
                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="file.filename"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Type: <span x-text="file.type"></span> -
                                    Lignes: <span x-text="file.row_count"></span> -
                                    TTC: <span x-text="formatAmount(file.totals?.total_ttc ?? 0)"></span> DA
                                </p>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <span x-text="file.impact.created"></span> C /
                                <span x-text="file.impact.updated"></span> M /
                                <span x-text="file.impact.skipped"></span> I
                            </div>
                        </div>
                        <div x-show="file.missing_headers?.length" class="mt-2 text-xs text-red-600">
                            Colonnes manquantes: <span x-text="file.missing_headers.join(', ')"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        @php
            $typeConfig = [
                'factures' => ['label' => 'Factures', 'color' => 'amber'],
                'prestations' => ['label' => 'Prestations', 'color' => 'teal'],
                'paiements' => ['label' => 'Paiements', 'color' => 'blue'],
                'factures_payees' => ['label' => 'Factures Payées', 'color' => 'green'],
                'prestations_payees' => ['label' => 'Prestations Payées', 'color' => 'purple'],
            ];
        @endphp

        <template x-for="(prog, type) in progresses" :key="type">
            <div x-show="prog.visible" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200
                                                dark:border-gray-700 p-5 mb-3">

                {{-- En-tête de la barre --}}
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2 min-w-0">

                        {{-- Pastille couleur --}}
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="{
                                                    'bg-amber-400':  type === 'factures',
                                                    'bg-teal-400':   type === 'prestations',
                                                    'bg-blue-400':   type === 'paiements',
                                                    'bg-green-400':  type === 'factures_payees',
                                                    'bg-purple-400': type === 'prestations_payees',
                                                    'animate-pulse': prog.status === 'processing',
                                                }"></span>

                        {{-- Label lisible --}}
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="{
                                                    factures:           'Factures',
                                                    prestations:        'Prestations',
                                                    paiements:          'Paiements',
                                                    factures_payees:    'Factures Payées',
                                                    prestations_payees: 'Prestations Payées',
                                                }[type] ?? type"></span>

                        {{-- Badge statut --}}
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0" :class="{
                                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300': prog.status === 'pending',
                                                    'bg-blue-100   text-blue-700   dark:bg-blue-900/50   dark:text-blue-300':   prog.status === 'processing',
                                                    'bg-green-100  text-green-700  dark:bg-green-900/50  dark:text-green-300':  prog.status === 'completed',
                                                    'bg-red-100    text-red-700    dark:bg-red-900/50    dark:text-red-300':    prog.status === 'failed',
                                                }"
                            x-text="{ pending: 'En attente', processing: 'En cours', completed: 'Terminé', failed: 'Échec' }[prog.status] ?? prog.status">
                        </span>

                        {{-- Temps écoulé --}}
                        <span class="text-xs text-gray-400 dark:text-gray-500 truncate hidden sm:block"
                            x-text="prog.started_at ? 'démarré ' + prog.started_at : ''">
                        </span>
                    </div>

                    {{-- Pourcentage --}}
                    <span class="text-sm font-bold tabular-nums ml-3 flex-shrink-0" :class="{
                                                      'text-gray-700 dark:text-gray-300': prog.status !== 'completed' && prog.status !== 'failed',
                                                      'text-green-600': prog.status === 'completed',
                                                      'text-red-600':   prog.status === 'failed',
                                                  }" x-text="prog.percentage + '%'">
                    </span>
                </div>

                {{-- Barre de progression --}}
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden mb-3">
                    <div class="h-2 rounded-full transition-all duration-500 ease-out" :class="{
                                                'bg-amber-400':  type === 'factures'           && prog.status !== 'completed' && prog.status !== 'failed',
                                                'bg-teal-400':   type === 'prestations'        && prog.status !== 'completed' && prog.status !== 'failed',
                                                'bg-blue-400':   type === 'paiements'          && prog.status !== 'completed' && prog.status !== 'failed',
                                                'bg-green-400':  type === 'factures_payees'    && prog.status !== 'completed' && prog.status !== 'failed',
                                                'bg-purple-400': type === 'prestations_payees' && prog.status !== 'completed' && prog.status !== 'failed',
                                                'bg-green-500':  prog.status === 'completed',
                                                'bg-red-500':    prog.status === 'failed',
                                            }" :style="'width: ' + prog.percentage + '%'">
                    </div>
                </div>

                {{-- Compteurs --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs">
                    <span class="text-gray-500 dark:text-gray-400 tabular-nums">
                        <strong class="text-gray-800 dark:text-gray-200"
                            x-text="prog.processed.toLocaleString('fr-FR')"></strong>
                        <span x-text="' / ' + prog.total.toLocaleString('fr-FR') + ' lignes'"></span>
                    </span>

                    <span x-show="prog.failed > 0" class="text-red-500 font-medium">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        <span x-text="prog.failed.toLocaleString('fr-FR')"></span> ignorées
                    </span>

                    <span x-show="prog.status === 'completed'" class="text-green-600 dark:text-green-400 font-medium">
                        <i class="fa-solid fa-circle-check mr-1"></i>
                        Terminé le <span x-text="prog.completed_at"></span>
                    </span>

                    <span x-show="prog.status === 'failed'" class="text-red-500 font-medium">
                        <i class="fa-solid fa-circle-xmark mr-1"></i>
                        Import échoué — consultez les logs Laravel
                    </span>

                    <span x-show="prog.status === 'pending'" class="text-gray-400 dark:text-gray-500 italic">
                        En attente du job précédent…
                    </span>
                </div>
            </div>
        </template>

        {{-- ── Historique des imports ───────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                    Historique des imports
                </h2>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $batches->total() }} import(s) au total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                        <tr class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <th class="px-5 py-3 text-left">Fichier</th>
                            <th class="px-5 py-3 text-left">Type</th>
                            <th class="px-5 py-3 text-left">Statut</th>
                            <th class="px-5 py-3 text-right">Lignes</th>
                            <th class="px-5 py-3 text-right hidden md:table-cell">Ignorées</th>
                            <th class="px-5 py-3 text-left hidden sm:table-cell">Date</th>
                            <th class="px-5 py-3 text-left hidden md:table-cell">Par</th>
                            {{-- En-tête --}}
                            <th class="px-5 py-3 text-center hidden md:table-cell">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($batches as $batch)
                            @php
                                $typeColors = [
                                    'factures' => 'bg-amber-100  text-amber-800  dark:bg-amber-900/50  dark:text-amber-200',
                                    'prestations' => 'bg-teal-100   text-teal-800   dark:bg-teal-900/50   dark:text-teal-200',
                                    'paiements' => 'bg-blue-100   text-blue-800   dark:bg-blue-900/50   dark:text-blue-200',
                                    'factures_payees' => 'bg-green-100  text-green-800  dark:bg-green-900/50  dark:text-green-200',
                                    'prestations_payees' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-200',
                                ];
                                $typeLabels = [
                                    'factures' => 'Factures',
                                    'prestations' => 'Prestations',
                                    'paiements' => 'Paiements',
                                    'factures_payees' => 'Factures Payées',
                                    'prestations_payees' => 'Prestations Payées',
                                ];
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                    'processing' => 'bg-blue-100   text-blue-700   dark:bg-blue-900/50   dark:text-blue-300',
                                    'completed' => 'bg-green-100  text-green-700  dark:bg-green-900/50  dark:text-green-300',
                                    'failed' => 'bg-red-100    text-red-700    dark:bg-red-900/50    dark:text-red-300',
                                ];
                                $statusLabels = [
                                    'pending' => 'En attente',
                                    'processing' => 'En cours',
                                    'completed' => 'Terminé',
                                    'failed' => 'Échec',
                                ];
                                $progressPct = $batch->total_rows > 0
                                    ? min(100, round($batch->processed_rows / $batch->total_rows * 100))
                                    : ($batch->status === 'completed' ? 100 : 0);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                                {{-- Fichier --}}
                                <td class="px-5 py-3 max-w-[180px]">
                                    <span class="font-mono text-xs text-gray-700 dark:text-gray-300 block truncate"
                                        title="{{ $batch->original_filename }}">
                                        {{ $batch->original_filename }}
                                    </span>
                                </td>

                                {{-- Type --}}
                                <td class="px-5 py-3">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-medium
                                                                                                 {{ $typeColors[$batch->type] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $typeLabels[$batch->type] ?? ucfirst($batch->type) }}
                                    </span>
                                </td>

                                {{-- Statut --}}
                                <td class="px-5 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-xs font-medium w-fit
                                                                                                     {{ $statusColors[$batch->status] ?? '' }}">
                                            {{ $statusLabels[$batch->status] ?? $batch->status }}
                                        </span>
                                        @if(in_array($batch->status, ['processing', 'completed']))
                                            <div class="w-20 h-1 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                                <div class="h-1 rounded-full {{ $batch->status === 'completed' ? 'bg-green-500' : 'bg-blue-400' }}"
                                                    style="width: {{ $progressPct }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                {{-- Lignes --}}
                                <td class="px-5 py-3 text-right tabular-nums">
                                    <span class="font-medium text-gray-800 dark:text-gray-200 text-xs">
                                        {{ number_format($batch->processed_rows, 0, ',', ' ') }}
                                    </span>
                                    <span class="text-gray-400 text-xs">
                                        / {{ number_format($batch->total_rows, 0, ',', ' ') }}
                                    </span>
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        +{{ number_format($batch->created_rows ?? 0, 0, ',', ' ') }}
                                        / ~{{ number_format($batch->updated_rows ?? 0, 0, ',', ' ') }}
                                        / ={{ number_format($batch->skipped_rows ?? 0, 0, ',', ' ') }}
                                    </div>
                                </td>

                                {{-- Ignorées --}}
                                <td class="px-5 py-3 text-right hidden md:table-cell">
                                    @if($batch->failed_rows > 0)
                                        <span class="text-red-500 text-xs font-medium tabular-nums">
                                            {{ number_format($batch->failed_rows, 0, ',', ' ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="px-5 py-3 hidden sm:table-cell">
                                    <span class="text-gray-500 dark:text-gray-400 text-xs tabular-nums">
                                        {{ $batch->created_at->format('d/m/Y') }}
                                    </span>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs block">
                                        {{ $batch->created_at->format('H:i') }}
                                    </span>
                                </td>

                                {{-- Par --}}
                                <td class="px-5 py-3 hidden md:table-cell">
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $batch->creator?->name ?? '—' }}
                                    </span>
                                </td>


                                {{-- Cellule dans le @forelse --}}
                                <td class="px-5 py-3 text-center hidden md:table-cell">
                                    @if(in_array($batch->status, ['completed', 'failed', 'pending']))
                                        <button onclick="deleteBatch({{ $batch->id }}, '{{ $batch->original_filename }}')"
                                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50
                                                                                           dark:hover:bg-red-900/20 rounded-lg transition-all" title="Supprimer">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </button>
                                    @else
                                        {{-- En cours : bouton désactivé --}}
                                        <span class="p-1.5 text-gray-200 dark:text-gray-600 cursor-not-allowed"
                                            title="Import en cours — suppression impossible">
                                            <i class="fa-solid fa-trash text-sm"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <i class="fa-solid fa-inbox text-4xl text-gray-200 dark:text-gray-600 mb-3 block"></i>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm">Aucun import effectué.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
    <script>
        // ── Fonction globale (EN DEHORS de importUploader) ────────────────────────
        async function deleteBatch(id, filename) {
            if (!confirm(`Supprimer l'import "${filename}" ?\nLe fichier Excel sera également effacé.`)) {
                return;
            }

            try {
                const res = await fetch(`{{ url('admin/imports') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });

                const data = await res.json();

                if (!res.ok) {
                    alert(data.message ?? 'Erreur lors de la suppression.');
                    return;
                }

                window.location.reload();

            } catch (err) {
                alert('Erreur réseau : ' + err.message);
            }
        }

        // ── Composant Alpine.js ───────────────────────────────────────────────────
        function importUploader() {
            return {
                files: {
                    factures: null,
                    prestations: null,
                    paiements: null,
                    factures_payees: null,
                    prestations_payees: null,
                },
                adaptiveFiles: [],
                forceImport: false,
                previewReport: null,

                isProcessing: false,
                pollingInterval: null,

                batchIds: {
                    factures: null,
                    prestations: null,
                    paiements: null,
                    factures_payees: null,
                    prestations_payees: null,
                },

                progresses: {
                    factures: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    prestations: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    paiements: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    factures_payees: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                    prestations_payees: { visible: false, status: 'pending', processed: 0, total: 0, failed: 0, percentage: 0, started_at: null, completed_at: null },
                },

                // ── Computed ──────────────────────────────────────────────────────
                get hasAnyFile() {
                    return this.adaptiveFiles.length > 0 || Object.values(this.files).some(f => f !== null);
                },

                get fileCount() {
                    return this.adaptiveFiles.length + Object.values(this.files).filter(f => f !== null).length;
                },

                get allDone() {
                    return Object.entries(this.batchIds).every(([type, id]) => {
                        if (!id) return true;
                        const s = this.progresses[type].status;
                        return s === 'completed' || s === 'failed';
                    });
                },

                // ── Init ──────────────────────────────────────────────────────────
                init() { },

                setAdaptiveFiles(event) {
                    this.adaptiveFiles = Array.from(event.target.files || []).slice(0, 5);
                    this.previewReport = null;
                },

                appendFiles(formData) {
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('force_import', this.forceImport ? '1' : '0');
                    this.adaptiveFiles.forEach(file => formData.append('files[]', file));
                    if (this.files.factures) formData.append('file_factures', this.files.factures);
                    if (this.files.prestations) formData.append('file_prestations', this.files.prestations);
                    if (this.files.paiements) formData.append('file_paiements', this.files.paiements);
                    if (this.files.factures_payees) formData.append('file_factures_payees', this.files.factures_payees);
                    if (this.files.prestations_payees) formData.append('file_prestations_payees', this.files.prestations_payees);
                },

                formatAmount(value) {
                    return Number(value || 0).toLocaleString('fr-DZ', { maximumFractionDigits: 2 });
                },

                async preview() {
                    if (!this.hasAnyFile || this.isProcessing) return;
                    const formData = new FormData();
                    this.appendFiles(formData);

                    try {
                        const res = await fetch('{{ route("admin.imports.preview") }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData,
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message ?? 'Erreur preview');
                        this.previewReport = data;
                    } catch (err) {
                        alert('Erreur de previsualisation :\n' + err.message);
                    }
                },

                // ── Soumission ────────────────────────────────────────────────────
                async submit() {
                    if (!this.hasAnyFile || this.isProcessing) return;

                    this.isProcessing = true;

                    const formData = new FormData();
                    this.appendFiles(formData);

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
                            console.error('Réponse brute :', text.substring(0, 500));
                            throw new Error(
                                res.status === 413
                                    ? 'Fichier trop volumineux. Augmentez upload_max_filesize dans php.ini.'
                                    : `Réponse non-JSON (HTTP ${res.status}). Voir la console.`
                            );
                        }

                        if (!res.ok) {
                            console.error('Erreur serveur :', data);
                            throw new Error(
                                data.message
                                ?? Object.values(data.errors ?? {}).flat().join('\n')
                                ?? `Erreur ${res.status}`
                            );
                        }

                        // Initialiser les barres pour chaque batch créé
                        for (const [type, id] of Object.entries(data.batch_ids)) {
                            this.batchIds[type] = id;
                            this.progresses[type].visible = true;
                            this.progresses[type].status = 'pending';
                            this.progresses[type].percentage = 0;
                            this.progresses[type].processed = 0;
                            this.progresses[type].total = 0;
                        }

                        this.startPolling();

                    } catch (err) {
                        alert('Erreur lors de l\'upload :\n' + err.message);
                        this.isProcessing = false;
                    }
                },

                // ── Polling ───────────────────────────────────────────────────────
                startPolling() {
                    this.pollingInterval = setInterval(() => this.fetchAllProgress(), 2000);
                },

                async fetchAllProgress() {
                    const types = [
                        'factures', 'prestations', 'paiements',
                        'factures_payees', 'prestations_payees',
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
                        if (!res.ok) return;
                        const data = await res.json();

                        this.progresses[type] = {
                            ...this.progresses[type],
                            ...data,
                            visible: true,
                        };
                    } catch (err) {
                        console.error(`Polling [${type}] :`, err);
                    }
                },

                destroy() {
                    if (this.pollingInterval) clearInterval(this.pollingInterval);
                },
            };
        }
    </script>
@endsection
