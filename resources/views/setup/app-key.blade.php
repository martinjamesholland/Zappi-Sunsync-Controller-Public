<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Key Setup</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            max-width: 600px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .setup-icon {
            font-size: 4rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .setup-title {
            color: #0d6efd;
            margin-bottom: 1.5rem;
        }
        .key-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            word-break: break-all;
            font-family: monospace;
        }
        .loading {
            display: none;
        }
        .loading.active {
            display: inline-block;
        }
        .file-info {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="text-center">
            <div class="setup-icon">🔑</div>
            <h1 class="setup-title">Application Key Setup</h1>
            <p class="lead">Generate and update your application encryption key</p>
        </div>

        @if(!$envExists)
        <div class="alert alert-warning">
            <h5>Warning: .env file not found</h5>
            <p class="mb-0">The system will attempt to create a new .env file at: <code>{{ $envPath }}</code></p>
        </div>
        @endif

        <div class="alert alert-info">
            <p class="mb-0">This tool will help you generate and update your application key in the .env file.</p>
        </div>

        <div class="d-grid gap-2">
            <button id="generateKey" class="btn btn-primary btn-lg">
                Generate New Key
                <span class="spinner-border spinner-border-sm loading" role="status" aria-hidden="true"></span>
            </button>
        </div>

        <div id="result" class="mt-4" style="display: none;">
            <div class="alert alert-success">
                <h5>Success!</h5>
                <p class="mb-2">Your application key has been generated and updated.</p>
                <div class="key-display" id="generatedKey"></div>
                <p class="mt-2 mb-0">The application will now work correctly. You can refresh the page to continue.</p>
            </div>
        </div>

        <div id="error" class="mt-4" style="display: none;">
            <div class="alert alert-danger">
                <h5>Error</h5>
                <p id="errorMessage" class="mb-0"></p>
            </div>
        </div>

        <div class="mt-4">
            <h5>System Information:</h5>
            <div class="file-info">
                <p class="mb-1"><strong>Environment File:</strong> {{ $envPath }}</p>
                <p class="mb-1"><strong>Status:</strong> {{ $envExists ? 'Found' : 'Not Found' }}</p>
                <p class="mb-0"><strong>Writable:</strong> {{ is_writable($envPath) ? 'Yes' : 'No' }}</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('generateKey').addEventListener('click', async function() {
            const button = this;
            const spinner = button.querySelector('.spinner-border');
            const result = document.getElementById('result');
            const error = document.getElementById('error');
            
            // Reset UI
            result.style.display = 'none';
            error.style.display = 'none';
            spinner.classList.add('active');
            button.disabled = true;

            try {
                const response = await fetch('{{ $baseUrl }}/setup/app-key/generate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('generatedKey').textContent = data.key;
                    result.style.display = 'block';
                } else {
                    document.getElementById('errorMessage').textContent = data.message;
                    error.style.display = 'block';
                }
            } catch (e) {
                document.getElementById('errorMessage').textContent = 'An error occurred while generating the key.';
                error.style.display = 'block';
            } finally {
                spinner.classList.remove('active');
                button.disabled = false;
            }
        });
    </script>
</body>
</html> 