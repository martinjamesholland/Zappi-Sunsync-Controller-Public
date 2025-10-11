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
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#testModeModal">Test Charging Mode</button>
                    </div>
                </div>

                <div class="card-body">
                    @if(request()->has('test_mode'))
                        <div class="alert alert-warning">
                            <strong>Test Mode Active:</strong> Simulating EV charging status
                        </div>
                    @endif

                    <!-- Time Slot Settings -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">SunSync Time Slot Configuration</h6>
                                    <small class="text-muted">Configure the 6 time slots that control battery charging and discharging</small>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('ev-charging.settings.update') }}" method="POST" id="timeSlotSettingsForm">
                                        @csrf
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Slot</th>
                                                        <th>Time</th>
                                                        <th>Battery Cap (%)</th>
                                                        <th style="text-align: center;">Grid Charge</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><strong>Slot 1</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="sell_time_1" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['sell_time_1'] ?? '00:00')->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="cap_1" 
                                                                   value="{{ $settings['cap_1'] ?? '100' }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" name="time_1_on" value="1" 
                                                                       id="time1on" {{ ($settings['time_1_on'] ?? 'true') === 'true' || ($settings['time_1_on'] ?? 'true') === '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="time1on">
                                                                    <small>{{ ($settings['time_1_on'] ?? 'true') === 'true' || ($settings['time_1_on'] ?? 'true') === '1' ? 'ON' : 'OFF' }}</small>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><small class="text-muted">Midnight to early morning</small></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Slot 2</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="sell_time_2" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['sell_time_2'] ?? '03:00')->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="cap_2" 
                                                                   value="{{ $settings['cap_2'] ?? '100' }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" name="time_2_on" value="1" 
                                                                       id="time2on" {{ ($settings['time_2_on'] ?? 'true') === 'true' || ($settings['time_2_on'] ?? 'true') === '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="time2on">
                                                                    <small>{{ ($settings['time_2_on'] ?? 'true') === 'true' || ($settings['time_2_on'] ?? 'true') === '1' ? 'ON' : 'OFF' }}</small>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><small class="text-muted">Early morning charging</small></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Slot 3</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="sell_time_3" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['sell_time_3'] ?? '05:30')->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="cap_3" 
                                                                   value="{{ $settings['cap_3'] ?? '25' }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" name="time_3_on" value="1" 
                                                                       id="time3on" {{ ($settings['time_3_on'] ?? 'false') === 'true' || ($settings['time_3_on'] ?? 'false') === '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="time3on">
                                                                    <small>{{ ($settings['time_3_on'] ?? 'false') === 'true' || ($settings['time_3_on'] ?? 'false') === '1' ? 'ON' : 'OFF' }}</small>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><small class="text-muted">Dawn period</small></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Slot 4</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="sell_time_4" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['sell_time_4'] ?? '08:00')->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="cap_4" 
                                                                   value="{{ $settings['cap_4'] ?? '25' }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" name="time_4_on" value="1" 
                                                                       id="time4on" {{ ($settings['time_4_on'] ?? 'false') === 'true' || ($settings['time_4_on'] ?? 'false') === '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="time4on">
                                                                    <small>{{ ($settings['time_4_on'] ?? 'false') === 'true' || ($settings['time_4_on'] ?? 'false') === '1' ? 'ON' : 'OFF' }}</small>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><small class="text-muted">Morning period</small></td>
                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td><strong>Slot 5</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="default_sell_time" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['default_sell_time'])->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="default_cap" 
                                                                   value="{{ $settings['default_cap'] }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <small class="text-muted">Auto</small>
                                                        </td>
                                                        <td><small class="text-muted"><strong>Primary EV charging slot</strong> (Auto-controlled)</small></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Slot 6</strong></td>
                                                        <td>
                                                            <input type="time" class="form-control form-control-sm" name="sell_time_6" 
                                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['sell_time_6'] ?? '23:30')->format('H:i') }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="cap_6" 
                                                                   value="{{ $settings['cap_6'] ?? '100' }}" min="0" max="100">
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="form-check form-switch d-inline-block">
                                                                <input class="form-check-input" type="checkbox" name="time_6_on" value="1" 
                                                                       id="time6on" {{ ($settings['time_6_on'] ?? 'true') === 'true' || ($settings['time_6_on'] ?? 'true') === '1' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="time6on">
                                                                    <small>{{ ($settings['time_6_on'] ?? 'true') === 'true' || ($settings['time_6_on'] ?? 'true') === '1' ? 'ON' : 'OFF' }}</small>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><small class="text-muted">Night period</small></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Save All Time Slots</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Night Time Range -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Night Time Range</h6>
                                    <small class="text-muted">Define when the system considers it "night time"</small>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('ev-charging.settings.update') }}" method="POST" id="nightTimeForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="nightStart" class="form-label">Night Start Time</label>
                                            <input type="time" class="form-control" id="nightStart" name="night_start" 
                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['night_start'])->format('H:i') }}">
                                            <small class="form-text text-muted">When night mode begins</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nightEnd" class="form-label">Night End Time</label>
                                            <input type="time" class="form-control" id="nightEnd" name="night_end" 
                                                   value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['night_end'])->format('H:i') }}">
                                            <small class="form-text text-muted">When night mode ends</small>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm">Update Night Times</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info bg-opacity-10">
                                <div class="card-header bg-info bg-opacity-25">
                                    <h6 class="mb-0">ℹ️ How Time Slots Work</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>Time Slots</strong> control when and how much battery can be used</li>
                                        <li><strong>Cap %</strong> sets the minimum battery level for each period</li>
                                        <li><strong>Grid Charge</strong> ON = inverter CAN charge battery from grid during this time slot<br>
                                            OFF = inverter CANNOT charge from grid (solar only)</li>
                                        <li><strong>Slot 5</strong> is automatically adjusted when EV charges during daytime</li>
                                        <li><strong>Night Time</strong> prevents EV charging adjustments during specified hours</li>
                                    </ul>
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
                                        <li>To save the output to a log file, modify the command to: <code>*/5 * * * * curl -s "{{ url('/ev-charging/status?cron_mode=1') }}" >> /path/to/logfile.log 2>&1</code></li>
                                    </ul>
                                </div>

                                <div class="mt-3">
                                    <h6>How cron_mode works:</h6>
                                    <ul>
                                        <li>When <code>cron_mode=1</code> is added to the URL, the system runs in a special mode that:</li>
                                        <ul>
                                            <li>Returns plain text output by default for easy reading in logs</li>
                                            <li>Includes detailed logs and API call information</li>
                                            <li>Provides current settings and status updates</li>
                                            <li>Can be used for monitoring and debugging</li>
                                        </ul>
                                        <li>You can still get JSON output by adding <code>&format=json</code> to the URL</li>
                                        <li>Example plain text output:</li>
                                        <pre class="mt-2"><code>=== EV CHARGING STATUS UPDATE: 2024-03-21 10:00:00 ===

Status: SUCCESS

--- LOGS ---
Retrieved Zappi status at 2024-03-21 10:00:00
EV Status: Not Charging
Time Check: Day Time
No settings update needed - all values already match

--- API CALLS ---
1. MyEnergi API - Get Zappi Status
   Endpoint: GET /api/zappi/status
   Response: Success

2. SunSync API - Get Current Settings
   Endpoint: GET /api/v1/common/setting/******/read
   Response: Success

========== END OF REPORT ==========</code></pre>
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
                        <strong>Last Updated:</strong> <span id="status-timestamp">{{ now()->timezone('Europe/London')->format('Y-m-d H:i:s') }}</span>
                    </div>

                    <div class="log-container" style="max-height: 400px; overflow-y: auto;">
                        @foreach($logs as $log)
                            <div class="log-entry">
                                <span class="text-muted">{{ now()->timezone('Europe/London')->format('H:i:s') }}</span> {{ $log }}
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

<!-- Test Mode Modal -->
<div class="modal fade" id="testModeModal" tabindex="-1" aria-labelledby="testModeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testModeModalLabel">Enter APP_KEY for Test Mode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ev-charging.status') }}" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="app_key" class="form-label">APP_KEY</label>
                        <input type="password" class="form-control" id="app_key" name="app_key" required>
                        <input type="hidden" name="test_mode" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Enter Test Mode</button>
                </div>
            </form>
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

// Update switch labels when toggled
document.addEventListener('DOMContentLoaded', function() {
    const switches = document.querySelectorAll('[id^="time"][id$="on"]');
    switches.forEach(function(switchEl) {
        switchEl.addEventListener('change', function() {
            const label = this.nextElementSibling.querySelector('small');
            if (label) {
                label.textContent = this.checked ? 'ON' : 'OFF';
            }
        });
    });
});
</script>
@endsection 