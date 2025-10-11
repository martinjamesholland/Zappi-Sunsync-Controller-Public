@extends('layouts.app')

@section('title', 'Home - Solar Battery EV Charger')

@section('content')

@php
    if($zappiData['zappi'][0]['grd'] < 25 & $zappiData['zappi'][0]['grd'] > -25){
        $zappiData['zappi'][0]['grd'] = 0;
    }
    if($sunSyncData['gridOrMeterPower'] < 25 & $sunSyncData['gridOrMeterPower'] > -25){
        $sunSyncData['gridOrMeterPower'] = 0;
    }
@endphp
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="fs-4">Welcome to Solar, Battery & EV Charger Collaboration</h1>
                </div>
                
                <!-- Combined Energy Flow Card -->
                <div id="combined-energy-flow" class="col-12 mb-4">
                    <div class="card">
                        <!-- Main Header Section -->
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2>Combined Energy Flow</h2>
                            <div class="form-check form-switch ms-2" style="margin-bottom: 0;">
                                <input class="form-check-input" type="checkbox" id="ssValueToggle">
                                <label class="form-check-label" for="ssValueToggle" style="user-select:none;">Show All SunSync values</label>
                            </div>
                        </div>
                        <!-- Data Flow Card -->
                        <div class="card-body">
                            <!-- Description Section -->
                            <div class="mb-3">
                                <p class="text-muted mb-2">
                                    This diagram combines SunSync inverter and Zappi charger information into a single energy flow visualization.
                                </p>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="energy-percentage d-inline-flex align-items-center px-2 py-1 rounded" style="font-size: 0.95em; background: #e3fcec; color: #008939;">
                                        <i class="bi bi-ev-station me-1"></i>
                                        <strong>Zappi Data</strong>
                                        <span class="js-relative-time ms-1"
                                            data-timestamp="{{ isset($zappiData['zappi'][0]['gen']) ? \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', $zappiData['zappi'][0]['dat'] . ' ' . $zappiData['zappi'][0]['tim'], 'UTC')->timezone('Europe/London')->toIso8601String() : '' }}">
                                            As of: {{ isset($zappiData['zappi'][0]['gen']) ? \Carbon\Carbon::createFromFormat('d-m-Y H:i:s', $zappiData['zappi'][0]['dat'] . ' ' . $zappiData['zappi'][0]['tim'], 'UTC')->timezone('Europe/London')->diffForHumans() : 'N/A' }}
                                        </span>
                                    </span>
                                    <span class="energy-percentage d-inline-flex align-items-center px-2 py-1 rounded" style="font-size: 0.95em; background: #fff8e1; color: #fbb428;">
                                        <i class="bi bi-sun me-1"></i>
                                        <strong>SunSync Data</strong>
                                        <span class="js-relative-time ms-1"
                                            data-timestamp="{{ isset($plantInfo['updateAt']) ? \Carbon\Carbon::parse($plantInfo['updateAt'])->setTimezone('Europe/London')->toIso8601String() : '' }}">
                                            As of: {{ isset($plantInfo['updateAt']) ? \Carbon\Carbon::parse($plantInfo['updateAt'])->setTimezone('Europe/London')->diffForHumans() : 'N/A' }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            @if(empty($sunSyncData) || !isset($sunSyncData['pv']))
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> Unable to retrieve SunSync inverter data. Please check your SunSync configuration or API connection.
                                </div>
                            @endif
                            <!-- Main Energy Flow Container -->
                            <div class="energy-flow-container" style="max-width: 700px; min-width: 300px; margin: 0 auto; min-height: 500px; position: relative;">
                                <!-- SVG Layer: Contains all flow path animations -->
                                <svg id="energy-flow-svg" width="100%" viewBox="0 0 700 800" preserveAspectRatio="xMidYMid meet" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); z-index: 1; pointer-events: none;">
                                    <!-- Flow Path Definitions -->
                                    <!-- PV1 to Inverter Path -->
                                    <path id="path-pv1-to-inverter" stroke="{{ ($sunSyncData['pv'][0]['power'] ?? 0) > 0 ? '#e59866' : '#cccccc' }}" stroke-width="2" fill="none" class="path-line"
                                          d="M 60 100 L 120 100 L 120 175 L 330 175">
                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                    </path>
                                    @if($sunSyncData['pv'][0]['power'] > 0)
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
                                    @if($sunSyncData['pv'][1]['power'] > 0)
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
                                        $upsPathColor = $battPower == 0 ? '#cccccc' : '#7986cb';
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
                                    @if($sunSyncData['smartLoadPower'] > 0)
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
                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                            <mpath xlink:href="#path-home-to-inverter"/>
                                        </animateMotion>
                                    </image>

                                    <!-- Grid to Inverter Path -->
                                    <path id="path-grid-to-inverter" 
                                          stroke="{{ ($zappiData['zappi'][0]['grd'] ?? 0) != 0 ? '#90caf9' : '#cccccc' }}" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="path-line"
                                          d="{{ ($zappiData['zappi'][0]['grd'] ?? 0) < 0 ? 'M 375 175 L 500 175 L 500 85 L 550 85' : 'M 550 85 L 500 85 L 500 175 L 375 175' }}">
                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                    </path>
                                    @if(($zappiData['zappi'][0]['grd'] ?? 0) != 0)
                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                        <animateMotion dur="3.6s" rotate="auto" repeatCount="indefinite">
                                            <mpath xlink:href="#path-grid-to-inverter"/>
                                        </animateMotion>
                                    </image>
                                    @endif

                                    <!-- Zappi to Grid Path -->
                                    <path id="path-zappi-to-grid" 
                                          stroke="{{ ($zappiData['zappi'][0]['div'] ?? 0) > 0 ? '#26c6da' : '#cccccc' }}" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="path-line"
                                          marker-end="#arrowhead-zappi-charging"
                                          d="M 500 175 L 500 350 L 550 350 L 570 350">
                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                    </path>
                                    @if(($zappiData['zappi'][0]['div'] ?? 0) != 0)
                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                            <mpath xlink:href="#path-zappi-to-grid"/>
                                        </animateMotion>
                                    </image>
                                    @endif
                                    <!-- Zappi to Car Path -->
                                    @php
                                        $evStatusColors = [
                                            'A' => '#aaaaaa',  // Gray for Disconnected
                                            'B1' => '#90caf9', // Blue for Connected
                                            'B2' => '#9c27b0', // Purple for Waiting
                                            'C1' => '#ffc354', // Yellow for Ready
                                            'C2' => '#66bb6a', // Green for Charging
                                            'F' => '#f44336'   // Red for Fault
                                        ];
                                        
                                        $currentStatus = $zappiData['zappi'][0]['pst'] ?? 'A';
                                        $pathColor = $evStatusColors[$currentStatus] ?? '#aaaaaa';
                                    @endphp
                                    <path id="path-car-to-zappi" 
                                          stroke="{{ $pathColor }}" 
                                          stroke-width="3" 
                                          fill="none" 
                                          class="path-line"
                                          d="M 620 350 L 625 350 L 650 350 L 660 350">
                                        <animate attributeName="stroke-dashoffset" from="0" to="-20" dur="1s" repeatCount="indefinite" />
                                    </path>
                                    @if($currentStatus != 'A' && $currentStatus != 'F' && $currentStatus != 'B1' && $currentStatus != 'B2' && $currentStatus != 'C1')
                                    <image x="-10px" y="-10px" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAANdJREFUSEvtlUEOgjAUROdHvAceREPPgAuX3qxbF3qGGvUecg8xNSWUkNr+Qlo2RrbT/Mf8mQJh4YcWno8fBGh1KLF6KbzXgsSpGa+Q00Kr/lqRvtVPEJXQunEhnDYdYB14IIODwAv4IN6QuUFzIcEW5YKwNc0Bid6DVEgUYILT930FQPUhXml7FjZQTjNnooDOQdFKAJWpLu0um2E4o9kz8QwShrMOUt+cdZBruNdBzuF+wKOW0HR0A+3axGjzvkVFK8dVdFvj0yYDcv/hovcgFfgHRDf4AWpe+Bmtf04QAAAAAElFTkSuQmCC" style="width: 20px; height: 20px;">
                                        <animateMotion dur="3.3s" rotate="auto" repeatCount="indefinite">
                                            <mpath xlink:href="#path-car-to-zappi"/>
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
                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['pv'][0]['power']+$sunSyncData['pv'][1]['power'] ?? 0, 0) }}W</div>
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
                                    <div class="energy-node-label" style="font-size: 0.6em;">Smart Load<br>(Water Heater)</div>
                                    <div class="energy-value" style="color:rgb(251 180 40)">{{ number_format($sunSyncData['smartLoadPower'] ?? 0, 0) }}W</div>
                                </div>

                                <!-- Home Load Node -->
                                <div id="home-load-node" class="energy-node" style="position: absolute; top: 370px; left: 65%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                    <img src="{{ asset('images/icons/house.png') }}" alt="Home Load" style="width: 50px; height: 50px;">
                                    <div class="energy-node-label">Home</div>
                                    <div class="energy-value-ss" style="font-size: 0.6em; color:rgb(251 180 40)">{{ number_format($sunSyncData['homeLoadPower'] - $zappiData['zappi'][0]['div'] ?? 0, 0) }}W</div>
                                    <div class="energy-value" style="color:rgb(0 137 57);">{{ number_format($zappiData['zappi'][0]['grd'] + $zappiData['zappi'][0]['gen']  - $zappiData['zappi'][0]['div'] ?? 0, 0) }}W</div>
                                    
                                </div>

                                <!-- Combined Load Node -->
                                <div id="combined-load" class="energy-node inverter-node" style="position: absolute; top: 210px; left: 50%; transform: translateX(-50%); z-index: 10; width: 70px; text-align: center;">
                                    <div id="combined-load-value" class="energy-value" style="background-color:#fcfcfc; padding: 2px; border-radius: 5px;">
                                        <div class="energy-value-ss" style="font-size: 0.7em; color:rgb(251 180 40);">{{ number_format($sunSyncData['upsLoadPower']  + $sunSyncData['smartLoadPower'] + $sunSyncData['homeLoadPower'] - $zappiData['zappi'][0]['div'] ?? 0, 0) }}W</div>
                                    
                                        {{ number_format($sunSyncData['upsLoadPower']  + $sunSyncData['smartLoadPower'] + $zappiData['zappi'][0]['grd'] + $zappiData['zappi'][0]['gen']  - $zappiData['zappi'][0]['div'] ?? 0, 0) }}W
                                    </div>
                                </div>

                                <!-- Right Side Nodes -->
                                <!-- Grid Node -->
                                <div id="grid-node" class="energy-node" style="position: absolute;  top: 50px; left: 82.14%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                    <img src="{{ asset('images/icons/power-grid.png') }}" alt="Power Grid" style="width: 50px; height: 50px;">
                                    <div class="energy-node-label">Grid</div>
                                    <div class="energy-value-ss" style="font-size: 0.6em; color:rgb(251 180 40)">{{ number_format(abs($sunSyncData['gridOrMeterPower'] ?? 0), 0) }}W</div>
                                    <div class="energy-value" style="color:rgb(0 137 57);">{{ number_format(abs($zappiData['zappi'][0]['grd'] ?? 0), 0) }}W</div>
                                </div>

                                <!-- Zappi Node -->
                                <div id="zappi-node" class="energy-node" style="position: absolute; top: 300px; left: 85%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                    <img src="{{ asset('images/icons/ev-charger.png') }}" alt="Zappi Charger" style="width: 50px; height: 50px;">
                                    <div class="energy-node-label">Zappi</div>
                                    <div class="energy-value" style="color:rgb(0 137 57);">{{ number_format($zappiData['zappi'][0]['div'] ?? 0, 0) }}W</div>
                                </div>

                                @php
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

                                <!-- Car Node (Bottom Row Slot 7) -->
                                <div id="car-node" class="energy-node" style="position: absolute; top: 300px; left: 99%; transform: translateX(-50%); z-index: 3; width: 100px; text-align: center;">
                                    <img src="{{ asset('images/icons/car.png') }}" alt="Car" style="width: 50px; height: 50px;">
                                    <div class="energy-node-label">Car</div>
                                    <div class="energy-value" style="font-size: 0.6em;">{{ $evStatusMap[$zappiData['zappi'][0]['pst']]['text'] ?? 'Unknown' }}
                                    <br>
                                    {{ $zappiModeMap[$zappiData['zappi'][0]['zmo']]['text'] ?? 'Unknown' }}
                                    <br> 
                                    {{ $statusMap[$zappiData['zappi'][0]['sta']] ?? 'Unknown' }}  </div>
                                    <!-- Car Consumption Node -->
                                <div id="car-consumption-node" class="energy-node" style=>
                                    <div class="energy-percentage" style="font-size: 0.6em;">Last Consumption: {{ number_format($zappiData['zappi'][0]['che'] ?? 0, 2) }} kWh</div>
                                </div>
                                </div>

                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Energy Flow History Chart -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                            <h2 class="mb-2 mb-md-0">Energy Flow History</h2>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="1h">1h</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="6h">6h</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn active" data-timeframe="24h">24h</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="48h">48h</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="72h">72h</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="7d">7d</button>
                                <button type="button" class="btn btn-outline-primary timeframe-btn" data-timeframe="custom">Custom</button>
                            </div>
                            <div id="customDatePicker" class="mt-3 w-100" style="display: none;">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="datePicker" placeholder="Select a date" readonly>
                                    <button type="button" class="btn btn-primary" id="applyDateBtn">Apply</button>
                                </div>
                                <div id="datePickerError" class="mt-2"></div>
                            </div>
                        </div>
                        <div class="card-body chart-container">
                            <div class="chart-wrapper">
                                <!-- Legend Container (move this above or below the chart) -->
                                <div id="d3-chart-legend" class="chart-legend"></div>

                                <!-- D3.js Chart Container -->
                                <div id="d3-energy-flow-chart" class="chart-area">
                                    <div class="text-center p-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading D3.js Chart...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Initializing D3.js Energy Flow Chart...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Update Instructions Card -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <button class="btn btn-link text-decoration-none text-dark w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#dataUpdateInstructions" aria-expanded="false" aria-controls="dataUpdateInstructions">
                                <h2 class="mb-0 fs-5"><i class="bi bi-gear-fill me-2"></i>Data Update Instructions</h2>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                        <div id="dataUpdateInstructions" class="collapse">
                            <div class="card-body">
                                <h3 class="fs-5 mb-3">Automatic Data Updates</h3>
                                <p>There are multiple ways to update the energy flow data automatically:</p>
                                
                                <div class="mb-4">
                                    <h4 class="fs-6"><i class="bi bi-clock me-2"></i>Scheduled Cron Job</h4>
                                    <p>Add the following cron job to your server to update data every 5 minutes:</p>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <code>*/5 * * * * cd {{ base_path() }} && php artisan app:update-energy-flow-data >> /dev/null 2>&1</code>
                                    </div>
                                    <p class="text-muted small">This runs the data collection command every 5 minutes and sends output to /dev/null.</p>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 class="fs-6"><i class="bi bi-globe me-2"></i>Web Browser/Curl Command</h4>
                                    <p>You can trigger a data update via a web browser or curl command:</p>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <code>{{ url('/api/command/update-energy-flow?api_key=' . config('services.api.key')) }}</code>
                                    </div>
                                    <p class="text-muted small">Simply visit this URL in a browser or use curl to trigger an immediate data update.</p>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 class="fs-6"><i class="bi bi-terminal me-2"></i>Command Line</h4>
                                    <p>Run the update command directly on the server:</p>
                                    <div class="bg-light p-3 rounded mb-3">
                                        <code>php artisan app:update-energy-flow-data</code>
                                    </div>
                                    <p class="text-muted small">This is useful for testing or manual updates.</p>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <strong>Note:</strong> The app's auto-refresh only refreshes the display. A cron job or API call is needed to keep adding new data points to the database.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zappi Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h1 class="mb-0 fs-4">Zappi EV Status</h1>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://www.myenergi.com/wp-content/uploads/2022/02/zappiMultiphase_ContentSuite_v1-D-1.jpg" alt="Zappi EV Charger" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                <p class="mt-2 text-muted">
                                    <small>Zappi EV Charger Visualization</small>
                                </p>
                            </div>
                            <h2 class="fs-5 mb-3">Monitor your Zappi EV charger</h2>
                            <p>This dashboard gives you access to:</p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-lightning-charge me-3 text-primary fs-4"></i>
                                    <div>
                                        <strong>Real-time Status Monitoring</strong>
                                        <p class="mb-0 text-muted">Check current charging status, power usage, and more</p>
                                    </div>
                                </li>
                            </ul>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="{{ url('/zappi/status') }}" class="btn btn-primary">
                                    <i class="bi bi-lightning-charge-fill me-2"></i>View Zappi Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SunSync Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h2 class="mb-0 fs-4">SunSync Inverter Monitoring</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <img src="https://static.wixstatic.com/media/9350f7_29095415950a470883275fba8a88fd65~mv2.png/v1/fill/w_1509,h_782,al_c/9350f7_29095415950a470883275fba8a88fd65~mv2.png" alt="SunSync Inverter" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                                <p class="mt-2 text-muted">
                                    <small>SunSync Solar Inverter System</small>
                                </p>
                            </div>
                            <h2 class="fs-5 mb-3">Track & Control your SunSync Inverter</h2>
                            <p>Monitor your solar energy system with frequently updated data:</p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-sun me-3 text-success fs-4"></i>
                                    <div>
                                        <strong>Inverter Status & Power Output</strong>
                                        <p class="mb-0 text-muted">View current generation and system status</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="bi bi-battery-charging me-3 text-success fs-4"></i>
                                    <div>
                                        <strong>Battery Management</strong>
                                        <p class="mb-0 text-muted">Control battery charge or discharge Based on Zappi Status</p>
                                    </div>
                                </li>
                            </ul>
                            <div class="d-grid gap-2 d-md-block">
                                <a href="{{ url('/sunsync/dashboard') }}" class="btn btn-success">
                                    <i class="bi bi-sun-fill me-2"></i>View SunSync Dashboard
                                </a>
                           <p></p>
                                <a href="{{ url('/ev-charging/status') }}" class="btn btn-success">
                                <i class="bi bi-ev-station me-2"></i>Control Inverter by EV Status
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-end align-items-center text-muted" style="font-size: 0.85em; margin-bottom: -1.5rem; gap: 0.5rem;">
    <i class="bi bi-arrow-clockwise"></i>
    <span id="auto-refresh-status">Auto-refresh enabled (every 1 minute)</span>
    <div class="form-check form-switch ms-2">
        <input class="form-check-input" type="checkbox" id="autoRefreshToggle" checked>
        <label class="form-check-label" for="autoRefreshToggle" style="user-select:none;">Auto-refresh</label>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/energy-flow.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">
<link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('js/home.js') }}"></script>
@endsection 