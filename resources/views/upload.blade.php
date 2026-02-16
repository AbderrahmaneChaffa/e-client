<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel 12 File Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Laravel 12 File Upload</h2>
        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form id="uploadForm" action="{{ route('file.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Choose File (Single)</label>
                <input type="file" name="file" class="form-control">
            </div>
            <div class="mb-3">
                <label for="files" class="form-label">Choose Files (Multiple)</label>
                <input type="file" name="files[]" class="form-control" multiple>
            </div>
            <div class="progress mb-3" style="display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const progressBar = document.querySelector('.progress');
            const progressBarInner = document.querySelector('.progress-bar');

            progressBar.style.display = 'block';
            progressBarInner.style.width = '0%';
            progressBarInner.textContent = '0%';

            const formData = new FormData(form);

            axios.post(form.action, formData, {
                    onUploadProgress: function(progressEvent) {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        progressBarInner.style.width = percentCompleted + '%';
                        progressBarInner.textContent = percentCompleted + '%';
                    }
                })
                .then(response => {
                    progressBar.style.display = 'none';
                    window.location.reload(); // Refresh to show success message
                })
                .catch(error => {
                    progressBar.style.display = 'none';
                    alert('Error uploading files!');
                });
        });
    </script>
</body>

</html>