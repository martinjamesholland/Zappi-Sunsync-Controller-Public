@extends('layouts.app')

@section('title', 'SunSync Dashboard - Solar Battery EV Charger')

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
</style>
<link href="{{ asset('css/energy-flow.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">SunSync Dashboard</h5>
                    <div>
                        <div class="form-check form-switch d-inline-block me-3">
                            <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                            <label class="form-check-label" for="auto-refresh">Auto-refresh (30s)</label>
                        </div>
                        <button class="btn btn-primary btn-sm" id="refresh-btn">Refresh Now</button>
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
                        <!-- Table View Tab -->
                        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">
                            <!-- Plant Information -->
                            <div class="row mb-4 mt-3">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Plant Information</h4>
                                        </div>
                                        <div class="card-body">
                                            @if(isset($plantInfo['thumbUrl']) && !empty($plantInfo['thumbUrl']))
                                            <div class="row mb-4">
                                                <div class="col-md-12 text-center">
                                                    <div class="plant-image-container" style="max-width: 600px; margin: 0 auto;">
                                                        <img src="{{ $plantInfo['thumbUrl'] }}" alt="Plant Image" class="img-fluid rounded shadow" style="max-height: 300px; width: 100%; object-fit: cover;">
                                                        <p class="text-muted mt-2">Plant image from SunSync</p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>Plant ID</td>
                                                            <td class="table-status-value">
                                                                @php
                                                                    $id = $plantInfo['id'];
                                                                    $strId = (string)$id;
                                                                    if (strlen($strId) > 2) {
                                                                        $maskedId = substr($strId, 0, 1) . str_repeat('*', strlen($strId) - 2) . substr($strId, -1);
                                                                        echo $maskedId;
                                                                    } else {
                                                                        echo $id;
                                                                    }
                                                                @endphp
                                                            </td>
                                                            <td>Plant Name</td>
                                                            <td class="table-status-value">{{ $plantInfo['name'] }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Status</td>
                                                            <td class="table-status-value">{{ $plantInfo['status'] == 1 ? 'Active' : 'Inactive' }}</td>
                                                            <td>Address</td>
                                                            <td class="table-status-value">********</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Current Power</td>
                                                            <td class="table-status-value">{{ number_format($plantInfo['pac'], 2) }} W</td>
                                                            <td>Efficiency</td>
                                                            <td class="table-status-value">{{ number_format($plantInfo['efficiency'] * 100, 2) }}%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Today's Energy</td>
                                                            <td class="table-status-value">{{ number_format($plantInfo['etoday'], 2) }} kWh</td>
                                                            <td>Total Energy</td>
                                                            <td class="table-status-value">{{ number_format($plantInfo['etotal'], 2) }} kWh</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Last Update</td>
                                                            <td class="table-status-value">{{ \Carbon\Carbon::parse($plantInfo['updateAt'])->format('Y-m-d H:i:s') }}</td>
                                                            <td>Creation Date</td>
                                                            <td class="table-status-value">{{ \Carbon\Carbon::parse($plantInfo['createAt'])->format('Y-m-d H:i:s') }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inverter Information -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Inverter Information</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>Inverter ID</td>
                                                            <td class="table-status-value">{{ $inverterInfo['id'] }}</td>
                                                            <td>Serial Number</td>
                                                            <td class="table-status-value">{{ $inverterInfo['sn'] }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Alias</td>
                                                            <td class="table-status-value">{{ $inverterInfo['alias'] ?? 'N/A' }}</td>
                                                            <td>GSN</td>
                                                            <td class="table-status-value">{{ $inverterInfo['gsn'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Status</td>
                                                            <td class="table-status-value">{{ $inverterInfo['status'] == 1 ? 'Active' : 'Inactive' }}</td>
                                                            <td>Type</td>
                                                            <td class="table-status-value">{{ $inverterInfo['type'] }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Communication Type</td>
                                                            <td class="table-status-value">{{ $inverterInfo['commTypeName'] ?? 'N/A' }}</td>
                                                            <td>Model</td>
                                                            <td class="table-status-value">{{ $inverterInfo['model'] ?: 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Current Power</td>
                                                            <td class="table-status-value">{{ number_format($inverterInfo['pac'], 2) }} W</td>
                                                            <td>Today's Energy</td>
                                                            <td class="table-status-value">{{ number_format($inverterInfo['etoday'], 2) }} kWh</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Energy</td>
                                                            <td class="table-status-value">{{ number_format($inverterInfo['etotal'], 2) }} kWh</td>
                                                            <td>Last Update</td>
                                                            <td class="table-status-value">{{ \Carbon\Carbon::parse($inverterInfo['updateAt'])->format('Y-m-d H:i:s') }}</td>
                                                        </tr>
                                                        @if(isset($inverterInfo['version']))
                                                        <tr>
                                                            <td>Master Version</td>
                                                            <td class="table-status-value">{{ $inverterInfo['version']['masterVer'] ?? 'N/A' }}</td>
                                                            <td>Software Version</td>
                                                            <td class="table-status-value">{{ $inverterInfo['version']['softVer'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Hardware Version</td>
                                                            <td class="table-status-value">{{ $inverterInfo['version']['hardVer'] ?? 'N/A' }}</td>
                                                            <td>HMI Version</td>
                                                            <td class="table-status-value">{{ $inverterInfo['version']['hmiVer'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>BMS Version</td>
                                                            <td class="table-status-value">{{ $inverterInfo['version']['bmsVer'] ?? 'N/A' }}</td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inverter Settings -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Inverter Settings</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <tbody>
                                                        <tr>
                                                            <td>Serial Number</td>
                                                            <td class="table-status-value">{{ $inverterSettings['sn'] }}</td>
                                                            <td>Safety Type</td>
                                                            <td class="table-status-value">{{ $inverterSettings['safetyType'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>System Work Mode</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['sysWorkMode']))
                                                                    @switch($inverterSettings['sysWorkMode'])
                                                                        @case('0')
                                                                            General Mode
                                                                            @break
                                                                        @case('1')
                                                                            Off-Grid Mode
                                                                            @break
                                                                        @case('2')
                                                                            Backup Mode
                                                                            @break
                                                                        @case('3')
                                                                            Economic Mode
                                                                            @break
                                                                        @case('4')
                                                                            Custom Mode
                                                                            @break
                                                                        @default
                                                                            {{ $inverterSettings['sysWorkMode'] }}
                                                                    @endswitch
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Battery Mode</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['battMode']))
                                                                    @switch($inverterSettings['battMode'])
                                                                        @case('-1')
                                                                            No Battery
                                                                            @break
                                                                        @case('0')
                                                                            User-defined
                                                                            @break
                                                                        @case('1')
                                                                            Lithium
                                                                            @break
                                                                        @case('2')
                                                                            Lead-Acid
                                                                            @break
                                                                        @default
                                                                            {{ $inverterSettings['battMode'] }}
                                                                    @endswitch
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Energy Mode</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['energyMode']))
                                                                    @switch($inverterSettings['energyMode'])
                                                                        @case('0')
                                                                            Self-consumption Mode
                                                                            @break
                                                                        @case('1')
                                                                            Feed-in Mode
                                                                            @break
                                                                        @default
                                                                            {{ $inverterSettings['energyMode'] }}
                                                                    @endswitch
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Zero Export Power</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['zeroExportPower']))
                                                                    {{ number_format($inverterSettings['zeroExportPower'], 2) }} W
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Solar Sell</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['solarSell']))
                                                                    {{ $inverterSettings['solarSell'] == 1 ? 'Enabled' : 'Disabled' }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Battery On</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['batteryOn']))
                                                                    {{ $inverterSettings['batteryOn'] == 1 ? 'Enabled' : 'Disabled' }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>PV Max Limit</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['pvMaxLimit']))
                                                                    {{ number_format($inverterSettings['pvMaxLimit'], 2) }} W
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Solar Max Sell Power</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['solarMaxSellPower']))
                                                                    {{ number_format($inverterSettings['solarMaxSellPower'], 2) }} W
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Charge Voltage</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['chargeVolt']))
                                                                    {{ number_format($inverterSettings['chargeVolt'], 2) }} V
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Float Voltage</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['floatVolt']))
                                                                    {{ number_format($inverterSettings['floatVolt'], 2) }} V
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Time 5 On</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['time5on']))
                                                                    {{ $inverterSettings['time5on'] ? 'Enabled' : 'Disabled' }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td>Sell Time 5</td>
                                                            <td class="table-status-value">{{ $inverterSettings['sellTime5'] ?? 'N/A' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Cap 5</td>
                                                            <td class="table-status-value">
                                                                @if(isset($inverterSettings['cap5']))
                                                                    {{ number_format($inverterSettings['cap5'], 0) }}%
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inverter Flow Information -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Inverter Flow Information</h4>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                // Use inverterFlowInfo for the flow diagram (same as home page)
                                                $sunSyncData = $inverterFlowInfo;
                                            @endphp
                                            
                                            <!-- Main Energy Flow Container (Same as Home Page) -->
                                            <div class="energy-flow-container" style="max-width: 700px; min-width: 300px; margin: 0 auto; min-height: 500px; position: relative;">
                                                <!-- SVG Layer: Contains all flow path animations -->
                                                <svg id="energy-flow-svg" width="100%" viewBox="0 0 700 800" preserveAspectRatio="xMidYMid meet" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); z-index: 1; pointer-events: none;">
                                                    <!-- Flow Path Definitions -->
                                                    <!-- PV1 to Inverter Path -->
                                                    <path id="path-pv1-to-inverter" stroke="{{ ($sunSyncData['pv'][0]['power'] ?? 0) > 0 ? '#e59866' : '#cccccc' }}" stroke-width="2" fill="none" class="path-line"
                                                          d="M 60 100 L 120 100 L 120 175 L 330 175">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if(($sunSyncData['pv'][0]['power'] ?? 0) > 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.4s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-pv1-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif
                                                    
                                                    <!-- PV2 to Inverter Path (Conditional) -->
                                                    <path id="path-pv2-to-inverter" stroke="{{ ($sunSyncData['pv'][1]['power'] ?? 0) > 0 ? '#e59866' : '#cccccc' }}" stroke-width="2" fill="none" class="path-line"
                                                          d="M 60 250 L 120 250 L 120 175 L 330 175">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if(($sunSyncData['pv'][1]['power'] ?? 0) > 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.8s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-pv2-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif

                                                    <!-- Battery to Inverter Path -->
                                                    @php
                                                        $battPower = $sunSyncData['battPower'] ?? 0;
                                                        $battPathColor = $battPower == 0 ? '#cccccc' : '#66bb6a';
                                                        $battPathD = '';
                                                        
                                                        if (isset($sunSyncData['batTo']) && $sunSyncData['batTo']) {
                                                            $battPathD = 'M 140 375 L 140 190 L 300 190 L 330 190';
                                                        } elseif (isset($sunSyncData['toBat']) && $sunSyncData['toBat']) {
                                                            $battPathD = 'M 330 190 L 300 190 L 140 190 L 140 375';
                                                        } else {
                                                            $battPathD = 'M 140 375 L 140 190 L 300 190 L 330 190';
                                                        }
                                                    @endphp
                                                    <path id="path-battery-to-inverter" 
                                                          stroke="{{ $battPathColor }}" 
                                                          stroke-width="2" 
                                                          fill="none" 
                                                          class="path-line"
                                                          d="{{ $battPathD }}">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if($battPower != 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.2s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-battery-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif

                                                    <!-- UPS to Inverter Path -->
                                                    @php
                                                        $upsPower = $sunSyncData['upsLoadPower'] ?? 0;
                                                        $upsPathColor = $upsPower != 0 ? '#7986cb' : '#cccccc';
                                                        $upsPathD = 'M 340 210 L 340 265 L 245 265 L 245 375'; 
                                                    @endphp
                                                    <path id="path-ups-to-inverter" stroke="{{ $upsPathColor }}" stroke-width="2" fill="none" class="path-line"
                                                          d="{{ $upsPathD }}">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if($upsPower != 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.0s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-ups-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif

                                                    <!-- Smart Load to Inverter Path -->
                                                    <path id="path-smart-to-inverter" stroke="{{ ($sunSyncData['smartLoadPower'] ?? 0) != 0 ? '#4db6ac' : '#cccccc' }}" stroke-width="2" fill="none" class="path-line"
                                                          d="M 350 210 L 350 265 350 355 L 350 355 L 350 375">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if(($sunSyncData['smartLoadPower'] ?? 0) > 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-smart-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif

                                                    <!-- Home Load to Inverter Path -->
                                                    <path id="path-home-to-inverter" stroke="{{ ($sunSyncData['homeLoadPower'] ?? 0) != 0 ? '#f06292' : '#cccccc' }}" stroke-width="2" fill="none" class="path-line"
                                                          d="M 360 210 L 360 265 L 455 265 L 455 375">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if(($sunSyncData['homeLoadPower'] ?? 0) > 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-home-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif

                                                    <!-- Grid to Inverter Path -->
                                                    @php
                                                        $gridPower = $sunSyncData['gridOrMeterPower'] ?? 0;
                                                    @endphp
                                                    <path id="path-grid-to-inverter" 
                                                          stroke="{{ $gridPower != 0 ? '#90caf9' : '#cccccc' }}" 
                                                          stroke-width="2" 
                                                          fill="none" 
                                                          class="path-line"
                                                          d="{{ $gridPower < 0 ? 'M 375 175 L 500 175 L 500 85 L 550 85' : 'M 550 85 L 500 85 L 500 175 L 375 175' }}">
                                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                                    </path>
                                                    @if($gridPower != 0)
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.6s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-grid-to-inverter"/>
                                                        </animateMotion>
                                                    </image>
                                                    @endif
                                                </svg>

                                                <!-- Node Definitions -->
                                                <!-- Central Inverter Node -->
                                                <div id="inverter-node" class="energy-node inverter-node" style="position: absolute; top: 125px; left: 50%; transform: translateX(-50%); z-index: 10; width: 150px; text-align: center;">
                                                    <div class="energy-node-label">Inverter</div>
                                                    <img src="{{ asset('images/icons/inverter.png') }}" alt="Inverter" style="width: 70px; height: 70px;">
                                                </div>

                                                <!-- Left Side Nodes -->
                                                <!-- PV1 Node -->
                                                <div id="pv1-node" class="energy-node" style="position: absolute; top: 50px; left: 5%; transform: translateX(-50%); z-index: 3; width: 70px; text-align: center;">
                                                    <img src="{{ asset('images/icons/solar-panel.png') }}" alt="Solar Panel" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">PV1</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['pv'][0]['power'] ?? 0, 0) }}W</div>
                                                </div>

                                                <!-- PV2 Node (Conditional) -->
                                                @if(isset($sunSyncData['pv']) && is_array($sunSyncData['pv']) && count($sunSyncData['pv']) > 1)
                                                <div id="pv2-node" class="energy-node" style="position: absolute; top: 200px; left: 5%; transform: translateX(-50%); z-index: 3; width: 70px; text-align: center;">
                                                    <img src="{{ asset('images/icons/solar-panel.png') }}" alt="Solar Panel 2" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">PV2</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['pv'][1]['power'] ?? 0, 0) }}W</div>
                                                </div>
                                                @endif

                                                <!-- PV Combined Power Node -->
                                                <div id="pv-combined-power-node" class="energy-node inverter-node" style="position: absolute; top: 150px; left: 25%; transform: translateX(-50%); z-index: 10; width: 90px; text-align: center;">
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format(($sunSyncData['pv'][0]['power'] ?? 0) + ($sunSyncData['pv'][1]['power'] ?? 0), 0) }}W</div>
                                                </div>

                                                <!-- Bottom Row Nodes -->
                                                <!-- Battery Node -->
                                                <div id="battery-node" class="energy-node" style="position: absolute; top: 370px; left: 20%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                                    <img src="{{ asset('images/icons/battery.png') }}" alt="Battery" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">Battery</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['battPower'] ?? 0, 0) }}W</div>
                                                    <div class="energy-percentage" style="top: -83px;position: relative;left: -40px;{{ ($sunSyncData['soc'] ?? 0) == 100 ? 'color: #66bb6a; font-weight: bold;' : (($sunSyncData['soc'] ?? 0) <= 20 ? 'color: #f44336; font-weight: bold;' : '') }}">{{ number_format($sunSyncData['soc'] ?? 0, 0) }}%</div>
                                                </div>

                                                <!-- UPS Node -->
                                                <div id="ups-node" class="energy-node" style="position: absolute; top: 370px; left: 35%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                                    <img src="{{ asset('images/icons/ups.png') }}" alt="UPS Load" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">UPS</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['upsLoadPower'] ?? 0, 0) }}W</div>
                                                </div>

                                                <!-- Smart Load Node -->
                                                <div id="smart-load-node" class="energy-node" style="position: absolute; top: 370px; left: 50%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                                    <img src="{{ asset('images/icons/smart-device.png') }}" alt="Smart Load" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label" style="font-size: 0.6em;">Water Heater</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['smartLoadPower'] ?? 0, 0) }}W</div>
                                                </div>

                                                <!-- Home Load Node -->
                                                <div id="home-load-node" class="energy-node" style="position: absolute; top: 370px; left: 65%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                                    <img src="{{ asset('images/icons/house.png') }}" alt="Home Load" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">Home</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['homeLoadPower'] ?? 0, 0) }}W</div>
                                                </div>

                                                <!-- Combined Load Node -->
                                                <div id="combined-load" class="energy-node inverter-node" style="position: absolute; top: 210px; left: 50%; transform: translateX(-50%); z-index: 10; width: 70px; text-align: center;">
                                                    <div id="combined-load-value" class="energy-value" style="background-color:#fcfcfc; padding: 2px; border-radius: 5px; color:rgb(251 180 40);">
                                                        {{ number_format(($sunSyncData['upsLoadPower'] ?? 0) + ($sunSyncData['smartLoadPower'] ?? 0) + ($sunSyncData['homeLoadPower'] ?? 0), 0) }}W
                                                    </div>
                                                </div>

                                                <!-- Right Side Nodes -->
                                                <!-- Grid Node -->
                                                <div id="grid-node" class="energy-node" style="position: absolute; top: 50px; left: 82.14%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                                    <img src="{{ asset('images/icons/power-grid.png') }}" alt="Power Grid" style="width: 50px; height: 50px;">
                                                    <div class="energy-node-label">Grid</div>
                                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format(abs($sunSyncData['gridOrMeterPower'] ?? 0), 0) }}W</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Simple Table for Additional Values -->
                                            <div class="table-responsive mt-4">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Parameter</th>
                                                            <th>Value</th>
                                                            <th>Parameter</th>
                                                            <th>Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Grid Status</td>
                                                            <td>{{ ($inverterFlowInfo['existsGrid'] ?? false) ? 'Connected' : 'Disconnected' }}</td>
                                                            <td>Total PV Power</td>
                                                            <td>{{ 
                                                                number_format(
                                                                    (isset($inverterFlowInfo['pv']) && is_array($inverterFlowInfo['pv']) ? 
                                                                        (isset($inverterFlowInfo['pv'][0]['power']) ? $inverterFlowInfo['pv'][0]['power'] : 0) + 
                                                                        (isset($inverterFlowInfo['pv'][1]['power']) ? $inverterFlowInfo['pv'][1]['power'] : 0) 
                                                                    : 0), 
                                                                2) 
                                                            }} W</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Load Power</td>
                                                            <td>{{ number_format($inverterFlowInfo['loadOrEpsPower'] ?? 0, 2) }} W</td>
                                                            <td>Battery Power Direction</td>
                                                            <td>{{ ($inverterFlowInfo['battPower'] ?? 0) > 0 ? 'Charging' : 'Discharging' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Generator Power</td>
                                                            <td>{{ number_format($inverterFlowInfo['genPower'] ?? 0, 2) }} W</td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- JSON View Tab -->
                        <div class="tab-pane fade" id="json" role="tabpanel" aria-labelledby="json-tab">
                            <!-- API Requests Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h4>API Requests</h4>
                                        </div>
                                        <div class="card-body">
                                            @foreach($apiRequests as $name => $request)
                                            <div class="mb-4">
                                                <h5>{{ ucfirst(str_replace('_', ' ', $name)) }}</h5>
                                                <div class="mb-2">
                                                    <strong>URL:</strong> {{ $request['url'] }}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Method:</strong> {{ $request['method'] }}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Headers:</strong>
                                                    <pre><code>{{ json_encode($request['headers'], JSON_PRETTY_PRINT) }}</code></pre>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Request Body:</strong>
                                                    <pre><code>{{ json_encode($request['body'], JSON_PRETTY_PRINT) }}</code></pre>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Response:</strong>
                                                    <pre><code>{{ json_encode($request['response'], JSON_PRETTY_PRINT) }}</code></pre>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                        </div>
                        
                        <!-- Field Mapping Tab -->
                        <div class="tab-pane fade" id="mapping" role="tabpanel" aria-labelledby="mapping-tab">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h4>Plant Information Fields</h4>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>id</td>
                                                        <td>Unique identifier for the plant</td>
                                                    </tr>
                                                    <tr>
                                                        <td>name</td>
                                                        <td>Plant name</td>
                                                    </tr>
                                                    <tr>
                                                        <td>status</td>
                                                        <td>Current plant status (1 = Active)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>address</td>
                                                        <td>Physical location of the plant</td>
                                                    </tr>
                                                    <tr>
                                                        <td>pac</td>
                                                        <td>Current power (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>efficiency</td>
                                                        <td>System efficiency as a decimal</td>
                                                    </tr>
                                                    <tr>
                                                        <td>etoday</td>
                                                        <td>Energy generated today (kWh)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>etotal</td>
                                                        <td>Total energy generated (kWh)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h4>Inverter Settings Fields</h4>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>sysWorkMode</td>
                                                        <td>System working mode (0-4)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>battMode</td>
                                                        <td>Battery operating mode</td>
                                                    </tr>
                                                    <tr>
                                                        <td>solarSell</td>
                                                        <td>Solar sell enabled (1) or disabled (0)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>batteryOn</td>
                                                        <td>Battery enabled (1) or disabled (0)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>pvMaxLimit</td>
                                                        <td>Maximum PV input power (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>solarMaxSellPower</td>
                                                        <td>Maximum solar export power (W)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h4>Inverter Flow Information Fields</h4>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>pvPower</td>
                                                        <td>Total solar power (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>battPower</td>
                                                        <td>Battery power (W), positive when charging</td>
                                                    </tr>
                                                    <tr>
                                                        <td>gridOrMeterPower</td>
                                                        <td>Grid power (W), positive when importing</td>
                                                    </tr>
                                                    <tr>
                                                        <td>loadOrEpsPower</td>
                                                        <td>Total load power (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>smartLoadPower</td>
                                                        <td>Smart load power consumption (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>upsLoadPower</td>
                                                        <td>UPS load power consumption (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>homeLoadPower</td>
                                                        <td>Home load power consumption (W)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>soc</td>
                                                        <td>Battery state of charge (%)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>existsGrid</td>
                                                        <td>Grid connection status (true/false)</td>
                                                    </tr>
                                                    <tr>
                                                        <td>pv</td>
                                                        <td>Array of individual PV strings with power values</td>
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
    fetch('{{ url("/sunsync/dashboard") }}', {
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
    const autoRefreshCheckbox = document.getElementById('auto-refresh');
    const refreshBtn = document.getElementById('refresh-btn');
    
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
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshData);
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
});
</script>
<script src="{{ asset('js/energy-flow.js') }}"></script>
@endsection 