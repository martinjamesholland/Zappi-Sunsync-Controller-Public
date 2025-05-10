@extends('layouts.app')

@section('title', 'Home - Solar Battery EV Charger')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
            <div class="col-md-12">
                            <h1 class="fs-4">Welcome to Solar, Battery & EV Charger Collaboration</h1>
                        </div>
                <!-- Zappi Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h1 class="mb-0 fs-4">Zappi EV Status</h1>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://www.myenergi.com/wp-content/uploads/2022/02/zappiMultiphase_ContentSuite_v1-D-1.jpg" alt="Zappi EV Charger" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                <p class="mt-2 text-muted">
                                    <small>Zappi EV Charger Visualization</small>
                                </p>
                            </div>
                            <h2 class="fs-5 mb-3">Monitor your Zappi EV charger</h2>
                            <p>This dashboard gives you access to:</p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-lightning-charge me-3 text-primary fs-4"></i>
                                    <div>
                                        <strong>Real-time Status Monitoring</strong>
                                        <p class="mb-0 text-muted">Check current charging status, power usage, and more</p>
                                    </div>
                                </li>
                            </ul>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="{{ url('/zappi/status') }}" class="btn btn-primary">
                                    <i class="bi bi-lightning-charge-fill me-2"></i>View Zappi Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SunSync Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h2 class="mb-0 fs-4">SunSync Inverter Monitoring</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://static.wixstatic.com/media/9350f7_29095415950a470883275fba8a88fd65~mv2.png/v1/fill/w_1509,h_782,al_c/9350f7_29095415950a470883275fba8a88fd65~mv2.png" alt="SunSync Inverter" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                <p class="mt-2 text-muted">
                                    <small>SunSync Solar Inverter System</small>
                                </p>
                            </div>
                            <h2 class="fs-5 mb-3">Track & Control your SunSync Inverter</h2>
                            <p>Monitor your solar energy system with frequently updated data:</p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-sun me-3 text-success fs-4"></i>
                                    <div>
                                        <strong>Inverter Status & Power Output</strong>
                                        <p class="mb-0 text-muted">View current generation and system status</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-battery-charging me-3 text-success fs-4"></i>
                                    <div>
                                        <strong>Battery Management</strong>
                                        <p class="mb-0 text-muted">Control battery charge or discharge Based on Zappi Status</p>
                                    </div>
                                </li>
                            </ul>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="{{ url('/sunsync/dashboard') }}" class="btn btn-success">
                                    <i class="bi bi-sun-fill me-2"></i>View SunSync Dashboard
                                </a>
                           <p></p>
                                <a href="{{ url('/ev-charging/status') }}" class="btn btn-success">
                                <i class="bi bi-ev-station me-2"></i>Control Inverter by EV Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 