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

                    @if(isset($apiRequests) && count($apiRequests) > 0)
                        <div class="mt-4">
                            <h4>API Response Details</h4>
                            @foreach($apiRequests as $request)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Request Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>URL:</strong>
                                            <pre><code>{{ $request['url'] }}</code></pre>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Method:</strong>
                                            <pre><code>{{ $request['method'] }}</code></pre>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Response:</strong>
                                            <pre><code>{{ json_encode($request['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
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