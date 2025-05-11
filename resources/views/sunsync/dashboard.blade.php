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
                                            <!-- Energy Flow Diagram -->
                                            <div class="energy-flow-container">
                                                <!-- SVG Overlay for Flow Animations -->
                                                <svg width="100%" height="100%" style="position: absolute; top: -30px; left: 0; z-index: 2;">
                                                    <!-- Solar to Inverter path -->
                                                    <path id="path-solar-to-inverter" stroke="#ffc354" d="M145,350 145,120 345,120" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.4s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-solar-to-inverter"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    <!-- Grid to Inverter path -->
                                                    <!--<path id="path-grid-to-inverter" stroke="#ffc354" d="M575,350 575,120 345,120" class="path-line"></path>-->
                                                    <path id="path-grid-to-inverter" stroke="#ffc354" d="M345,120 575,120 575,350" class="path-line"></path>
                                                   
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.6s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-grid-to-inverter"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    <!-- Inverter to Battery path -->
                                                    <path id="path-inverter-to-battery" stroke="#ffc354" d="M245,350 345,120" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.2s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-inverter-to-battery"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    <!-- Inverter to UPS path -->
                                                    <path id="path-inverter-to-ups" stroke="#ffc354" d="M345,120 345,350" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.0s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-inverter-to-ups"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    <!-- Inverter to Smart path -->
                                                    <path id="path-inverter-to-smart" stroke="#ffc354" d="M345,120 425,350" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-inverter-to-smart"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    <!-- Inverter to Home path -->
                                                    <path id="path-inverter-to-home" stroke="#ffc354" d="M345,120 495,350" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-inverter-to-home"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    
                                                    @if(isset($inverterFlowInfo['pv']) && is_array($inverterFlowInfo['pv']) && count($inverterFlowInfo['pv']) > 1)
                                                    <!-- Second Solar Panel path -->
                                                    <path id="path-solar2-to-inverter" stroke="#ffc354" d="M45,350 45,120 345,120" class="path-line"></path>
                                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                                        <animateMotion dur="3.8s" rotate="auto" repeatCount="indefinite">
                                                            <mpath xlink:href="#path-solar2-to-inverter"></mpath>
                                                        </animateMotion>
                                                    </image>
                                                    @endif
                                                </svg>
                                                
                                                <!-- Inverter Node (Top Center) -->
                                                <div class="inverter-node">
                                                    <div class="energy-node-label">Inverter</div>
                                                    <img src="{{ asset('images/icons/inverter.svg') }}" alt="Inverter">
                                                </div>
                                                
                                                <!-- Components Row at Bottom -->
                                                <!-- PV1 Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 45px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/solar-panel.svg') }}" alt="Solar Panel">
                                                    <div class="energy-node-label">PV1</div>
                                                    <div id="solar-power-value" class="energy-value">{{ number_format(isset($inverterFlowInfo['pv']) && is_array($inverterFlowInfo['pv']) && isset($inverterFlowInfo['pv'][0]['power']) ? $inverterFlowInfo['pv'][0]['power'] : 0, 0) }}W</div>
                                                </div>
                                                @if(isset($inverterFlowInfo['pv']) && is_array($inverterFlowInfo['pv']) && count($inverterFlowInfo['pv']) > 1)
                                                <!-- PV2 Node (Only if available) -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 145px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/solar-panel.svg') }}" alt="Solar Panel 2">
                                                    <div class="energy-node-label">PV2</div>
                                                    <div id="solar2-power-value" class="energy-value">{{ number_format($inverterFlowInfo['pv'][1]['power'] ?? 0, 0) }}W</div>
                                                </div>
                                                @endif
                                                
                                                <!-- Battery Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 10px; left: 245px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/battery.svg') }}" alt="Battery">
                                                    <div class="energy-node-label">Battery</div>
                                                    <div id="battery-power-value" class="energy-value">{{ number_format($inverterFlowInfo['battPower'] ?? 0, 0) }}W</div>
                                                    <div class="energy-percentage">{{ number_format($inverterFlowInfo['soc'] ?? 0, 0) }}%</div>
                                                </div>
                                               
                                                <!-- UPS Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 345px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/ups.svg') }}" alt="UPS">
                                                    <div class="energy-node-label">UPS</div>
                                                    <div id="ups-load-value" class="energy-value">{{ number_format($inverterFlowInfo['upsLoadPower'] ?? 0, 0) }}W</div>
                                                </div>
                                                
                                                <!-- Smart Load Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 425px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/smart-device.svg') }}" alt="Smart Load">
                                                    <div class="energy-node-label">Water Heater</div>
                                                    <div id="smart-load-value" class="energy-value">{{ number_format($inverterFlowInfo['smartLoadPower'] ?? 0, 0) }}W</div>
                                                </div>
                                                
                                                <!-- Home Load Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 495px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/house.svg') }}" alt="Home Load">
                                                    <div class="energy-node-label">Home</div>
                                                    <div id="home-load-value" class="energy-value">{{ number_format($inverterFlowInfo['homeLoadPower'] ?? 0, 0) }}W</div>
                                                </div>
                                                
                                                <!-- Grid Node -->
                                                <div class="energy-node" style="position: absolute; bottom: 39px; left: 575px; transform: translateX(-50%); z-index: 3;">
                                                    <img src="{{ asset('images/icons/power-grid.svg') }}" alt="Power Grid">
                                                    <div class="energy-node-label">Grid</div>
                                                    <div id="grid-power-value" class="energy-value">{{ number_format($inverterFlowInfo['gridOrMeterPower'] ?? 0, 0) }}W</div>
                                                </div>
                                                
                                                
                                            </div>
                                            
                                            <!-- Hidden flags for flow direction -->
                                            <input type="hidden" id="pvTo-flag" value="{{ $inverterFlowInfo['pvTo'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toLoad-flag" value="{{ $inverterFlowInfo['toLoad'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toSmartLoad-flag" value="{{ $inverterFlowInfo['toSmartLoad'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toUpsLoad-flag" value="{{ $inverterFlowInfo['toUpsLoad'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toHomeLoad-flag" value="{{ $inverterFlowInfo['toHomeLoad'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toGrid-flag" value="{{ $inverterFlowInfo['toGrid'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="toBat-flag" value="{{ $inverterFlowInfo['toBat'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="gridTo-flag" value="{{ $inverterFlowInfo['gridTo'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="genTo-flag" value="{{ $inverterFlowInfo['genTo'] ?? false ? 'true' : 'false' }}">
                                            <input type="hidden" id="existsGrid-flag" value="{{ $inverterFlowInfo['existsGrid'] ?? false ? 'true' : 'false' }}">
                                            
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