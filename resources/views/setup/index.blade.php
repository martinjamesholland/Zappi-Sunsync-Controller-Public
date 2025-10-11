@extends('layouts.setup')

@section('title', 'Setup Wizard - Solar Battery EV Charger')

@section('content')
<div class="setup-header">
    <h1 class="h2 mb-2">
        <i class="bi bi-gear-fill"></i> Welcome to Setup
    </h1>
    <p class="mb-0">Let's configure your Solar Battery EV Charger system</p>
</div>

<div class="setup-body">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step {{ $setupStatus['app_key'] ? 'completed' : '' }}">
            <div class="step-circle">
                @if($setupStatus['app_key'])
                    <i class="bi bi-check"></i>
                @else
                    1
                @endif
            </div>
            <span class="step-label">APP KEY</span>
        </div>
        <div class="step {{ $setupStatus['database'] ? 'completed' : '' }}">
            <div class="step-circle">
                @if($setupStatus['database'])
                    <i class="bi bi-check"></i>
                @else
                    2
                @endif
            </div>
            <span class="step-label">Database</span>
        </div>
        <div class="step {{ $setupStatus['zappi'] ? 'completed' : '' }}">
            <div class="step-circle">
                @if($setupStatus['zappi'])
                    <i class="bi bi-check"></i>
                @else
                    3
                @endif
            </div>
            <span class="step-label">Zappi</span>
        </div>
        <div class="step {{ $setupStatus['sunsync'] ? 'completed' : '' }}">
            <div class="step-circle">
                @if($setupStatus['sunsync'])
                    <i class="bi bi-check"></i>
                @else
                    4
                @endif
            </div>
            <span class="step-label">SunSync</span>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="text-center mb-4">
        <i class="bi bi-sun-fill text-warning" style="font-size: 4rem;"></i>
        <h2 class="h4 mt-3 mb-3">Solar Battery & EV Charger Integration</h2>
        <p class="text-muted">
            This wizard will help you set up your system to monitor and control your solar panels, 
            battery storage, and EV charger. We'll configure each component step by step.
        </p>
    </div>

    <!-- Setup Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-key-fill"></i> Application Key
                    </h5>
                    <p class="card-text text-muted small">
                        Secure encryption key for your application
                    </p>
                    @if($setupStatus['app_key'])
                        <span class="status-badge configured">
                            <i class="bi bi-check-circle-fill"></i> Configured
                        </span>
                    @else
                        <span class="status-badge not-configured">
                            <i class="bi bi-exclamation-circle-fill"></i> Not Configured
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-database-fill"></i> Database
                    </h5>
                    <p class="card-text text-muted small">
                        Storage for your energy data and settings
                    </p>
                    @if($setupStatus['database'])
                        <span class="status-badge configured">
                            <i class="bi bi-check-circle-fill"></i> Configured
                        </span>
                    @else
                        <span class="status-badge not-configured">
                            <i class="bi bi-exclamation-circle-fill"></i> Not Configured
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-ev-station-fill"></i> Zappi Charger
                    </h5>
                    <p class="card-text text-muted small">
                        MyEnergi Zappi EV charger integration
                    </p>
                    @if($setupStatus['zappi'])
                        <span class="status-badge configured">
                            <i class="bi bi-check-circle-fill"></i> Configured
                        </span>
                    @else
                        <span class="status-badge not-configured">
                            <i class="bi bi-exclamation-circle-fill"></i> Not Configured
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-sun-fill"></i> SunSync Inverter
                    </h5>
                    <p class="card-text text-muted small">
                        SunSync solar inverter integration
                    </p>
                    @if($setupStatus['sunsync'])
                        <span class="status-badge configured">
                            <i class="bi bi-check-circle-fill"></i> Configured
                        </span>
                    @else
                        <span class="status-badge not-configured">
                            <i class="bi bi-exclamation-circle-fill"></i> Not Configured
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-grid gap-2">
        @if(!$setupStatus['app_key'])
            <a href="{{ route('setup.app-key') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Start Setup
            </a>
        @elseif(!$setupStatus['database'])
            <a href="{{ route('setup.database') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Continue Setup - Configure Database
            </a>
        @elseif(!$setupStatus['zappi'])
            <a href="{{ route('setup.zappi') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Continue Setup - Configure Zappi
            </a>
        @elseif(!$setupStatus['sunsync'])
            <a href="{{ route('setup.sunsync') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Continue Setup - Configure SunSync
            </a>
        @else
            <a href="{{ route('setup.complete') }}" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> View Setup Summary
            </a>
        @endif
        
        @if($setupStatus['app_key'] || $setupStatus['database'] || $setupStatus['zappi'] || $setupStatus['sunsync'])
            <a href="{{ route('setup.app-key') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i> Reconfigure from Beginning
            </a>
        @endif
    </div>

    @if($setupStatus['app_key'] && $setupStatus['database'] && $setupStatus['zappi'] && $setupStatus['sunsync'])
        <div class="alert alert-success mt-4 mb-0">
            <i class="bi bi-check-circle-fill"></i>
            <strong>Setup Complete!</strong> All components are configured. You can start using the application.
        </div>
    @endif
</div>
@endsection

