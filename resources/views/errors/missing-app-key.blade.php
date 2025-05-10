<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Key Missing</title>
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
        .error-container {
            max-width: 600px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .error-title {
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .error-steps {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin-top: 1.5rem;
        }
        .error-steps ol {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        .error-steps li {
            margin-bottom: 0.5rem;
        }
        .error-steps li:last-child {
            margin-bottom: 0;
        }
        code {
            background: #e9ecef;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .setup-button {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="text-center">
            <div class="error-icon">🔑</div>
            <h1 class="error-title">Application Key Missing</h1>
            <p class="lead">The application encryption key has not been set. This is required for secure operation of the application.</p>
        </div>

        <div class="error-steps">
            <h5>To resolve this issue, you can:</h5>
            <ol>
                <li>Click the button below to automatically generate and set up your application key</li>
                <li>Or follow these manual steps:
                    <ul>
                        <li>Open your terminal and navigate to your project directory</li>
                        <li>Run the following command to generate a new application key:
                            <br><code>php artisan key:generate</code>
                        </li>
                        <li>If you're using version control, make sure your <code>.env</code> file is properly configured and not ignored</li>
                        <li>Verify that your <code>.env</code> file contains the <code>APP_KEY</code> value</li>
                        <li>Restart your application server</li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="text-center setup-button">
            <a href="{{ url('/setup/app-key') }}" class="btn btn-primary btn-lg">
                Generate Application Key
            </a>
        </div>

        <div class="mt-4 text-center">
            <p class="text-muted">If you continue to experience issues, please contact your system administrator.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 