@extends('layouts.app')

@section('title', 'Zappi Status - Solar Battery EV Charger')

@section('styles')
<style>
    pre {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 15px;
        max-height: 600px;
        overflow: auto;
    }
    /* JSON Syntax Highlighting */
    .json-key { color: #881391; font-weight: bold; }
    .json-string { color: #1A1AA6; }
    .json-number { color: #1C00CF; }
    .json-boolean { color: #0000FF; }
    .json-null { color: #808080; }
    .json-punctuation { color: #000000; }
    .json-property { color: #881391; }
    .json-value { color: #1A1AA6; }
    .json-array { color: #000000; }
    .json-object { color: #000000; }
    .toolbar {
        margin-bottom: 20px;
    }
    .highlight-changed {
        background-color: #fffacd;
        transition: background-color 1s;
    }
    .card {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .card-header {
        background-color: #f1f8ff;
        font-weight: bold;
    }
    #auto-refresh {
        margin-right: 8px;
    }
    .table-status-value {
        font-weight: bold;
    }
    .tab-pane {
        padding-top: 20px;
    }
    .badge-ev-status {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
    .badge-mode {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
</style>
@endsection

@section('content')
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Zappi Charger Status</h2>
                    <div>
                        <div class="form-check form-switch d-inline-block me-3">
                            <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                            <label class="form-check-label" for="auto-refresh">Auto-refresh (30s)</label>
                        </div>
                       <!-- <button id="refresh-btn" class="btn btn-primary btn-sm">Refresh Data</button> -->
                    </div>
                </div>
                <div class="card-body">
                    <div id="status-timestamp" class="text-muted small mb-2">Last updated: {{ date('Y-m-d H:i:s') }}</div>
                    
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table" type="button" role="tab" aria-controls="table" aria-selected="true">Table View</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="json-tab" data-bs-toggle="tab" data-bs-target="#json" type="button" role="tab" aria-controls="json" aria-selected="false">JSON View</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="mapping-tab" data-bs-toggle="tab" data-bs-target="#mapping" type="button" role="tab" aria-controls="mapping" aria-selected="false">Field Mapping</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">
                            @if(isset($data['zappi']) && count($data['zappi']) > 0)
                                @php
                                    $zappi = $data['zappi'][0];
                                    
                                    // Map status codes to human-readable text
                                    $evStatusMap = [
                                        'A' => ['text' => 'EV Disconnected', 'class' => 'bg-secondary'],
                                        'B1' => ['text' => 'EV Connected', 'class' => 'bg-info'],
                                        'B2' => ['text' => 'Waiting for EV', 'class' => 'bg-info'],
                                        'C1' => ['text' => 'EV Ready to Charge', 'class' => 'bg-warning'],
                                        'C2' => ['text' => 'Charging', 'class' => 'bg-success'],
                                        'F' => ['text' => 'Fault', 'class' => 'bg-danger']
                                    ];
                                    
                                    $zappiModeMap = [
                                        1 => ['text' => 'Fast', 'class' => 'bg-danger'],
                                        2 => ['text' => 'Eco', 'class' => 'bg-warning'],
                                        3 => ['text' => 'Eco+', 'class' => 'bg-success'],
                                        4 => ['text' => 'Stopped', 'class' => 'bg-secondary']
                                    ];
                                    
                                    $statusMap = [
                                        1 => 'Paused',
                                        3 => 'Diverting/Charging',
                                        5 => 'Complete'
                                    ];
                                @endphp
                                
                                <div class="row mt-3">
                                    <div class="col-md-8">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4>Zappi Status</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="row mb-4">
                                                    <div class="col-md-3 text-center">
                                                        <div class="mb-2">EV Status</div>
                                                        <span class="badge {{ $evStatusMap[$zappi['pst']]['class'] ?? 'bg-secondary' }} badge-ev-status">
                                                            {{ $evStatusMap[$zappi['pst']]['text'] ?? 'Unknown' }}
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3 text-center">
                                                        <div class="mb-2">Charging Mode</div>
                                                        <span class="badge {{ $zappiModeMap[$zappi['zmo']]['class'] ?? 'bg-secondary' }} badge-mode">
                                                            {{ $zappiModeMap[$zappi['zmo']]['text'] ?? 'Unknown' }}
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3 text-center">
                                                        <div class="mb-2">Charger Status</div>
                                                        <span class="badge bg-primary badge-mode">
                                                            {{ $statusMap[$zappi['sta']] ?? 'Unknown' }}
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3 text-center">
                                                        <div class="mb-2">Time & Date</div>
                                                        <div>{{ $zappi['tim'] ?? '--:--:--' }}</div>
                                                        <div>{{ $zappi['dat'] ?? '--/--/----' }}</div>
                                                        @if(isset($zappi['tim']) && isset($zappi['dat']))
                                                            @php
                                                                try {
                                                                    $date = str_replace('/', '-', $zappi['dat']);
                                                                    $zappiDateTime = \Carbon\Carbon::createFromFormat('H:i:s d-m-Y', $zappi['tim'] . ' ' . $date);
                                                                    $now = \Carbon\Carbon::now();
                                                                    $diff = $now->diffForHumans($zappiDateTime);
                                                                } catch (\Exception $e) {
                                                                    $diff = 'Unable to calculate';
                                                                }
                                                            @endphp
                                                            <div class="text-muted small mt-1 zappi-last-updated">Last updated: {{ $diff }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <table class="table table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>Charge Added</td>
                                                            <td class="table-status-value">{{ $zappi['che'] ?? 0 }} kWh</td>
                                                            <td>Serial Number</td>
                                                            <td class="table-status-value">{{ $zappi['sno'] ?? 'Unknown' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Diversion Amount</td>
                                                            <td class="table-status-value">{{ $zappi['div'] ?? 0 }} W</td>
                                                            <td>Firmware Version</td>
                                                            <td class="table-status-value">{{ $zappi['fwv'] ?? 'Unknown' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Supply Voltage</td>
                                                            <td class="table-status-value">{{ $zappi['vol'] ?? 0 }} V</td>
                                                            <td>Supply Frequency</td>
                                                            <td class="table-status-value">{{ $zappi['frq'] ?? 0 }} Hz</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Grid Consumption</td>
                                                            <td class="table-status-value">{{ $zappi['grd'] ?? 0 }} W</td>
                                                            <td>Generated Power</td>
                                                            <td class="table-status-value">{{ $zappi['gen'] ?? 0 }} W</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Minimum Green Level</td>
                                                            <td class="table-status-value">{{ $zappi['mgl'] ?? 0 }}%</td>
                                                            <td>DST Enabled</td>
                                                            <td class="table-status-value">{{ $zappi['dst'] ? 'Yes' : 'No' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4>Boost Settings</h4>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>Smart Boost Time</td>
                                                            <td class="table-status-value">
                                                                @if(isset($zappi['sbh']) && isset($zappi['sbm']))
                                                                    {{ sprintf('%02d:%02d', $zappi['sbh'], $zappi['sbm']) }}
                                                                @else
                                                                    Not set
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Smart Boost Amount</td>
                                                            <td class="table-status-value">{{ $zappi['sbk'] ?? 0 }} kWh</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Boost Time</td>
                                                            <td class="table-status-value">
                                                                @if(isset($zappi['tbh']) && isset($zappi['tbm']))
                                                                    {{ sprintf('%02d:%02d', $zappi['tbh'], $zappi['tbm']) }}
                                                                @else
                                                                    Not set
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Boost Amount</td>
                                                            <td class="table-status-value">{{ $zappi['tbk'] ?? 0 }} kWh</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Remaining Boost</td>
                                                            <td class="table-status-value">
                                                                @if(isset($zappi['tbk']) && isset($zappi['che']))
                                                                    {{ max(0, $zappi['tbk'] - $zappi['che']) }} kWh
                                                                @else
                                                                    0 kWh
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h4>Power Readings</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>CT Sensor</th>
                                                        <th>Name</th>
                                                        <th>Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @for($i = 1; $i <= 6; $i++)
                                                        @if(isset($zappi["ectt{$i}"]) && $zappi["ectt{$i}"] > 0)
                                                            <tr>
                                                                <td>CT{{ $i }}</td>
                                                                <td>
                                                                    @switch($zappi["ectt{$i}"])
                                                                        @case(1)
                                                                            Grid
                                                                            @break
                                                                        @case(2)
                                                                            Generation
                                                                            @break
                                                                        @case(4)
                                                                            Battery
                                                                            @break
                                                                        @case(5)
                                                                            AC Battery
                                                                            @break
                                                                        @default
                                                                            Other/Unknown
                                                                    @endswitch
                                                                </td>
                                                                <td>{{ $zappi["ectp{$i}"] ?? 0 }} W</td>
                                                            </tr>
                                                        @endif
                                                    @endfor
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                            @else
                                <div class="alert alert-warning mt-3">
                                    <h4 class="alert-heading">No Zappi Data Available</h4>
                                    <p>Unable to retrieve data from your Zappi charger. This could be due to connection issues or the API may be temporarily unavailable.</p>
                                    <hr>
                                    <p class="mb-0">Please try refreshing the page or check your Zappi connection.</p>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane fade" id="json" role="tabpanel" aria-labelledby="json-tab">
                            <div class="row">
                                <div class="col-md-12">
                                    @if(isset($apiRequests) && count($apiRequests) > 0)
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h4>Zappi Status Data</h4>
                                            </div>
                                            <div class="card-body">
                                                @foreach($apiRequests as $key => $request)
                                                    <div class="mb-4">
                                                        <h5>Zappi Status Data</h5>
                                                        <div class="mb-3">
                                                            <strong>URL:</strong>
                                                            <pre><code>{{ $request['url'] }}</code></pre>
                                                        </div>
                                                        <div class="mb-3">
                                                            <strong>Method:</strong>
                                                            <pre><code>{{ $request['method'] }}</code></pre>
                                                        </div>
                                                        <div class="mb-3">
                                                            <strong>Headers:</strong>
                                                            <pre><code>{{ json_encode($request['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                        </div>
                                                        @if($request['body'])
                                                            <div class="mb-3">
                                                                <strong>Request Body:</strong>
                                                                <pre><code>{{ json_encode($request['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                            </div>
                                                        @endif
                                                        <div class="mb-3">
                                                            <strong>Response:</strong>
                                                            <pre><code>{{ json_encode($request['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                        </div>
                                                    </div>
                                                    @if(!$loop->last)
                                                        <hr class="my-4">
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="mapping" role="tabpanel" aria-labelledby="mapping-tab">
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h4>JSON Field Mapping Reference</h4>
                                            <p class="mb-0 text-muted">This table shows how the JSON fields from the API map to human-readable values</p>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>JSON Field</th>
                                                        <th>Description</th>
                                                        <th>Possible Values</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><code>pst</code></td>
                                                        <td>EV Status</td>
                                                        <td>
                                                            <ul class="list-unstyled mb-0">
                                                                <li><code>'A'</code>: EV Disconnected</li>
                                                                <li><code>'B1'</code>: EV Connected</li>
                                                                <li><code>'B2'</code>: Waiting for EV</li>
                                                                <li><code>'C1'</code>: EV Ready to Charge</li>
                                                                <li><code>'C2'</code>: Charging</li>
                                                                <li><code>'F'</code>: Fault</li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>zmo</code></td>
                                                        <td>Zappi Charging Mode</td>
                                                        <td>
                                                            <ul class="list-unstyled mb-0">
                                                                <li><code>1</code>: Fast (Using grid power)</li>
                                                                <li><code>2</code>: Eco (Preference for solar/green energy)</li>
                                                                <li><code>3</code>: Eco+ (Solar/green energy only)</li>
                                                                <li><code>4</code>: Stopped</li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>sta</code></td>
                                                        <td>Charger Status</td>
                                                        <td>
                                                            <ul class="list-unstyled mb-0">
                                                                <li><code>1</code>: Paused</li>
                                                                <li><code>3</code>: Diverting/Charging</li>
                                                                <li><code>5</code>: Complete</li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>tim</code></td>
                                                        <td>Device Time</td>
                                                        <td>Current time in HH:MM:SS format</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>dat</code></td>
                                                        <td>Device Date</td>
                                                        <td>Current date in DD/MM/YYYY format</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>che</code></td>
                                                        <td>Charge Added</td>
                                                        <td>Energy added during current charging session (kWh)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>div</code></td>
                                                        <td>Diversion Amount</td>
                                                        <td>Current power being diverted to EV (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>vol</code></td>
                                                        <td>Supply Voltage</td>
                                                        <td>Current supply voltage (V)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>frq</code></td>
                                                        <td>Supply Frequency</td>
                                                        <td>Current supply frequency (Hz)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>grd</code></td>
                                                        <td>Grid Consumption</td>
                                                        <td>Current power being drawn from grid (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>gen</code></td>
                                                        <td>Generated Power</td>
                                                        <td>Current solar/renewable power being generated (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>mgl</code></td>
                                                        <td>Minimum Green Level</td>
                                                        <td>Minimum percentage of green energy in Eco mode (%)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>dst</code></td>
                                                        <td>DST Enabled</td>
                                                        <td>Whether Daylight Saving Time is enabled</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>sno</code></td>
                                                        <td>Serial Number</td>
                                                        <td>Device serial number</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>fwv</code></td>
                                                        <td>Firmware Version</td>
                                                        <td>Current firmware version</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>sbh, sbm</code></td>
                                                        <td>Smart Boost Time</td>
                                                        <td>Smart boost hour and minute (24-hour format)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>sbk</code></td>
                                                        <td>Smart Boost Amount</td>
                                                        <td>Energy to be added by smart boost (kWh)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>tbh, tbm</code></td>
                                                        <td>Boost Time</td>
                                                        <td>Boost hour and minute (24-hour format)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>tbk</code></td>
                                                        <td>Boost Amount</td>
                                                        <td>Energy to be added by boost (kWh)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>ectt#</code></td>
                                                        <td>CT Sensor Type</td>
                                                        <td>
                                                            <ul class="list-unstyled mb-0">
                                                                <li><code>1</code>: Grid</li>
                                                                <li><code>2</code>: Generation</li>
                                                                <li><code>4</code>: Battery</li>
                                                                <li><code>5</code>: AC Battery</li>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>ectp#</code></td>
                                                        <td>CT Sensor Power Reading</td>
                                                        <td>Power reading from CT sensor (W)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtn = document.getElementById('refresh-btn');
        const autoRefresh = document.getElementById('auto-refresh');
        let autoRefreshInterval = null;
        const REFRESH_INTERVAL = 30000; // 30 seconds

        // Store the last known Zappi time
        let lastZappiTime = null;
        let lastZappiDate = null;

        // Simple function to update the time difference
        function updateTime() {
            const lastUpdatedElement = document.querySelector('.zappi-last-updated');
            if (!lastUpdatedElement || !lastZappiTime || !lastZappiDate) return;

            const [day, month, year] = lastZappiDate.split('/');
            const [hours, minutes, seconds] = lastZappiTime.split(':');
            const zappiTime = new Date(year, month - 1, day, hours, minutes, seconds);
            const now = new Date();
            const diffSeconds = Math.floor((now - zappiTime) / 1000);

            let text;
            if (diffSeconds < 60) text = `${diffSeconds} seconds ago`;
            else if (diffSeconds < 3600) text = `${Math.floor(diffSeconds / 60)} minutes ago`;
            else if (diffSeconds < 86400) text = `${Math.floor(diffSeconds / 3600)} hours ago`;
            else text = `${Math.floor(diffSeconds / 86400)} days ago`;

            lastUpdatedElement.textContent = `Last updated: ${text}`;
        }

        // Start the 5-second timer
        function startTimer() {
            // Clear any existing timer
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
            // Run immediately
            updateTime();
            // Set new timer
            autoRefreshInterval = setInterval(updateTime, 5000);
        }

        function updateUI(data) {
            // Update JSON view
            const jsonElements = document.querySelectorAll('pre code');
            jsonElements.forEach(element => {
                element.innerHTML = syntaxHighlight(JSON.stringify(data, null, 4));
            });

            // Only continue if we have zappi data
            if (!data.zappi || data.zappi.length === 0) {
                return;
            }

            const zappi = data.zappi[0];
            
            // Store the latest Zappi time and date
            if (zappi.tim && zappi.dat) {
                lastZappiTime = zappi.tim;
                lastZappiDate = zappi.dat;
                // Update time immediately
                updateTime();
            }
            
            // Update highlighted elements and apply a visual indicator for changes
            updateElementWithHighlight('.badge-ev-status', getEvStatusText(zappi.pst), getEvStatusClass(zappi.pst));
            updateElementWithHighlight('.badge-mode', getZappiModeText(zappi.zmo), getZappiModeClass(zappi.zmo));
            
            // Update table values
            updateTableValues(zappi);
        }

        function formatJSON() {
            const jsonElements = document.querySelectorAll('pre code');
            
            jsonElements.forEach(element => {
                const content = element.textContent;
                try {
                    const parsed = JSON.parse(content);
                    element.innerHTML = syntaxHighlight(JSON.stringify(parsed, null, 4));
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                }
            });
        }

        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        }

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

        // Toggle auto-refresh
        autoRefresh.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });

        // Manual refresh button
        refreshBtn.addEventListener('click', function() {
            refreshData();
        });

        function refreshData() {
            fetch('{{ url("/zappi/status") }}', {
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
                updateUI(data);
                document.getElementById('status-timestamp').textContent = 'Last updated: ' + new Date().toLocaleString();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                document.getElementById('status-timestamp').textContent = 'Error updating: ' + new Date().toLocaleString();
            });
        }

        // Helper functions for status updates
        function getEvStatusText(status) {
            const statusMap = {
                'A': 'EV Disconnected',
                'B1': 'EV Connected',
                'B2': 'Waiting for EV',
                'C1': 'EV Ready to Charge',
                'C2': 'Charging',
                'F': 'Fault'
            };
            return statusMap[status] || 'Unknown';
        }

        function getEvStatusClass(status) {
            const classMap = {
                'A': 'bg-secondary',
                'B1': 'bg-info',
                'B2': 'bg-info',
                'C1': 'bg-warning',
                'C2': 'bg-success',
                'F': 'bg-danger'
            };
            return classMap[status] || 'bg-secondary';
        }

        function getZappiModeText(mode) {
            const modeMap = {
                1: 'Fast',
                2: 'Eco',
                3: 'Eco+',
                4: 'Stopped'
            };
            return modeMap[mode] || 'Unknown';
        }

        function getZappiModeClass(mode) {
            const classMap = {
                1: 'bg-danger',
                2: 'bg-warning',
                3: 'bg-success',
                4: 'bg-secondary'
            };
            return classMap[mode] || 'bg-secondary';
        }

        function updateElementWithHighlight(selector, text, className) {
            const element = document.querySelector(selector);
            if (element) {
                const oldText = element.textContent;
                const oldClass = element.className;
                
                if (oldText !== text || oldClass !== className) {
                    element.textContent = text;
                    element.className = className + ' badge badge-ev-status';
                    element.classList.add('highlight-changed');
                    setTimeout(() => {
                        element.classList.remove('highlight-changed');
                    }, 1000);
                }
            }
        }

        function updateTableValues(zappi) {
            // Update all table values
            const tableCells = document.querySelectorAll('.table-status-value');
            tableCells.forEach(cell => {
                const key = cell.getAttribute('data-key');
                if (key && zappi[key] !== undefined) {
                    const oldValue = cell.textContent;
                    const newValue = formatValue(key, zappi[key]);
                    
                    if (oldValue !== newValue) {
                        cell.textContent = newValue;
                        cell.classList.add('highlight-changed');
                        setTimeout(() => {
                            cell.classList.remove('highlight-changed');
                        }, 1000);
                    }
                }
            });
        }

        function formatValue(key, value) {
            switch(key) {
                case 'che':
                case 'sbk':
                case 'tbk':
                    return value + ' kWh';
                case 'div':
                case 'grd':
                case 'gen':
                    return value + ' W';
                case 'vol':
                    return value + ' V';
                case 'frq':
                    return value + ' Hz';
                case 'mgl':
                    return value + '%';
                default:
                    return value;
            }
        }

        // Format JSON on load
        formatJSON();
        
        // Start both timers on page load
        startAutoRefresh();
        startTimer();

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        });
    });
</script>
@endsection 