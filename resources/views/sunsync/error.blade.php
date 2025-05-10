@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="mb-0">Error</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        {{ $message }}
                    </div>

                    @if(isset($apiError))
                        <div class="mt-4">
                            <h5>API Error Details:</h5>
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0"><code>{{ json_encode($apiError, JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    @endif

                    @if(isset($showSettingsLink) && $showSettingsLink)
                        <div class="text-center mt-4">
                            <a href="{{ route('settings.index') }}" class="btn btn-primary">
                                <i class="bi bi-gear"></i> Go to Settings
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 