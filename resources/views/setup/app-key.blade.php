@extends('layouts.setup')

@section('title', 'APP KEY Setup - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-key-fill"></i> Step 1: Application Key
    </h1>
    <p class="mb-0">Generate a secure encryption key for your application</p>
</div>

<div class="setup-body">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active">
            <div class="step-circle">1</div>
            <span class="step-label">APP KEY</span>
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
        <strong>What is an Application Key?</strong>
        <p class="mb-0 mt-2">
            The application key is used to encrypt session data and other sensitive information. 
            It's essential for the security of your application. This key will be stored in your .env file.
        </p>
    </div>

    @if($hasAppKey)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <strong>Application Key Already Set</strong>
            <p class="mb-2 mt-2">Your application already has an encryption key configured.</p>
            <div class="font-monospace small text-break bg-light p-2 rounded">
                {{ $currentKey }}
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>No Application Key Found</strong>
            <p class="mb-0 mt-2">Click the button below to generate a new application key.</p>
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
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-grid gap-2">
        <button type="button" id="generateKeyBtn" class="btn btn-primary btn-lg">
            <i class="bi bi-key"></i> 
            {{ $hasAppKey ? 'Regenerate Application Key' : 'Generate Application Key' }}
        </button>
        
        @if($hasAppKey)
            <a href="{{ route('setup.database') }}" class="btn btn-success btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Continue to Database Setup
            </a>
        @endif
        
        <a href="{{ route('setup.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>

    @if($hasAppKey)
        <div class="alert alert-warning mt-4 mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Warning:</strong> Regenerating the application key will invalidate all existing sessions and encrypted data.
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
