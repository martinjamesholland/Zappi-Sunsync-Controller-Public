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
                    {{-- Inverter Status Banner --}}
                    @if(isset($inverterInfo) && isset($inverterSettings))
                        @php
                            $currentMode = $inverterSettings['sysWorkMode'] ?? '2';
                            $isDischargeMode = $currentMode === '0';
                            $modeText = $isDischargeMode ? 'Discharge Mode' : 'Normal Mode';
                            $modeColor = $isDischargeMode ? 'warning' : 'success';
                            $modeIcon = $isDischargeMode ? 'battery-charging' : 'house-check';
                            $inverterModel = $inverterInfo['model'] ?? $inverterInfo['inverterModel'] ?? 'Unknown Model';
                            $inverterSn = $inverterInfo['sn'] ?? 'N/A';
                        @endphp
                        <div class="alert alert-{{ $modeColor }} alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-{{ $modeIcon }} fs-4 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Inverter Status:</strong> {{ $inverterModel }} (S/N: {{ $inverterSn }})
                                <span class="badge bg-{{ $modeColor }} ms-2">{{ $modeText }}</span>
                                @if($isDischargeMode)
                                    <small class="d-block text-muted mt-1">
                                        <i class="bi bi-arrow-down-circle"></i> Battery is discharging to grid
                                    </small>
                                @else
                                    <small class="d-block text-muted mt-1">
                                        <i class="bi bi-check-circle"></i> Operating in normal mode (limited to home)
                                    </small>
                                @endif
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(request()->has('test_mode'))
                        <div class="alert alert-warning">
                            <strong>Test Mode Active:</strong> Simulating EV charging status
                        </div>
                    @endif

                    <!-- Time Slot Settings -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">SunSync Time Slot Configuration</h6>
                                        <small class="text-muted">Configure the 6 time slots that control battery charging and discharging</small>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-info btn-sm me-2" id="refreshInverterBtn">
                                            <i class="bi bi-arrow-clockwise"></i> Refresh Current Values
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" id="syncToInverterBtn">
                                            <i class="bi bi-cloud-upload"></i> Sync to Inverter Now
                                        </button>
                                    </div>
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
                                                        <th>Current Inverter Value</th>
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime1'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap1'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time1on'] ?? false) === true || ($inverterSettings['time1on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime2'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap2'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time2on'] ?? false) === true || ($inverterSettings['time2on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime3'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap3'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time3on'] ?? false) === true || ($inverterSettings['time3on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime4'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap4'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time4on'] ?? false) === true || ($inverterSettings['time4on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime5'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap5'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time5on'] ?? false) === true || ($inverterSettings['time5on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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
                                                        <td>
                                                            @if(isset($inverterSettings))
                                                                <small class="text-muted">
                                                                    Time: {{ $inverterSettings['sellTime6'] ?? 'N/A' }}<br>
                                                                    Cap: {{ $inverterSettings['cap6'] ?? 'N/A' }}%<br>
                                                                    Grid: {{ (($inverterSettings['time6on'] ?? false) === true || ($inverterSettings['time6on'] ?? false) === 'true') ? 'ON' : 'OFF' }}
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Loading...</small>
                                                            @endif
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

                    <!-- Battery Discharge to Grid Settings -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning bg-opacity-10">
                                    <h6 class="mb-0">🔋 Battery Discharge to Grid Settings</h6>
                                    <small class="text-muted">Configure automatic battery discharge to grid during evening peak hours</small>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('ev-charging.settings.update') }}" method="POST" id="batteryDischargeForm">
                                        @csrf
                                        
                                        <div class="row">
                                            <!-- Enable/Disable Feature -->
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="discharge_enabled" value="true" 
                                                           id="dischargeEnabled" {{ ($settings['discharge_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="dischargeEnabled">
                                                        <strong>Enable Battery Discharge to Grid</strong>
                                                    </label>
                                                </div>
                                                <small class="text-muted">When enabled, the system will automatically discharge excess battery to the grid during configured times</small>
                                            </div>
                                        </div>

                                        <div id="dischargeSettingsPanel" style="{{ ($settings['discharge_enabled'] ?? 'false') === 'true' ? '' : 'display: none;' }}">
                                            <div class="row">
                                                <!-- Battery Size -->
                                                <div class="col-md-4 mb-3">
                                                    <label for="batterySizeWh" class="form-label">Total Battery Capacity (Wh)</label>
                                                    <input type="number" class="form-control" id="batterySizeWh" name="battery_size_wh" 
                                                           value="{{ $settings['battery_size_wh'] ?? '10000' }}" min="1000" max="100000" step="100">
                                                    <small class="form-text text-muted">Example: 10kWh battery = 10000 Wh</small>
                                                </div>

                                                <!-- Discharge Rate -->
                                                <div class="col-md-4 mb-3">
                                                    <label for="dischargeRateW" class="form-label">Discharge Rate (W)</label>
                                                    <input type="number" class="form-control" id="dischargeRateW" name="discharge_rate_w" 
                                                           value="{{ $settings['discharge_rate_w'] ?? '2750' }}" min="100" max="10000" step="50">
                                                    <small class="form-text text-muted">Maximum discharge power output (e.g., 2750W)</small>
                                                </div>

                                                <!-- House Load -->
                                                <div class="col-md-4 mb-3">
                                                    <label for="houseLoadW" class="form-label">Average House Load (W)</label>
                                                    <input type="number" class="form-control" id="houseLoadW" name="house_load_w" 
                                                           value="{{ $settings['house_load_w'] ?? '350' }}" min="0" max="5000" step="10">
                                                    <small class="form-text text-muted">Average power consumption (e.g., 350W)</small>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Discharge To SOC -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="dischargeToSoc" class="form-label">Discharge To (% SOC)</label>
                                                    <input type="number" class="form-control" id="dischargeToSoc" name="discharge_to_soc" 
                                                           value="{{ $settings['discharge_to_soc'] ?? '20' }}" min="0" max="100" step="1">
                                                    <small class="form-text text-muted">Stop discharging when battery reaches this level (e.g., 20%)</small>
                                                </div>

                                                <!-- Minimum SOC at Check Time -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="dischargeMinSoc" class="form-label">Minimum SOC at Check Time (%)</label>
                                                    <input type="number" class="form-control" id="dischargeMinSoc" name="discharge_min_soc" 
                                                           value="{{ $settings['discharge_min_soc'] ?? '50' }}" min="0" max="100" step="1">
                                                    <small class="form-text text-muted">Don't enable discharge if battery is below this at check time (e.g., 50%)</small>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <!-- Check Time -->
                                                <div class="col-md-4 mb-3">
                                                    <label for="dischargeCheckTime" class="form-label">Check Time</label>
                                                    <input type="time" class="form-control" id="dischargeCheckTime" name="discharge_check_time" 
                                                           value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['discharge_check_time'] ?? '20:00')->format('H:i') }}">
                                                    <small class="form-text text-muted">Time to check if discharge should be enabled (e.g., 20:00 for 8pm)</small>
                                                </div>

                                                <!-- Stop Time -->
                                                <div class="col-md-4 mb-3">
                                                    <label for="dischargeStopTime" class="form-label">Stop Time</label>
                                                    <input type="time" class="form-control" id="dischargeStopTime" name="discharge_stop_time" 
                                                           value="{{ \Carbon\Carbon::createFromFormat('H:i', $settings['discharge_stop_time'] ?? '23:45')->format('H:i') }}">
                                                    <small class="form-text text-muted">Time to stop discharge and return to normal (e.g., 23:45)</small>
                                                </div>

                                                <!-- Calculated Start Time (Display Only) -->
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Calculated Start Time</label>
                                                    <div class="form-control bg-light" id="calculatedStartTime">--:--</div>
                                                    <small class="form-text text-muted">Calculated based on battery level and discharge rate</small>
                                                </div>
                                            </div>

                                            <div class="alert alert-info">
                                                <strong>ℹ️ How Battery Discharge Works:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li><strong>Check Time:</strong> At the check time (e.g., 8pm), the system checks if battery SOC is above the minimum level (e.g., 50%)</li>
                                                    <li><strong>Calculation:</strong> System accounts for house consumption during <u>waiting period</u> only:<br>
                                                        <code>1. Energy = Battery Size × (Current SOC - Target SOC)</code><br>
                                                        <code>2. Initial Time = Energy ÷ Discharge Rate</code><br>
                                                        <code>3. Initial Start = Stop Time - Initial Time</code><br>
                                                        <code>4. Waiting Period = Initial Start - Check Time</code><br>
                                                        <code>5. House Consumption = Waiting Period × House Load</code><br>
                                                        <code>6. Adjusted Energy = Energy - House Consumption</code><br>
                                                        <code>7. Final Hours = Adjusted Energy ÷ Discharge Rate</code><br>
                                                        <small class="d-block mt-1">Example (80% → 20%, Check 8pm, Stop 11:45pm):<br>
                                                        Energy = 10000 × 0.60 = 6000 Wh<br>
                                                        Initial = 6000 ÷ 2750 = 2.18h → Start ~9:34pm<br>
                                                        Waiting = 9:34pm - 8:00pm = 1.57h<br>
                                                        House = 1.57h × 350W = 550 Wh<br>
                                                        Adjusted = 6000 - 550 = 5450 Wh<br>
                                                        Final = 5450 ÷ 2750 = <strong>1.98 hours → Start ~9:47pm</strong></small>
                                                    </li>
                                                    <li><strong>Why?</strong> House drains battery while <em>waiting</em> (check to start). During <em>discharge</em>, house is powered from grid.</li>
                                                    <li><strong>Start Time:</strong> Discharge starts at: Stop Time - Final Hours</li>
                                                    <li><strong>Stops When:</strong> Either reaches discharge-to SOC level OR stop time - whichever comes first</li>
                                                    <li><strong>EV Priority:</strong> Discharge is blocked if EV is plugged in or charging</li>
                                                    <li><strong>Reset:</strong> System returns to normal settings at stop time every night</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-warning" id="saveDischargeBtn">
                                            <i class="bi bi-save"></i> Save Discharge Settings
                                        </button>
                                        <small class="text-muted ms-2">Click Save to persist your changes</small>
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

<!-- Sync Progress Modal -->
<div class="modal fade" id="syncProgressModal" tabindex="-1" aria-labelledby="syncProgressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncProgressModalLabel">
                    <span class="spinner-border spinner-border-sm me-2" id="syncSpinner"></span>
                    Syncing to Inverter
                </h5>
            </div>
            <div class="modal-body">
                <div id="syncProgressSteps">
                    <!-- Steps will be added dynamically here -->
                </div>
                <div id="syncProgressMessage" class="mt-3 alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="syncCloseBtn" data-bs-dismiss="modal" disabled>Close</button>
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
#syncProgressSteps {
    max-height: 500px;
    overflow-y: auto;
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
}
.sync-step {
    background-color: #fff;
    transition: all 0.3s ease;
    animation: slideIn 0.3s ease-out;
}
.sync-step:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.sync-step.updated {
    animation: pulse 0.5s ease-out;
}
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
}
.json-output {
    background-color: #282c34;
    color: #abb2bf;
    border-radius: 0.25rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
    font-size: 0.85rem;
    overflow-x: auto;
    max-height: 300px;
    overflow-y: auto;
}
.json-output code {
    color: #abb2bf;
    white-space: pre;
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

    // Refresh Inverter Values button
    const refreshBtn = document.getElementById('refreshInverterBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Refreshing...';

            fetch('{{ route("ev-charging.get-inverter-settings") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.settings) {
                    // Update all the current inverter value cells
                    updateInverterValueCell(1, data.settings);
                    updateInverterValueCell(2, data.settings);
                    updateInverterValueCell(3, data.settings);
                    updateInverterValueCell(4, data.settings);
                    updateInverterValueCell(5, data.settings);
                    updateInverterValueCell(6, data.settings);
                    
                    // Show success message
                    showToast('Success', 'Inverter values refreshed successfully', 'success');
                } else {
                    showToast('Error', data.message || 'Failed to refresh inverter values', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to refresh inverter values', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
        });
    }

    // Sync to Inverter Now button
    const syncBtn = document.getElementById('syncToInverterBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', function() {
            if (!confirm('This will immediately push your current settings to the inverter. Continue?')) {
                return;
            }

            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Syncing...';

            // Show the progress modal
            const progressModal = new bootstrap.Modal(document.getElementById('syncProgressModal'));
            progressModal.show();
            
            // Reset modal content
            document.getElementById('syncProgressSteps').innerHTML = '';
            document.getElementById('syncProgressMessage').style.display = 'none';
            document.getElementById('syncSpinner').style.display = 'inline-block';
            document.getElementById('syncCloseBtn').disabled = true;

            // Use Server-Sent Events for real-time updates
            const eventSource = new EventSource('{{ route("ev-charging.sync-now") }}?stream=true&_token={{ csrf_token() }}');
            let allSteps = [];
            let stepCounter = 0;
            
            eventSource.addEventListener('step', function(e) {
                const stepData = JSON.parse(e.data);
                
                // Create unique identifier based on step number and base message
                const baseMessage = stepData.message.split('(')[0].trim(); // Remove attempt info for matching
                const stepKey = `${stepData.step}-${baseMessage}`;
                
                // Find if this step already exists (for updates)
                const existingIndex = allSteps.findIndex(s => {
                    const sBaseMessage = s.message.split('(')[0].trim();
                    return s.step === stepData.step && sBaseMessage === baseMessage;
                });
                
                if (existingIndex >= 0) {
                    // Update existing step
                    allSteps[existingIndex] = {...allSteps[existingIndex], ...stepData, id: allSteps[existingIndex].id};
                } else {
                    // Add new step with unique ID
                    stepData.id = ++stepCounter;
                    allSteps.push(stepData);
                }
                
                // Display steps in real-time
                displaySyncStepsRealtime(allSteps);
            });
            
            eventSource.addEventListener('complete', function(e) {
                const result = JSON.parse(e.data);
                
                // Close the event stream
                eventSource.close();
                
                // Hide spinner
                document.getElementById('syncSpinner').style.display = 'none';
                
                // Show final message
                const messageDiv = document.getElementById('syncProgressMessage');
                messageDiv.style.display = 'block';
                
                if (result.success) {
                    messageDiv.className = 'mt-3 alert alert-success';
                    messageDiv.innerHTML = '<strong>✓ Success!</strong><br>' + result.message;
                    
                    if (result.partial) {
                        messageDiv.className = 'mt-3 alert alert-warning';
                        messageDiv.innerHTML = '<strong>⚠ Partial Success</strong><br>' + result.message;
                        if (result.verificationErrors && result.verificationErrors.length > 0) {
                            messageDiv.innerHTML += '<br><small>Mismatches: ' + result.verificationErrors.slice(0, 5).join(', ') + '</small>';
                        }
                    }
                    
                    // Update modal title
                    document.getElementById('syncProgressModalLabel').innerHTML = '✓ Sync Complete';
                    
                    // Refresh the inverter values after sync
                    setTimeout(() => {
                        document.getElementById('refreshInverterBtn')?.click();
                    }, 2000);
                } else {
                    messageDiv.className = 'mt-3 alert alert-danger';
                    messageDiv.innerHTML = '<strong>✗ Error</strong><br>' + (result.message || 'Failed to sync settings to inverter');
                    
                    // Update modal title
                    document.getElementById('syncProgressModalLabel').innerHTML = '✗ Sync Failed';
                }
                
                // Enable close button
                document.getElementById('syncCloseBtn').disabled = false;
                
                // Re-enable sync button
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            });
            
            eventSource.onerror = function(error) {
                console.error('EventSource error:', error);
                eventSource.close();
                
                // Hide spinner
                document.getElementById('syncSpinner').style.display = 'none';
                
                const messageDiv = document.getElementById('syncProgressMessage');
                messageDiv.style.display = 'block';
                messageDiv.className = 'mt-3 alert alert-danger';
                messageDiv.innerHTML = '<strong>✗ Error</strong><br>Connection error during sync. Please check the logs.';
                
                document.getElementById('syncProgressModalLabel').innerHTML = '✗ Sync Failed';
                document.getElementById('syncCloseBtn').disabled = false;
                
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            };
        });
    }
});

function updateInverterValueCell(slotNumber, settings) {
    const cells = document.querySelectorAll('td');
    cells.forEach(cell => {
        const content = cell.querySelector('small.text-muted');
        if (content && content.textContent.includes('Time:') && content.textContent.includes('Cap:')) {
            // Check if this is the right slot by counting rows
            const row = cell.closest('tr');
            const tbody = row.closest('tbody');
            const rowIndex = Array.from(tbody.children).indexOf(row);
            
            if (rowIndex === slotNumber - 1) {
                const timeKey = `sellTime${slotNumber}`;
                const capKey = `cap${slotNumber}`;
                const gridKey = `time${slotNumber}on`;
                
                const time = settings[timeKey] || 'N/A';
                const cap = settings[capKey] || 'N/A';
                const grid = (settings[gridKey] === true || settings[gridKey] === 'true') ? 'ON' : 'OFF';
                
                content.innerHTML = `Time: ${time}<br>Cap: ${cap}%<br>Grid: ${grid}`;
            }
        }
    });
}

function displaySyncStepsRealtime(steps) {
    const stepsContainer = document.getElementById('syncProgressSteps');
    
    steps.forEach((step) => {
        const stepId = `step-${step.id}`;
        let stepDiv = document.getElementById(stepId);
        
        if (!stepDiv) {
            // Create new step element
            stepDiv = document.createElement('div');
            stepDiv.id = stepId;
            stepDiv.className = 'sync-step mb-3 p-3 border rounded';
            stepsContainer.appendChild(stepDiv);
        } else {
            // Add pulse animation for updates
            stepDiv.classList.add('updated');
            setTimeout(() => stepDiv.classList.remove('updated'), 500);
        }
        
        let statusIcon = '';
        let statusClass = '';
        
        switch(step.status) {
            case 'success':
                statusIcon = '<i class="bi bi-check-circle-fill text-success"></i>';
                statusClass = 'border-success';
                break;
            case 'error':
                statusIcon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                statusClass = 'border-danger';
                break;
            case 'warning':
                statusIcon = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                statusClass = 'border-warning';
                break;
            case 'in_progress':
                statusIcon = '<span class="spinner-border spinner-border-sm text-primary"></span>';
                statusClass = 'border-primary';
                break;
            default:
                statusIcon = '<i class="bi bi-info-circle-fill text-info"></i>';
                statusClass = 'border-info';
        }
        
        // Update border class
        stepDiv.className = 'sync-step mb-3 p-3 border rounded ' + statusClass;
        
        const uniqueId = `collapse-${stepId}`;
        
        let stepHTML = `
            <div class="d-flex align-items-start">
                <div class="me-3 fs-5">${statusIcon}</div>
                <div class="flex-grow-1">
                    <strong>Step ${step.step}:</strong> ${step.message}
        `;
        
        if (step.details) {
            stepHTML += `<br><small class="text-muted">${step.details}</small>`;
        }
        
        // Add request/response toggle button if data exists
        if (step.request || step.response || step.comparison || step.verificationErrors) {
            stepHTML += `
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#${uniqueId}" aria-expanded="false">
                        <i class="bi bi-code-square"></i> Show JSON Details
                    </button>
                </div>
                <div class="collapse mt-2" id="${uniqueId}">
            `;
            
            if (step.request) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-primary">📤 Request:</strong>
                        <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.request, null, 2)}</code></pre>
                    </div>
                `;
            }
            
            if (step.response) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-success">📥 Response:</strong>
                        <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.response, null, 2)}</code></pre>
                    </div>
                `;
            }
            
            if (step.comparison) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-info">🔍 Comparison:</strong>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Expected Values:</small>
                                <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.comparison.expected, null, 2)}</code></pre>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Actual Values:</small>
                                <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.comparison.actual, null, 2)}</code></pre>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            if (step.verificationErrors && step.verificationErrors.length > 0) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-danger">❌ Verification Errors:</strong>
                        <ul class="mt-1 mb-0">
                            ${step.verificationErrors.map(err => `<li><small>${err}</small></li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            stepHTML += `</div>`;
        }
        
        stepHTML += `
                </div>
            </div>
        `;
        
        stepDiv.innerHTML = stepHTML;
    });
    
    // Scroll to bottom of steps
    stepsContainer.scrollTop = stepsContainer.scrollHeight;
}

function displaySyncSteps(steps) {
    const stepsContainer = document.getElementById('syncProgressSteps');
    
    // Track existing steps for animation
    const existingSteps = {};
    stepsContainer.querySelectorAll('.sync-step').forEach(div => {
        const stepId = div.getAttribute('data-step-id');
        if (stepId) existingSteps[stepId] = div;
    });
    
    stepsContainer.innerHTML = '';
    
    steps.forEach((step, index) => {
        const stepId = `${step.step}-${step.message}`;
        const isUpdate = existingSteps.hasOwnProperty(stepId);
        
        const stepDiv = document.createElement('div');
        stepDiv.className = 'sync-step mb-3 p-3 border rounded';
        stepDiv.setAttribute('data-step-id', stepId);
        
        // Add updated class if this is an update
        if (isUpdate) {
            stepDiv.classList.add('updated');
        }
        
        let statusIcon = '';
        let statusClass = '';
        
        switch(step.status) {
            case 'success':
                statusIcon = '<i class="bi bi-check-circle-fill text-success"></i>';
                statusClass = 'border-success';
                break;
            case 'error':
                statusIcon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                statusClass = 'border-danger';
                break;
            case 'warning':
                statusIcon = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                statusClass = 'border-warning';
                break;
            case 'in_progress':
                statusIcon = '<span class="spinner-border spinner-border-sm text-primary"></span>';
                statusClass = 'border-primary';
                break;
            default:
                statusIcon = '<i class="bi bi-info-circle-fill text-info"></i>';
                statusClass = 'border-info';
        }
        
        stepDiv.className += ' ' + statusClass;
        
        const uniqueId = `step-${index}-${Date.now()}`;
        
        let stepHTML = `
            <div class="d-flex align-items-start">
                <div class="me-3 fs-5">${statusIcon}</div>
                <div class="flex-grow-1">
                    <strong>Step ${step.step}:</strong> ${step.message}
        `;
        
        if (step.details) {
            stepHTML += `<br><small class="text-muted">${step.details}</small>`;
        }
        
        // Add request/response toggle button if data exists
        if (step.request || step.response || step.comparison || step.verificationErrors) {
            stepHTML += `
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#${uniqueId}" aria-expanded="false">
                        <i class="bi bi-code-square"></i> Show JSON Details
                    </button>
                </div>
                <div class="collapse mt-2" id="${uniqueId}">
            `;
            
            if (step.request) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-primary">📤 Request:</strong>
                        <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.request, null, 2)}</code></pre>
                    </div>
                `;
            }
            
            if (step.response) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-success">📥 Response:</strong>
                        <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.response, null, 2)}</code></pre>
                    </div>
                `;
            }
            
            if (step.comparison) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-info">🔍 Comparison:</strong>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Expected Values:</small>
                                <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.comparison.expected, null, 2)}</code></pre>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Actual Values:</small>
                                <pre class="json-output p-2 mt-1"><code>${JSON.stringify(step.comparison.actual, null, 2)}</code></pre>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            if (step.verificationErrors && step.verificationErrors.length > 0) {
                stepHTML += `
                    <div class="mb-2">
                        <strong class="text-danger">❌ Verification Errors:</strong>
                        <ul class="mt-1 mb-0">
                            ${step.verificationErrors.map(err => `<li><small>${err}</small></li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            stepHTML += `</div>`;
        }
        
        stepHTML += `
                </div>
            </div>
        `;
        
        stepDiv.innerHTML = stepHTML;
        stepsContainer.appendChild(stepDiv);
    });
    
    // Scroll to bottom of steps
    stepsContainer.scrollTop = stepsContainer.scrollHeight;
}

function showToast(title, message, type = 'info') {
    // Simple alert fallback - you can enhance this with Bootstrap toasts if needed
    const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
    alert(`${icon} ${title}\n\n${message}`);
}

// Battery Discharge Settings UI Handlers
document.addEventListener('DOMContentLoaded', function() {
    // Toggle discharge settings panel
    const dischargeEnabledCheckbox = document.getElementById('dischargeEnabled');
    const dischargeSettingsPanel = document.getElementById('dischargeSettingsPanel');
    
    if (dischargeEnabledCheckbox) {
        dischargeEnabledCheckbox.addEventListener('change', function() {
            if (this.checked) {
                dischargeSettingsPanel.style.display = 'block';
            } else {
                dischargeSettingsPanel.style.display = 'none';
            }
        });
    }
    
    // Calculate discharge start time (accounting for house load during waiting period)
    function calculateDischargeStartTime() {
        const batterySize = parseFloat(document.getElementById('batterySizeWh')?.value || 10000);
        const dischargeRate = parseFloat(document.getElementById('dischargeRateW')?.value || 2750);
        const houseLoad = parseFloat(document.getElementById('houseLoadW')?.value || 350);
        const dischargeToSoc = parseFloat(document.getElementById('dischargeToSoc')?.value || 20);
        const checkTime = document.getElementById('dischargeCheckTime')?.value || '20:00';
        const stopTime = document.getElementById('dischargeStopTime')?.value || '23:45';
        
        // Assume current SOC is 80% for demonstration
        const currentSoc = 80;
        
        if (dischargeRate <= 0) {
            const calculatedStartTimeElement = document.getElementById('calculatedStartTime');
            if (calculatedStartTimeElement) {
                calculatedStartTimeElement.textContent = 'Error: Invalid discharge rate';
                calculatedStartTimeElement.className = 'form-control bg-danger text-white';
            }
            return;
        }
        
        // Step 1: Calculate total energy available (Wh)
        const energyAvailable = batterySize * ((currentSoc - dischargeToSoc) / 100);
        
        // Step 2: Calculate initial discharge time (hours)
        const initialDischargeTime = energyAvailable / dischargeRate;
        
        // Step 3: Calculate initial start time (Stop Time - Initial discharge time)
        const [stopHours, stopMinutes] = stopTime.split(':').map(Number);
        const stopTimeDate = new Date();
        stopTimeDate.setHours(stopHours, stopMinutes, 0, 0);
        
        const initialStartTime = new Date(stopTimeDate.getTime() - (initialDischargeTime * 60 * 60 * 1000));
        
        // Step 4: Calculate waiting period (from check time to start time)
        const [checkHours, checkMinutes] = checkTime.split(':').map(Number);
        const checkTimeDate = new Date();
        checkTimeDate.setHours(checkHours, checkMinutes, 0, 0);
        
        let waitingPeriodHours = (initialStartTime - checkTimeDate) / (1000 * 60 * 60);
        
        // If start time is before check time, no waiting period
        if (waitingPeriodHours < 0) {
            waitingPeriodHours = 0;
        }
        
        // Step 5: Calculate house consumption during waiting period (Wh)
        const houseConsumption = waitingPeriodHours * houseLoad;
        
        // Step 6: Calculate adjusted energy (Wh)
        const adjustedEnergy = energyAvailable - houseConsumption;
        
        if (adjustedEnergy <= 0) {
            const calculatedStartTimeElement = document.getElementById('calculatedStartTime');
            if (calculatedStartTimeElement) {
                calculatedStartTimeElement.textContent = 'Error: House consumption during waiting exceeds energy';
                calculatedStartTimeElement.className = 'form-control bg-danger text-white';
            }
            return;
        }
        
        // Step 7: Calculate final discharge hours
        const finalDischargeHours = adjustedEnergy / dischargeRate;
        
        // Calculate final start time
        const finalStartTime = new Date(stopTimeDate.getTime() - (finalDischargeHours * 60 * 60 * 1000));
        const startTimeFormatted = finalStartTime.toTimeString().slice(0, 5);
        
        // Update display with detailed info
        const calculatedStartTimeElement = document.getElementById('calculatedStartTime');
        if (calculatedStartTimeElement) {
            const energyToGridKwh = (adjustedEnergy / 1000).toFixed(2);
            const waitingFormatted = waitingPeriodHours.toFixed(2);
            calculatedStartTimeElement.textContent = startTimeFormatted + ' (' + finalDischargeHours.toFixed(2) + ' hours, ' + energyToGridKwh + ' kWh to grid, ' + waitingFormatted + 'h wait)';
            calculatedStartTimeElement.className = 'form-control bg-light';
        }
    }
    
    // Add event listeners to recalculate when inputs change
    const batteryInputs = [
        'batterySizeWh',
        'dischargeRateW',
        'houseLoadW',
        'dischargeToSoc',
        'dischargeCheckTime',
        'dischargeStopTime'
    ];
    
    batteryInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', calculateDischargeStartTime);
            input.addEventListener('change', calculateDischargeStartTime);
        }
    });
    
    // Initial calculation
    calculateDischargeStartTime();
});
</script>
@endsection 