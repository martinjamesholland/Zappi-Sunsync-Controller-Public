@extends('layouts.app')

@section('title', 'EV Charging Status - Solar Battery EV Charger')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">EV Charging Status</h5>
                    <div>
                        <div class="form-check form-switch d-inline-block me-3">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                            <label class="form-check-label" for="autoRefresh">Auto-refresh (5 min)</label>
                        </div>
                       <!-- <button class="btn btn-primary btn-sm" onclick="refreshData()">Refresh Now</button> -->
                        <a href="{{ route('ev-charging.status', ['test_mode' => 1]) }}" class="btn btn-warning btn-sm">Test Charging Mode</a>
                    </div>
                </div>

                <div class="card-body">
                    @if(request()->has('test_mode'))
                        <div class="alert alert-warning">
                            <strong>Test Mode Active:</strong> Simulating EV charging status
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Default Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('ev-charging.settings.update') }}" method="POST" id="defaultSettingsForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="defaultSellTime" class="form-label">Default Sell Time</label>
                                            <input type="time" class="form-control" id="defaultSellTime" name="default_sell_time" 
                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['default_sell_time'])->format('H:i') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="defaultCap" class="form-label">Default Cap (%)</label>
                                            <input type="number" class="form-control" id="defaultCap" name="default_cap" 
                                                   value="{{ $settings['default_cap'] }}" min="0" max="100">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Update Defaults</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Night Time Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('ev-charging.settings.update') }}" method="POST" id="nightTimeForm">
                                        @csrf
                                        <input type="hidden" name="default_sell_time" value="{{ $settings['default_sell_time'] }}">
                                        <input type="hidden" name="default_cap" value="{{ $settings['default_cap'] }}">
                                        <div class="mb-3">
                                            <label for="nightStart" class="form-label">Night Start Time</label>
                                            <input type="time" class="form-control" id="nightStart" name="night_start" 
                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['night_start'])->format('H:i') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="nightEnd" class="form-label">Night End Time</label>
                                            <input type="time" class="form-control" id="nightEnd" name="night_end" 
                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['night_end'])->format('H:i') }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Update Night Times</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Cron Job Configuration</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">To enable automatic EV charging status updates, you can set up a cron job on your server. This will ensure regular updates even when the web interface is not open.</p>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Cron Command</h6>
                                    <p class="mb-0">Add the following line to your server's crontab (runs every 5 minutes):</p>
                                    <pre class="mt-2 mb-0"><code>*/5 * * * * curl -s "{{ url('/ev-charging/status?cron_mode=1') }}" > /dev/null 2>&1</code></pre>
                                </div>

                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">Important Notes</h6>
                                    <ul class="mb-0">
                                        <li>Make sure the cron job runs as the same user that owns the Laravel application files</li>
                                        <li>The cron job requires proper permissions to access the application files</li>
                                        <li>You can adjust the frequency by modifying the cron schedule (e.g., */10 for every 10 minutes)</li>
                                    </ul>
                                </div>

                                <div class="mt-3">
                                    <h6>How cron_mode works:</h6>
                                    <ul>
                                        <li>When <code>cron_mode=1</code> is added to the URL, the system runs in a special mode that:</li>
                                        <ul>
                                            <li>Returns JSON responses instead of HTML views</li>
                                            <li>Includes detailed logs and API call information</li>
                                            <li>Provides current settings and status updates</li>
                                            <li>Can be used for monitoring and debugging</li>
                                        </ul>
                                        <li>Example response format:</li>
                                        <pre class="mt-2"><code>{
    "success": true,
    "timestamp": "2024-03-21 10:00:00",
    "logs": [...],
    "api_calls": [...],
    "current_settings": {...},
    "error_message": null
}</code></pre>
                                    </ul>
                                </div>

                                <div class="mt-3">
                                    <h6>To set up the cron job:</h6>
                                    <ol>
                                        <li>Access your server via SSH</li>
                                        <li>Run <code>crontab -e</code> to edit the crontab</li>
                                        <li>Add the cron command shown above</li>
                                        <li>Save and exit the editor</li>
                                    </ol>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <h6 class="alert-heading">Testing the Cron Mode</h6>
                                    <p class="mb-0">You can test the cron mode by visiting: <a href="{{ url('/ev-charging/status?cron_mode=1') }}" target="_blank">{{ url('/ev-charging/status?cron_mode=1') }}</a></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Last Updated:</strong> {{ now()->format('Y-m-d H:i:s') }}
                    </div>

                    <div class="log-container" style="max-height: 400px; overflow-y: auto;">
                        @foreach($logs as $log)
                            <div class="log-entry">
                                <span class="text-muted">{{ now()->format('H:i:s') }}</span> {{ $log }}
                            </div>
                        @endforeach
                    </div>

                    @if(!empty($apiCalls))
                        <div class="mt-4">
                            <h6>API Calls <small class="text-muted">(Total: {{ count($apiCalls) }})</small></h6>
                            <div class="api-calls-container" style="max-height: 400px; overflow-y: auto;">
                                @foreach($apiCalls as $index => $call)
                                    <div class="api-call mb-3">
                                        <div class="api-call-header">
                                            <strong>{{ $call['name'] ?? 'Unnamed API Call' }}</strong>
                                            <small class="text-muted ms-2">{{ isset($call['endpoint']) ? preg_replace('/\/api\/v1\/plant\/(\d+)\//', '/api/v1/plant/******/', $call['endpoint']) : 'No endpoint' }}</small>
                                            <small class="text-muted ms-2">(#{{ $index + 1 }})</small>
                                        </div>
                                        @if(isset($call['request']))
                                            <div class="api-call-request">
                                                <small class="text-muted">Request:</small>
                                                <pre class="mb-1"><code>@php
    if (isset($call['request']) && is_array($call['request'])) {
        $maskedRequest = $call['request'];
        if (isset($maskedRequest['plantId'])) {
            $maskedRequest['plantId'] = '******';
        }
        echo json_encode($maskedRequest, JSON_PRETTY_PRINT);
    } else {
        echo json_encode($call['request'], JSON_PRETTY_PRINT);
    }
@endphp</code></pre>
                                            </div>
                                        @endif
                                        @if(isset($call['response']))
                                            <div class="api-call-response">
                                                <small class="text-muted">Response:</small>
                                                <pre class="mb-0"><code>{{ isset($call['response']) ? 'JSON Response: ' . (empty($call['response']) ? 'Empty' : 'Success') : 'No response' }}</code></pre>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-container {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    font-family: monospace;
}
.log-entry {
    margin-bottom: 0.25rem;
}
.api-calls-container {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
}
.api-call {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 1rem;
}
.api-call:last-child {
    border-bottom: none;
}
.api-call pre {
    background-color: #fff;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin: 0.25rem 0;
}
</style>

<script>
let autoRefreshInterval = null;
const REFRESH_INTERVAL = 30000; // 30 seconds

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    refreshData(); // Initial refresh
    autoRefreshInterval = setInterval(refreshData, REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshData() {
    fetch('{{ url("/ev-charging/status") }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Update the dashboard content
        if (data.html) {
            document.querySelector('.card-body').innerHTML = data.html;
        }
        document.getElementById('status-timestamp').textContent = 'Last updated: ' + new Date().toLocaleString();
    })
    .catch(error => {
        console.error('Error fetching data:', error);
        document.getElementById('status-timestamp').textContent = 'Error updating: ' + new Date().toLocaleString();
    });
}

// Initialize auto-refresh
document.addEventListener('DOMContentLoaded', function() {
    const autoRefreshCheckbox = document.getElementById('autoRefresh');
    
    if (autoRefreshCheckbox) {
        if (autoRefreshCheckbox.checked) {
            startAutoRefresh();
        }
        
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});
</script>
@endsection 