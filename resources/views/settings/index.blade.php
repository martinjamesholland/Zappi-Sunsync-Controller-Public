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
</div>
@endsection 