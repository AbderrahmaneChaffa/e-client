@extends('admins.layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto" id="import-container">
    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Module d'Importation Intelligent</h2>

        <div id="drop-zone" class="border-2 border-dashed border-blue-300 rounded-xl p-10 text-center hover:bg-blue-50 transition-all cursor-pointer relative">
            <input type="file" id="fileInput" class="hidden" accept=".xlsx,.csv">
            <div id="upload-prompt">
                <i class="fa-solid fa-cloud-arrow-up text-5xl text-blue-500 mb-4"></i>
                <p class="text-gray-600 font-medium">Glissez le fichier ici ou <span class="text-blue-600 underline">parcourez</span></p>
            </div>

            <div id="progress-container" class="hidden mt-4">
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div id="progress-bar" class="bg-blue-600 h-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="progress-text" class="text-sm text-blue-600 mt-2 font-bold italic text-center">Chargement du fichier : 0%</p>
            </div>
        </div>

        <div id="analysis-zone" class="hidden mt-8 border-t pt-6">
            <div id="analysis-results" class="mb-6 p-4 rounded-lg">
            </div>

            <form id="final-import-form" action="{{ route('admin.imports.store') }}" method="POST">
                @csrf
                <input type="hidden" name="file_path" id="stored_file_path">
                <button type="submit" id="btn-confirm" class="w-full bg-green-600 text-white py-4 rounded-xl font-black text-lg shadow-xl hover:bg-green-700 hidden">
                    CONFIRMER ET LANCER L'IMPORTATION
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('drop-zone');

    // 1. Déclenchement de l'upload dès la sélection
    fileInput.addEventListener('change', e => uploadFile(e.target.files[0]));
    dropZone.addEventListener('click', () => fileInput.click());

    function uploadFile(file) {
        if (!file) return;

        let formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');

        // Afficher la progression
        document.getElementById('upload-prompt').classList.add('hidden');
        document.getElementById('progress-container').classList.remove('hidden');

        let xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("admin.imports.upload-temp") }}', true);

        xhr.upload.onprogress = (e) => {
            let percent = Math.round((e.loaded / e.total) * 100);
            document.getElementById('progress-bar').style.width = percent + '%';
            document.getElementById('progress-text').innerText = `Upload vers le serveur : ${percent}%`;
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                let res = JSON.parse(xhr.responseText);
                analyzeFile(res.path); // Lancer l'analyse des doublons
            }
        };
        xhr.send(formData);
    }

    // 2. Analyse du fichier pour détecter les doublons
    function analyzeFile(path) {
        document.getElementById('progress-text').innerText = "Analyse du contenu et scan des doublons...";
        document.getElementById('progress-bar').classList.replace('bg-blue-600', 'bg-orange-500');

        fetch('{{ route("admin.imports.analyze") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    path: path
                })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('progress-container').classList.add('hidden');
                document.getElementById('analysis-zone').classList.remove('hidden');
                document.getElementById('stored_file_path').value = path;

                let resDiv = document.getElementById('analysis-results');
                if (data.duplicates > 0) {
                    resDiv.className = "p-4 bg-red-50 border-l-4 border-red-500 text-red-700";
                    resDiv.innerHTML = `<h3 class="font-bold text-lg"><i class="fa-solid fa-triangle-exclamation"></i> ATTENTION !</h3>
                                <p>Ce fichier contient <b>${data.duplicates}</b> factures qui existent déjà en base de données.</p>
                                <p class="text-sm mt-2 font-bold italic">Si vous confirmez, ces factures seront mises à jour avec les nouvelles données de l'Excel.</p>`;
                } else {
                    resDiv.className = "p-4 bg-green-50 border-l-4 border-green-500 text-green-700";
                    resDiv.innerHTML = `<h3 class="font-bold text-lg"><i class="fa-solid fa-check-circle"></i> FICHIER SAIN</h3>
                                <p>Aucun doublon détecté sur les ${data.total} lignes. Vous pouvez procéder.</p>`;
                }
                document.getElementById('btn-confirm').classList.remove('hidden');
            });
    }
</script>
@endsection