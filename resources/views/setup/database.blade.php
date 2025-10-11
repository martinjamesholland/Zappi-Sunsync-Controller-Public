@extends('layouts.setup')

@section('title', 'Database Setup - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-database-fill"></i> Step 2: Database Configuration
    </h1>
    <p class="mb-0">Configure your database connection and run migrations</p>
</div>

<div class="setup-body">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">APP KEY</span>
        </div>
        <div class="step active">
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
        <strong>Database Configuration</strong>
        <p class="mb-0 mt-2">
            Choose your database type and provide connection details. 
            We'll test the connection and automatically run migrations to create the necessary tables.
        </p>
    </div>

    <!-- Database Configuration Form -->
    <form id="databaseForm">
        @csrf
        
        <!-- Database Type Selection -->
        <div class="mb-4">
            <label for="db_connection" class="form-label">
                <i class="bi bi-server"></i> Database Type
            </label>
            <select class="form-select form-select-lg" id="db_connection" name="db_connection" required>
                <option value="sqlite" {{ $currentConnection === 'sqlite' ? 'selected' : '' }}>SQLite (Recommended for simple setups)</option>
                <option value="mysql" {{ $currentConnection === 'mysql' ? 'selected' : '' }}>MySQL</option>
                <option value="mariadb" {{ $currentConnection === 'mariadb' ? 'selected' : '' }}>MariaDB</option>
                <option value="pgsql" {{ $currentConnection === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
            </select>
            <div class="form-text">SQLite is the simplest option and doesn't require a separate database server.</div>
        </div>

        <!-- SQLite Configuration -->
        <div id="sqliteConfig" class="db-config">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">SQLite Configuration</h5>
                    <p class="text-muted small">SQLite stores data in a single file. Perfect for small to medium deployments.</p>
                    
                    <div class="mb-3">
                        <label for="sqlite_database" class="form-label">Database File Path</label>
                        <input type="text" class="form-control" id="sqlite_database" name="db_database" 
                               value="{{ $currentConnection === 'sqlite' ? ($currentConfig['database'] ?? database_path('database.sqlite')) : database_path('database.sqlite') }}"
                               placeholder="{{ database_path('database.sqlite') }}">
                        <div class="form-text">The file will be created automatically if it doesn't exist.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MySQL/MariaDB/PostgreSQL Configuration -->
        <div id="serverConfig" class="db-config" style="display: none;">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Server Database Configuration</h5>
                    <p class="text-muted small">Configure your database server connection details.</p>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="db_host" class="form-label">Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="{{ $currentConfig['host'] ?? '127.0.0.1' }}" placeholder="127.0.0.1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="db_port" class="form-label">Port</label>
                            <input type="number" class="form-control" id="db_port" name="db_port" 
                                   value="{{ $currentConfig['port'] ?? '3306' }}" placeholder="3306">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="db_database_server" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_database_server" name="db_database" 
                               value="{{ $currentConfig['database'] ?? 'laravel' }}" placeholder="laravel">
                    </div>

                    <div class="mb-3">
                        <label for="db_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="db_username" name="db_username" 
                               value="{{ $currentConfig['username'] ?? 'root' }}" placeholder="root">
                    </div>

                    <div class="mb-3">
                        <label for="db_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="db_password" name="db_password" 
                               placeholder="Enter database password">
                        <div class="form-text">Leave blank if no password is required.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-grid gap-2">
            <button type="submit" id="testAndSaveBtn" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle"></i> Test Connection & Save
            </button>
            <a href="{{ route('setup.app-key') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to APP KEY
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
// Database type switching
const dbConnectionSelect = document.getElementById('db_connection');
const sqliteConfig = document.getElementById('sqliteConfig');
const serverConfig = document.getElementById('serverConfig');
const dbPortInput = document.getElementById('db_port');

function updateDatabaseConfig() {
    const dbType = dbConnectionSelect.value;
    
    if (dbType === 'sqlite') {
        sqliteConfig.style.display = 'block';
        serverConfig.style.display = 'none';
        // Remove required attribute from server inputs
        document.getElementById('db_host').removeAttribute('required');
        document.getElementById('db_database_server').removeAttribute('required');
        document.getElementById('db_username').removeAttribute('required');
    } else {
        sqliteConfig.style.display = 'none';
        serverConfig.style.display = 'block';
        // Add required attribute to server inputs
        document.getElementById('db_host').setAttribute('required', 'required');
        document.getElementById('db_database_server').setAttribute('required', 'required');
        document.getElementById('db_username').setAttribute('required', 'required');
        
        // Update default port based on database type
        if (dbType === 'mysql' || dbType === 'mariadb') {
            dbPortInput.value = '3306';
        } else if (dbType === 'pgsql') {
            dbPortInput.value = '5432';
        }
    }
}

// Initialize on page load
updateDatabaseConfig();

// Update when selection changes
dbConnectionSelect.addEventListener('change', updateDatabaseConfig);

// Form submission
document.getElementById('databaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('testAndSaveBtn');
    const originalText = btn.innerHTML;
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing connection...';
    
    // Clear previous alerts
    document.getElementById('alertContainer').innerHTML = '';
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    
    fetch('{{ route('setup.database.save') }}', {
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
            // Show success message
            document.getElementById('alertContainer').innerHTML = `
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>Success!</strong> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Redirect to next step after 1.5 seconds
            setTimeout(() => {
                window.location.href = '{{ route('setup.zappi') }}';
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

