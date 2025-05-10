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

                            <div class="mb-3">
                                <label for="ZAPPI_SERIAL" class="form-label">Zappi Serial Number</label>
                                <input type="text" class="form-control @error('ZAPPI_SERIAL') is-invalid @enderror" 
                                    id="ZAPPI_SERIAL" name="ZAPPI_SERIAL" 
                                    value="{{ old('ZAPPI_SERIAL', $settings['ZAPPI_SERIAL']) }}" required>
                                @error('ZAPPI_SERIAL')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="ZAPPI_PASSWORD" class="form-label">Zappi API Key</label>
                                <input type="password" class="form-control @error('ZAPPI_PASSWORD') is-invalid @enderror" 
                                    id="ZAPPI_PASSWORD" name="ZAPPI_PASSWORD" 
                                    value="{{ old('ZAPPI_PASSWORD', $settings['ZAPPI_PASSWORD']) }}" required>
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

                            <div class="mb-3">
                                <label for="SUNSYNC_USERNAME" class="form-label">SunSync Username</label>
                                <input type="text" class="form-control @error('SUNSYNC_USERNAME') is-invalid @enderror" 
                                    id="SUNSYNC_USERNAME" name="SUNSYNC_USERNAME" 
                                    value="{{ old('SUNSYNC_USERNAME', $settings['SUNSYNC_USERNAME']) }}" required>
                                @error('SUNSYNC_USERNAME')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="SUNSYNC_PASSWORD" class="form-label">SunSync Password</label>
                                <input type="password" class="form-control @error('SUNSYNC_PASSWORD') is-invalid @enderror" 
                                    id="SUNSYNC_PASSWORD" name="SUNSYNC_PASSWORD" 
                                    value="{{ old('SUNSYNC_PASSWORD', $settings['SUNSYNC_PASSWORD']) }}" required>
                                @error('SUNSYNC_PASSWORD')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 