@extends('admins.layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto py-10">

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-blue-900 to-blue-700 p-8 text-white">
            <h2 class="text-3xl font-bold flex items-center">
                <i class="fa-solid fa-database mr-4"></i> Importation
            </h2>
        </div>

        <div class="p-8">
            <div id="alert-box" class="hidden p-4 mb-6 rounded-lg text-sm font-medium"></div>

            <!-- tabs -->
            <div class="flex space-x-4 border-b mb-6">
                <button type="button" class="tab-btn px-4 py-2 font-medium text-gray-700 bg-gray-100 rounded-t-lg active" data-target="facturesForm">
                    Factures
                </button>
                <button type="button" class="tab-btn px-4 py-2 font-medium text-gray-700 bg-gray-100 rounded-t-lg" data-target="paiementsForm">
                    Paiements
                </button>
            </div>

            <!-- factures form -->
            <div id="facturesForm" class="tab-content">
                <p class="text-sm text-gray-600 mb-4">Le fichier doit contenir les en-têtes : <code>n_facture, date, client_code, client_nom, nis, t_ht, t_tva, t_ttc</code>. Téléchargez <a href="{{ route('admin.imports.template.factures') }}" class="text-blue-600 underline">le modèle</a>.</p>
                <form id="uploadForm-factures" action="{{ route('admin.imports.factures') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-center w-full mb-6">
                        <label class="flex flex-col items-center justify-center w-full h-64 border-2 border-blue-300 border-dashed rounded-lg cursor-pointer bg-blue-50 hover:bg-blue-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fa-solid fa-cloud-arrow-up text-5xl text-blue-500 mb-4"></i>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Cliquez pour upload</span> ou glissez le fichier</p>
                                <p class="text-xs text-gray-400">XLSX, CSV (Max 50Mo)</p>
                                <p class="mt-4 text-sm font-bold text-blue-700 file-name"></p>
                            </div>
                            <input type="file" name="file" class="hidden dropzone-file" accept=".xlsx,.csv" />
                        </label>
                    </div>
                    <div class="progress-wrapper hidden mb-6">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-blue-700">Envoi au serveur...</span>
                            <span class="text-sm font-medium text-blue-700 progress-percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="progress-bar bg-blue-600 h-4 rounded-full transition-all duration-75" style="width: 0%"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-lg px-5 py-4 text-center shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-play mr-2"></i> LANCER L'IMPORTATION
                    </button>
                </form>
            </div>

            <!-- paiements form (hidden initially) -->
            <div id="paiementsForm" class="tab-content hidden">
                <p class="text-sm text-gray-600 mb-4">Le fichier doit contenir les en-têtes : <code>n_facture, date, reference, numero_cheque, banque, montant</code>. Téléchargez <a href="{{ route('admin.imports.template.paiements') }}" class="text-green-600 underline">le modèle</a>.</p>
                <form id="uploadForm-paiements" action="{{ route('admin.imports.paiements') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center justify-center w-full mb-6">
                        <label class="flex flex-col items-center justify-center w-full h-64 border-2 border-green-300 border-dashed rounded-lg cursor-pointer bg-green-50 hover:bg-green-100 transition">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fa-solid fa-file-invoice-dollar text-5xl text-green-500 mb-4"></i>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Cliquez pour upload</span> ou glissez le fichier</p>
                                <p class="text-xs text-gray-400">XLSX, CSV (Max 50Mo)</p>
                                <p class="mt-4 text-sm font-bold text-green-700 file-name"></p>
                            </div>
                            <input type="file" name="file" class="hidden dropzone-file" accept=".xlsx,.csv" />
                        </label>
                    </div>
                    <div class="progress-wrapper hidden mb-6">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-green-700">Envoi au serveur...</span>
                            <span class="text-sm font-medium text-green-700 progress-percent">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="progress-bar bg-green-600 h-4 rounded-full transition-all duration-75" style="width: 0%"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit w-full text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-bold rounded-lg text-lg px-5 py-4 text-center shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-play mr-2"></i> LANCER L'IMPORTATION
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    // tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.target).classList.remove('hidden');
        });
    });

    // generic initializer for upload forms
    function initUploader(prefix) {
        const form = document.getElementById(`uploadForm-${prefix}`);
        const fileInput = form.querySelector('.dropzone-file');
        const fileNameDisplay = form.querySelector('.file-name');
        const progressWrapper = form.querySelector('.progress-wrapper');
        const progressBar = form.querySelector('.progress-bar');
        const progressPercent = form.querySelector('.progress-percent');
        const btnSubmit = form.querySelector('.btn-submit');

        // choose colors
        const primary = prefix === 'factures' ? 'blue' : 'green';
        const successColor = prefix === 'factures' ? 'bg-blue-500' : 'bg-green-500';
        const uploadColor = prefix === 'factures' ? 'bg-blue-600' : 'bg-green-600';

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = "Fichier prêt : " + this.files[0].name;
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (fileInput.files.length === 0) {
                showAlert('error', 'Veuillez sélectionner un fichier d\'abord.');
                return;
            }

            const formData = new FormData(form);
            progressWrapper.classList.remove('hidden');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Envoi en cours...';
            hideAlert();

            axios.post(form.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: function(progressEvent) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        progressBar.style.width = percentCompleted + '%';
                        progressPercent.textContent = percentCompleted + '%';
                    }
                })
                .then(response => {
                    progressBar.classList.remove(uploadColor, 'bg-red-600');
                    progressBar.classList.add(successColor);
                    showAlert('success', response.data.message);
                    btnSubmit.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Terminé !';
                    setTimeout(() => location.reload(), 2000);
                })
                .catch(error => {
                    progressBar.classList.remove(uploadColor, successColor);
                    progressBar.classList.add('bg-red-600');
                    let msg = "Une erreur est survenue.";
                    if (error.response && error.response.data.message) {
                        msg = error.response.data.message;
                    }
                    showAlert('error', msg);
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-play mr-2"></i> Réessayer';
                });
        });
    }

    // initialize both uploaders
    initUploader('factures');
    initUploader('paiements');

    const alertBox = document.getElementById('alert-box');
    function showAlert(type, message) {
        alertBox.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
        if (type === 'error') {
            alertBox.classList.add('bg-red-100', 'text-red-700', 'border', 'border-red-400');
            alertBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-2"></i> ${message}`;
        } else {
            alertBox.classList.add('bg-green-100', 'text-green-700', 'border', 'border-green-400');
            alertBox.innerHTML = `<i class="fa-solid fa-circle-check mr-2"></i> ${message}`;
        }
    }

    function hideAlert() {
        alertBox.classList.add('hidden');
    }
</script>
@endsection