@extends('layouts.setup')

@section('title', 'Security Keys Setup - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-key-fill"></i> Step 1: Security Keys
    </h1>
    <p class="mb-0">Generate secure encryption and API keys for your application</p>
</div>

<div class="setup-body">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active">
            <div class="step-circle">1</div>
            <span class="step-label">Security Keys</span>
        </div>
        <div class="step">
            <div class="step-circle">2</div>
            <span class="step-label">Database</span>
        </div>
        <div class="step">
            <div class="step-circle">3</div>
            <span class="step-label">Zappi</span>
        </div>
        <div class="step">
            <div class="step-circle">4</div>
            <span class="step-label">SunSync</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <!-- Information -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i>
        <strong>What are these keys?</strong>
        <div class="mt-2">
            <p class="mb-2">
                <strong>APP_KEY:</strong> Used to encrypt session data and other sensitive information. 
                Essential for the security of your application.
            </p>
            <p class="mb-0">
                <strong>API_KEY:</strong> Used to authenticate API endpoint calls for external integrations 
                (e.g., cron jobs, webhooks). This ensures only authorized requests can trigger data updates.
            </p>
        </div>
    </div>

    @if($hasAppKey && $hasApiKey)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <strong>Keys Already Configured</strong>
            <p class="mb-2 mt-2">Your application already has both encryption keys configured.</p>
            <div class="mb-2">
                <strong>APP_KEY:</strong>
                <div class="font-monospace small text-break bg-light p-2 rounded">
                    {{ $currentKey }}
                </div>
            </div>
            <div>
                <strong>API_KEY:</strong>
                <div class="font-monospace small text-break bg-light p-2 rounded">
                    {{ $currentApiKey }}
                </div>
            </div>
        </div>
    @elseif($hasAppKey && !$hasApiKey)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>API Key Missing</strong>
            <p class="mb-0 mt-2">APP_KEY is configured, but API_KEY is missing. Click the button below to generate it.</p>
        </div>
    @elseif(!$hasAppKey && $hasApiKey)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Application Key Missing</strong>
            <p class="mb-0 mt-2">API_KEY is configured, but APP_KEY is missing. Click the button below to generate it.</p>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>No Keys Found</strong>
            <p class="mb-0 mt-2">Click the button below to generate both application keys.</p>
        </div>
    @endif

    <!-- Current Status -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Current Status</h5>
            <table class="table table-sm mb-0">
                <tbody>
                    <tr>
                        <td><strong>.env File:</strong></td>
                        <td>
                            @if(file_exists(base_path('.env')))
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Exists
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="bi bi-exclamation-circle"></i> Will be created
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>APP_KEY:</strong></td>
                        <td>
                            @if($hasAppKey)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Configured
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Not Set
                                </span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>API_KEY:</strong></td>
                        <td>
                            @if($hasApiKey)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Configured
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Not Set
                                </span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-grid gap-2">
        <button type="button" id="generateKeyBtn" class="btn btn-primary btn-lg">
            <i class="bi bi-key"></i> 
            {{ ($hasAppKey && $hasApiKey) ? 'Regenerate Application Keys' : 'Generate Application Keys' }}
        </button>
        
        @if($hasAppKey && $hasApiKey)
            <a href="{{ route('setup.database') }}" class="btn btn-success btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Continue to Database Setup
            </a>
        @endif
        
        <a href="{{ route('setup.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>

    @if($hasAppKey || $hasApiKey)
        <div class="alert alert-warning mt-4 mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Warning:</strong> Regenerating the keys will invalidate all existing sessions, encrypted data, and API authentication tokens.
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.getElementById('generateKeyBtn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    // Clear previous alerts
    document.getElementById('alertContainer').innerHTML = '';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    
    fetch('{{ route('setup.app-key.generate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>Success!</strong> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Reload page after 1.5 seconds to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Show error message
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <strong>Error!</strong> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        // Show error message
        document.getElementById('alertContainer').innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle-fill"></i>
                <strong>Error!</strong> An unexpected error occurred. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});
</script>
@endsection
