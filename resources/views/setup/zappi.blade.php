@extends('layouts.setup')

@section('title', 'Zappi Setup - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-ev-station-fill"></i> Step 3: Zappi Configuration
    </h1>
    <p class="mb-0">Connect your MyEnergi Zappi EV charger</p>
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
        <div class="step active">
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
        <strong>MyEnergi Zappi Credentials</strong>
        <p class="mb-0 mt-2">
            Enter your Zappi serial number and password. These can be found in your MyEnergi app 
            or on your Zappi device. We'll verify the credentials by connecting to the MyEnergi API.
        </p>
    </div>

    @if($hasCredentials)
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <strong>Zappi Already Configured</strong>
            <p class="mb-0 mt-2">
                Your Zappi credentials are already set. Serial: <strong>{{ $currentSerial }}</strong>
            </p>
        </div>
    @endif

    <!-- Zappi Configuration Form -->
    <form id="zappiForm">
        @csrf
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Zappi API Credentials</h5>
                
                <div class="mb-3">
                    <label for="zappi_serial" class="form-label">
                        <i class="bi bi-hash"></i> Zappi Serial Number
                    </label>
                    <input type="text" class="form-control form-control-lg" id="zappi_serial" 
                           name="zappi_serial" value="{{ $currentSerial ?? '' }}" 
                           placeholder="Enter Zappi serial number" required>
                    <div class="form-text">
                        Your Zappi serial number (e.g., 12345678). Found in the MyEnergi app under device settings.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="zappi_password" class="form-label">
                        <i class="bi bi-lock-fill"></i> Zappi API key
                    </label>
                    <input type="password" class="form-control form-control-lg" id="zappi_password" 
                           name="zappi_password" placeholder="Enter Zappi API key" required>
                    <div class="form-text">
                        Your MyEnergi API key.
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
                <h5>How to get your Zappi Serial and API Key:</h5>
                                <ol>
                                    <li>Log in to your myenergi account at <a href="https://myaccount.myenergi.com" target="_blank">myaccount.myenergi.com</a></li>
                                    <li>Go to the "API" section</li>
                                    <li>Your Zappi Serial Number can be found on your device or in the app</li>
                                    <li>Generate an API key in the API section</li>
                                </ol>
                                <p>For more detailed instructions, visit: 
                                    <a href="https://support.myenergi.com/hc/en-gb/articles/5069627351185-How-do-I-get-an-API-key" target="_blank">
                                        myenergi API Key Guide
                                    </a>
                                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-grid gap-2">
            <button type="submit" id="testAndSaveBtn" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Test & Save Zappi Credentials
            </button>
            
            @if($hasCredentials)
                <a href="{{ route('setup.sunsync') }}" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Skip to SunSync Setup
                </a>
            @endif
            
            <a href="{{ route('setup.database') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Database Setup
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('zappiForm').addEventListener('submit', function(e) {
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
    
    fetch('{{ route('setup.zappi.save') }}', {
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
            // Show success message with Zappi info if available
            let successMessage = `<div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i>
                <strong>Success!</strong> ${data.message}`;
            
            if (data.data && data.data.zappi && data.data.zappi[0]) {
                const zappi = data.data.zappi[0];
                successMessage += `
                    <div class="mt-2 pt-2 border-top">
                        <strong>Zappi Status:</strong><br>
                        <small>Serial: ${zappi.sno || 'N/A'}<br>
                        Firmware: ${zappi.fwv || 'N/A'}</small>
                    </div>
                `;
            }
            
            successMessage += `<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            document.getElementById('alertContainer').innerHTML = successMessage;
            
            // Redirect to next step after 2 seconds
            setTimeout(() => {
                window.location.href = '{{ route('setup.sunsync') }}';
            }, 2000);
        } else {
            // Show error message
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <strong>Error!</strong> ${data.message}
                    <div class="mt-2 small">
                        Please verify your serial number and password are correct.
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

