@extends('layouts.setup')

@section('title', 'SunSync Setup - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-sun-fill"></i> Step 4: SunSync Configuration
    </h1>
    <p class="mb-0">Connect your SunSync solar inverter</p>
</div>

<div class="setup-body">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">APP KEY</span>
        </div>
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">Database</span>
        </div>
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">Zappi</span>
        </div>
        <div class="step active">
            <div class="step-circle">4</div>
            <span class="step-label">SunSync</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <!-- Information -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill"></i>
        <strong>SunSync API Credentials</strong>
        <p class="mb-0 mt-2">
            Enter your SunSync account email and password. These are the same credentials you use 
            to log into the SunSync mobile app or web portal. We'll verify them by connecting to the SunSync API.
        </p>
    </div>

    @if($hasCredentials)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <strong>SunSync Already Configured</strong>
            <p class="mb-0 mt-2">
                Your SunSync credentials are already set. Username: <strong>{{ $currentUsername }}</strong>
            </p>
        </div>
    @endif

    <!-- SunSync Configuration Form -->
    <form id="sunsyncForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">SunSync API Credentials</h5>
                
                <div class="mb-3">
                    <label for="sunsync_username" class="form-label">
                        <i class="bi bi-envelope-fill"></i> Email Address
                    </label>
                    <input type="email" class="form-control form-control-lg" id="sunsync_username" 
                           name="sunsync_username" value="{{ $currentUsername ?? '' }}" 
                           placeholder="Enter your SunSync email" required>
                    <div class="form-text">
                        The email address you use to log into SunSync.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sunsync_password" class="form-label">
                        <i class="bi bi-lock-fill"></i> Password
                    </label>
                    <input type="password" class="form-control form-control-lg" id="sunsync_password" 
                           name="sunsync_password" placeholder="Enter your SunSync password" required>
                    <div class="form-text">
                        Your SunSync account password.
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="card mb-4 border-warning">
            <div class="card-body">
                <h6 class="card-title text-warning">
                    <i class="bi bi-question-circle-fill"></i> Where to Find Your Credentials
                </h6>
                <ul class="mb-0 small">
                    <li>These are the same credentials you use for the <strong>SunSync</strong> mobile app</li>
                    <li>Or the credentials for <strong>https://api.sunsynk.net</strong></li>
                    <li>If you don't have an account, create one in the SunSync app first</li>
                    <li>Make sure your inverter is added to your SunSync account</li>
                </ul>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-grid gap-2">
            <button type="submit" id="testAndSaveBtn" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Test & Save SunSync Credentials
            </button>
            
            @if($hasCredentials)
                <a href="{{ route('setup.complete') }}" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Skip to Completion
                </a>
            @endif
            
            <a href="{{ route('setup.zappi') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Zappi Setup
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('sunsyncForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('testAndSaveBtn');
    const originalText = btn.innerHTML;
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing credentials...';
    
    // Clear previous alerts
    document.getElementById('alertContainer').innerHTML = '';
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    
    fetch('{{ route('setup.sunsync.save') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message with SunSync info if available
            let successMessage = `<div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i>
                <strong>Success!</strong> ${data.message}`;
            
            if (data.data) {
                successMessage += `
                    <div class="mt-2 pt-2 border-top">
                        <strong>Connection verified!</strong><br>
                        <small>Successfully authenticated with SunSync API.</small>
                    </div>
                `;
            }
            
            successMessage += `<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            document.getElementById('alertContainer').innerHTML = successMessage;
            
            // Redirect to completion page after 2 seconds
            setTimeout(() => {
                window.location.href = '{{ route('setup.complete') }}';
            }, 2000);
        } else {
            // Show error message
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <strong>Error!</strong> ${data.message}
                    <div class="mt-2 small">
                        Please verify your email and password are correct. Make sure you can log into the SunSync app with these credentials.
                    </div>
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

