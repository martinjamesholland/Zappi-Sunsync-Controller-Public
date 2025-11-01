@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">API Settings</h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(isset($dbError))
                        <div class="alert alert-danger">
                            <h5>Database Connection Error</h5>
                            <p>There was an error connecting to your database. Please update your database settings below:</p>
                            <div class="mt-2 p-2 bg-light rounded">
                                <code>{{ $dbError }}</code>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Zappi Settings -->
                        <div class="mb-4">
                            <h4>Zappi Settings</h4>
                            <div class="alert alert-info">
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

                            <!-- Current Status -->
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Current Zappi Configuration</h5>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Serial Number:</span>
                                            @if($settingsStatus['ZAPPI_SERIAL'])
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">API Key:</span>
                                            @if($settingsStatus['ZAPPI_PASSWORD'])
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="ZAPPI_SERIAL" class="form-label">Zappi Serial Number</label>
                                <input type="text" class="form-control @error('ZAPPI_SERIAL') is-invalid @enderror" 
                                    id="ZAPPI_SERIAL" name="ZAPPI_SERIAL" 
                                    placeholder="Enter your Zappi serial number"
                                    value="{{ old('ZAPPI_SERIAL') }}">
                                @error('ZAPPI_SERIAL')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="ZAPPI_PASSWORD" class="form-label">Zappi API Key</label>
                                <input type="password" class="form-control @error('ZAPPI_PASSWORD') is-invalid @enderror" 
                                    id="ZAPPI_PASSWORD" name="ZAPPI_PASSWORD" 
                                    placeholder="Enter your Zappi API key">
                                @error('ZAPPI_PASSWORD')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- SunSync Settings -->
                        <div class="mb-4">
                            <h4>SunSync Settings</h4>
                            <div class="alert alert-info">
                                <h5>How to get your SunSync credentials:</h5>
                                <ol>
                                    <li>Go to <a href="https://sunsynk.net/login" target="_blank">SunSync Connect</a></li>
                                    <li>Log in with your SunSync account</li>
                                    <li>Use your SunSync Connect username and password</li>
                                </ol>
                            </div>

                            <!-- Current Status -->
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Current SunSync Configuration</h5>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Username:</span>
                                            @if($settingsStatus['SUNSYNC_USERNAME'])
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">Password:</span>
                                            @if($settingsStatus['SUNSYNC_PASSWORD'])
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="SUNSYNC_USERNAME" class="form-label">SunSync Username</label>
                                <input type="text" class="form-control @error('SUNSYNC_USERNAME') is-invalid @enderror" 
                                    id="SUNSYNC_USERNAME" name="SUNSYNC_USERNAME" 
                                    placeholder="Enter your SunSync username"
                                    value="{{ old('SUNSYNC_USERNAME') }}">
                                @error('SUNSYNC_USERNAME')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="SUNSYNC_PASSWORD" class="form-label">SunSync Password</label>
                                <input type="password" class="form-control @error('SUNSYNC_PASSWORD') is-invalid @enderror" 
                                    id="SUNSYNC_PASSWORD" name="SUNSYNC_PASSWORD" 
                                    placeholder="Enter your SunSync password">
                                @error('SUNSYNC_PASSWORD')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Database Settings -->
                        <div class="mb-4">
                            <h4>Database Settings</h4>
                            <div class="alert alert-info">
                                <h5>Database Configuration Information:</h5>
                                <p>These settings control how the application connects to your database. Make sure to use secure credentials and keep them private.</p>
                            </div>

                            <!-- Current Status -->
                            <div class="mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Current Database Configuration</h5>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Database Name:</span>
                                            @if(env('DB_DATABASE'))
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Host:</span>
                                            @if(env('DB_HOST'))
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Port:</span>
                                            @if(env('DB_PORT'))
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="me-2">Username:</span>
                                            @if(env('DB_USERNAME'))
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">Password:</span>
                                            @if(env('DB_PASSWORD'))
                                                <span class="badge bg-success">Configured</span>
                                            @else
                                                <span class="badge bg-danger">Not Configured</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="DB_DATABASE" class="form-label">Database Name</label>
                                <input type="text" class="form-control @error('DB_DATABASE') is-invalid @enderror" 
                                    id="DB_DATABASE" name="DB_DATABASE" 
                                    placeholder="Enter database name"
                                    value="{{ old('DB_DATABASE') }}">
                                @error('DB_DATABASE')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="DB_HOST" class="form-label">Database Host</label>
                                <input type="text" class="form-control @error('DB_HOST') is-invalid @enderror" 
                                    id="DB_HOST" name="DB_HOST" 
                                    placeholder="Enter database host (e.g., localhost)"
                                    value="{{ old('DB_HOST') }}">
                                @error('DB_HOST')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="DB_PORT" class="form-label">Database Port</label>
                                <input type="text" class="form-control @error('DB_PORT') is-invalid @enderror" 
                                    id="DB_PORT" name="DB_PORT" 
                                    placeholder="Enter database port (e.g., 3306)"
                                    value="{{ old('DB_PORT') }}">
                                @error('DB_PORT')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="DB_USERNAME" class="form-label">Database Username</label>
                                <input type="text" class="form-control @error('DB_USERNAME') is-invalid @enderror" 
                                    id="DB_USERNAME" name="DB_USERNAME" 
                                    placeholder="Enter database username"
                                    value="{{ old('DB_USERNAME') }}">
                                @error('DB_USERNAME')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="DB_PASSWORD" class="form-label">Database Password</label>
                                <input type="password" class="form-control @error('DB_PASSWORD') is-invalid @enderror" 
                                    id="DB_PASSWORD" name="DB_PASSWORD" 
                                    placeholder="Enter database password">
                                @error('DB_PASSWORD')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cost Settings Section -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card" id="cost-settings">
                <div class="card-header">
                    <h2 class="mb-0"><i class="bi bi-currency-pound"></i> Cost Settings</h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="cost_settings" value="1">

                        <!-- Cost Rates -->
                        <div class="mb-4">
                            <h4>Electricity Rates</h4>
                            
                            <div class="mb-3">
                                <label for="peak_rate" class="form-label">Peak Rate (per kWh)</label>
                                <div class="input-group">
                                    <span class="input-group-text">£</span>
                                    <input type="number" step="0.01" class="form-control @error('peak_rate') is-invalid @enderror" 
                                        id="peak_rate" name="peak_rate" 
                                        value="{{ old('peak_rate', $costSettings['peak_rate'] ?? 0.30) }}"
                                        placeholder="0.30">
                                </div>
                                <small class="text-muted">Charged during peak hours</small>
                                @error('peak_rate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="off_peak_rate" class="form-label">Off-Peak Rate (per kWh)</label>
                                <div class="input-group">
                                    <span class="input-group-text">£</span>
                                    <input type="number" step="0.01" class="form-control @error('off_peak_rate') is-invalid @enderror" 
                                        id="off_peak_rate" name="off_peak_rate" 
                                        value="{{ old('off_peak_rate', $costSettings['off_peak_rate'] ?? 0.07) }}"
                                        placeholder="0.07">
                                </div>
                                <small class="text-muted">Charged during off-peak hours</small>
                                @error('off_peak_rate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="ev_charging_rate" class="form-label">EV Charging Rate (per kWh)</label>
                                <div class="input-group">
                                    <span class="input-group-text">£</span>
                                    <input type="number" step="0.01" class="form-control @error('ev_charging_rate') is-invalid @enderror" 
                                        id="ev_charging_rate" name="ev_charging_rate" 
                                        value="{{ old('ev_charging_rate', $costSettings['ev_charging_rate'] ?? 0.07) }}"
                                        placeholder="0.07">
                                </div>
                                <small class="text-muted">Rate charged for EV charging from grid</small>
                                @error('ev_charging_rate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="export_credit_rate" class="form-label">Export Credit (per kWh)</label>
                                <div class="input-group">
                                    <span class="input-group-text">£</span>
                                    <input type="number" step="0.01" class="form-control @error('export_credit_rate') is-invalid @enderror" 
                                        id="export_credit_rate" name="export_credit_rate" 
                                        value="{{ old('export_credit_rate', $costSettings['export_credit_rate'] ?? 0.15) }}"
                                        placeholder="0.15">
                                </div>
                                <small class="text-muted">Credit received for exporting to grid</small>
                                @error('export_credit_rate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Peak Hours -->
                        <div class="mb-4">
                            <h4>Peak Hours</h4>
                            <p class="text-muted">Define when peak rates apply (off-peak is the opposite)</p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="peak_start_hour" class="form-label">Peak Start Time</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" class="form-control @error('peak_start_hour') is-invalid @enderror" 
                                                id="peak_start_hour" name="peak_start_hour" 
                                                value="{{ old('peak_start_hour', $costSettings['peak_start_hour'] ?? 5) }}"
                                                min="0" max="23" placeholder="Hour">
                                            <small class="text-muted">Hour (0-23)</small>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control @error('peak_start_minute') is-invalid @enderror" 
                                                id="peak_start_minute" name="peak_start_minute" 
                                                value="{{ old('peak_start_minute', $costSettings['peak_start_minute'] ?? 30) }}"
                                                min="0" max="59" placeholder="Minute">
                                            <small class="text-muted">Minute (0-59)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="peak_end_hour" class="form-label">Peak End Time</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" class="form-control @error('peak_end_hour') is-invalid @enderror" 
                                                id="peak_end_hour" name="peak_end_hour" 
                                                value="{{ old('peak_end_hour', $costSettings['peak_end_hour'] ?? 23) }}"
                                                min="0" max="23" placeholder="Hour">
                                            <small class="text-muted">Hour (0-23)</small>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control @error('peak_end_minute') is-invalid @enderror" 
                                                id="peak_end_minute" name="peak_end_minute" 
                                                value="{{ old('peak_end_minute', $costSettings['peak_end_minute'] ?? 30) }}"
                                                min="0" max="59" placeholder="Minute">
                                            <small class="text-muted">Minute (0-59)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <strong>Current Setting:</strong> Peak hours are 
                                {{ sprintf('%02d:%02d', $costSettings['peak_start_hour'] ?? 5, $costSettings['peak_start_minute'] ?? 30) }} to 
                                {{ sprintf('%02d:%02d', $costSettings['peak_end_hour'] ?? 23, $costSettings['peak_end_minute'] ?? 30) }}
                                <br>
                                <small>During this period, peak rates apply. Outside this period, off-peak rates apply.</small>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Cost Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 