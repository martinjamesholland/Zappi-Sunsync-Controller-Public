@extends('layouts.setup')

@section('title', 'Setup Complete - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-check-circle-fill"></i> Setup Complete!
    </h1>
    <p class="mb-0">Your system is configured and ready to use</p>
</div>

<div class="setup-body">
    <!-- Step Indicator - All Complete -->
    <div class="step-indicator">
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">Security Keys</span>
        </div>
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">Database</span>
        </div>
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">Zappi</span>
        </div>
        <div class="step completed">
            <div class="step-circle"><i class="bi bi-check"></i></div>
            <span class="step-label">SunSync</span>
        </div>
    </div>

    <!-- Success Message -->
    <div class="text-center mb-4">
        <div class="display-1 text-success mb-3">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h2 class="h3 mb-3">Congratulations!</h2>
        <p class="text-muted">
            Your Solar Battery EV Charger system is now fully configured and ready to use.
        </p>
    </div>

    <!-- Configuration Summary -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-list-check"></i> Configuration Summary
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Security Keys Status -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-key-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Security Keys</h6>
                            <small class="text-muted">
                                @if($setupStatus['app_key'])
                                    <i class="bi bi-check-circle-fill text-success"></i> Configured
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i> Not Configured
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Database Status -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-database-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Database</h6>
                            <small class="text-muted">
                                @if($setupStatus['database'])
                                    <i class="bi bi-check-circle-fill text-success"></i> Connected & Migrated
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i> Not Configured
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Zappi Status -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-ev-station-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Zappi Charger</h6>
                            <small class="text-muted">
                                @if($setupStatus['zappi'])
                                    <i class="bi bi-check-circle-fill text-success"></i> Connected
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i> Not Configured
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <!-- SunSync Status -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <i class="bi bi-sun-fill"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">SunSync Inverter</h6>
                            <small class="text-muted">
                                @if($setupStatus['sunsync'])
                                    <i class="bi bi-check-circle-fill text-success"></i> Connected
                                @else
                                    <i class="bi bi-x-circle-fill text-danger"></i> Not Configured
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-lightbulb-fill"></i> What's Next?
            </h5>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <div class="list-group-item px-0">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">1</span>
                        <div>
                            <h6 class="mb-1">Visit the Home Dashboard</h6>
                            <p class="mb-0 text-muted small">View your combined energy flow and system status at a glance.</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item px-0">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">2</span>
                        <div>
                            <h6 class="mb-1">Monitor Your Zappi Status</h6>
                            <p class="mb-0 text-muted small">Check your EV charging status and configure charging modes.</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item px-0">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">3</span>
                        <div>
                            <h6 class="mb-1">View SunSync Dashboard</h6>
                            <p class="mb-0 text-muted small">Monitor your solar inverter performance and battery status.</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item px-0">
                    <div class="d-flex align-items-start">
                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">4</span>
                        <div>
                            <h6 class="mb-1">Configure EV Charging Settings</h6>
                            <p class="mb-0 text-muted small">Set up intelligent charging based on your battery and solar status.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$setupStatus['app_key'] || !$setupStatus['database'] || !$setupStatus['zappi'] || !$setupStatus['sunsync'])
        <div class="alert alert-warning mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Incomplete Setup Detected</strong>
            <p class="mb-2 mt-2">Some components are not fully configured:</p>
            <ul class="mb-0">
                @if(!$setupStatus['app_key'])
                    <li>Security Keys (APP_KEY and API_KEY) are missing</li>
                @endif
                @if(!$setupStatus['database'])
                    <li>Database is not configured</li>
                @endif
                @if(!$setupStatus['zappi'])
                    <li>Zappi credentials are missing</li>
                @endif
                @if(!$setupStatus['sunsync'])
                    <li>SunSync credentials are missing</li>
                @endif
            </ul>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="d-grid gap-2">
        <a href="{{ url('/') }}" class="btn btn-success btn-lg">
            <i class="bi bi-house-door-fill"></i> Go to Home Dashboard
        </a>
        
        <a href="{{ route('setup.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Review Setup Configuration
        </a>
    </div>
</div>
@endsection

