@extends('admins.layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-blue-900 to-blue-700 p-8 text-white">
            <h2 class="text-3xl font-bold flex items-center">
                <i class="fa-solid fa-database mr-4"></i> Importation Massive
            </h2>
            <p class="mt-2 text-blue-100 opacity-90">Module optimisé pour les fichiers > 20 000 lignes.</p>
        </div>

        <div class="p-8">
            <div id="alert-box" class="hidden p-4 mb-6 rounded-lg text-sm font-medium"></div>

            <form id="uploadForm" action="{{ route('admin.imports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="flex items-center justify-center w-full mb-6">
                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-blue-300 border-dashed rounded-lg cursor-pointer bg-blue-50 hover:bg-blue-100 transition">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fa-solid fa-cloud-arrow-up text-5xl text-blue-500 mb-4"></i>
                            <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Cliquez pour upload</span> ou glissez le fichier</p>
                            <p class="text-xs text-gray-400">XLSX, CSV (Max 50Mo)</p>
                            <p id="file-name" class="mt-4 text-sm font-bold text-blue-700"></p>
                        </div>
                        <input id="dropzone-file" type="file" name="file" class="hidden" accept=".xlsx,.csv" />
                    </label>
                </div>

                <div id="progress-wrapper" class="hidden mb-6">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-blue-700">Envoi au serveur...</span>
                        <span class="text-sm font-medium text-blue-700" id="progress-percent">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div id="progress-bar" class="bg-blue-600 h-4 rounded-full transition-all duration-75" style="width: 0%"></div>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-lg px-5 py-4 text-center shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-play mr-2"></i> LANCER L'IMPORTATION
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    const fileInput = document.getElementById('dropzone-file');
    const fileNameDisplay = document.getElementById('file-name');
    const form = document.getElementById('uploadForm');
    const progressWrapper = document.getElementById('progress-wrapper');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');
    const alertBox = document.getElementById('alert-box');
    const btnSubmit = document.getElementById('btn-submit');

    // Afficher le nom du fichier sélectionné
    fileInput.addEventListener('change', function() {
        if(this.files && this.files[0]) {
            fileNameDisplay.textContent = "Fichier prêt : " + this.files[0].name;
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Vérification fichier
        if(fileInput.files.length === 0) {
            showAlert('error', 'Veuillez sélectionner un fichier d\'abord.');
            return;
        }

        // Préparation UI
        const formData = new FormData(form);
        progressWrapper.classList.remove('hidden');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Envoi en cours...';
        hideAlert();

        // Envoi Axios
        axios.post(form.action, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: function(progressEvent) {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                progressBar.style.width = percentCompleted + '%';
                progressPercent.textContent = percentCompleted + '%';
            }
        })
        .then(response => {
            // SUCCÈS
            progressBar.classList.remove('bg-blue-600');
            progressBar.classList.add('bg-green-500');
            showAlert('success', response.data.message);
            btnSubmit.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Terminé !';
            setTimeout(() => { location.reload(); }, 2000); // Recharger la page après 2s
        })
        .catch(error => {
            // ERREUR
            progressBar.classList.remove('bg-blue-600');
            progressBar.classList.add('bg-red-600');
            let msg = "Une erreur est survenue.";
            if(error.response && error.response.data.message) {
                msg = error.response.data.message;
            }
            showAlert('error', msg);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fa-solid fa-play mr-2"></i> Réessayer';
        });
    });

    function showAlert(type, message) {
        alertBox.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
        if(type === 'error') {
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