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