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
                                    <div class="energy-value" style="color:rgb(0 137 57);">{{ number_format($zappiData['zappi'][0]['grd'] + $zappiData['zappi'][0]['gen']  ?? 0, 0) }}W</div>
                                    
                                </div>

                                <!-- Combined Load Node -->
                                <div id="combined-load" class="energy-node inverter-node" style="position: absolute; top: 210px; left: 50%; transform: translateX(-50%); z-index: 10; width: 70px; text-align: center;">
                                    <div id="combined-load-value" class="energy-value" style="background-color:#fcfcfc; padding: 2px; border-radius: 5px;">
                                        <div class="energy-value-ss" style="font-size: 0.7em; color:rgb(251 180 40);">{{ number_format($sunSyncData['upsLoadPower']  + $sunSyncData['smartLoadPower'] + $sunSyncData['homeLoadPower'] - $zappiData['zappi'][0]['div'] ?? 0, 0) }}W</div>
                                    
                                        {{ number_format($sunSyncData['upsLoadPower']  + $sunSyncData['smartLoadPower'] + $zappiData['zappi'][0]['grd'] + $zappiData['zappi'][0]['gen'] ?? 0, 0) }}W
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
<style>
    .energy-node {
        /* Common styles for energy nodes */
    }
    .energy-node-label {
        font-weight: bold;
        font-size: 0.9em;
        margin-top: 2px;
    }
    .energy-value, .energy-percentage {
        font-size: 0.85em;
        color: #555;
    }
    .energy-value-ss {
        color:#6ec6f6 !important; /* Light pastel blue */
    }
    /* Add styles for animated paths */
    .path-line {
        stroke-dasharray: 5,5;
        transition: stroke 0.3s ease;
    }
    /* Arrow marker styles */
    marker polygon {
        transition: fill 0.3s ease;
    }
</style>
@endpush

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded');
    const svg = document.getElementById('energy-flow-svg');
    const container = document.querySelector('.energy-flow-container');
    
    // Define base sizes for different types of images
    const imageSizes = {
        'solar-panel': { base: 50, min: 35 },
        'battery': { base: 50, min: 35 },
        'ups': { base: 50, min: 35 },
        'smart-device': { base: 50, min: 35 },
        'house': { base: 50, min: 35 },
        'power-grid': { base: 50, min: 35 },
        'ev-charger': { base: 50, min: 35 },
        'car': { base: 50, min: 35 },
        'inverter': { base: 70, min: 50 }  // Inverter is larger
    };
    
    if (!svg || !container) {
        console.error('Required elements not found:', { svg: !!svg, container: !!container });
        return;
    }
    
    function adjustViewBox() {
        // Get container dimensions and base values
        const containerWidth = container.offsetWidth;
        const baseWidth = 700;  // Original design width
        const baseHeight = 500; // Original design height
        
        // Calculate adjustment factor for responsive scaling
        const adjustmentFactor = Math.max(0, (baseWidth - containerWidth) / baseWidth);
        
        // Set base viewBox
        svg.setAttribute('viewBox', `0 0 ${baseWidth} ${baseHeight}`);
        console.log(containerWidth);

        // Handle large screens (>580px)
        if (containerWidth > 580) {
            // Set fixed values for large screens
            container.style.minHeight = `500px`;
            container.style.top = `0px`;
            svg.setAttribute('viewBox', `0 0 ${baseWidth} ${baseHeight}`);

            // Combined load element styling
            document.querySelector('#combined-load').style.top = '220px';
            document.querySelector('#combined-load').style.width = '90px';
            document.querySelector('#combined-load-value').style.fontSize = '13px';
            document.querySelector('#combined-load-value').style.lineHeight = '18px';
            document.querySelector('#combined-load-value').style.padding = '5px';
            document.querySelector('#combined-energy-flow').style.minHeight = '500px';

            // Path coordinates for large screens
            document.querySelector('#path-car-to-zappi').setAttribute('d', 'M 620 350 L 625 350 L 650 350 L 660 350');
            document.querySelector('#path-zappi-to-grid').setAttribute('d', 'M 500 175 L 500 350 L 550 350 L 570 350');

            // Font sizes for large screens
            document.querySelectorAll('.energy-node-label').forEach(label => {
                label.style.fontSize = '0.9em';
            });
            document.querySelectorAll('.energy-value').forEach(value => {
                value.style.fontSize = '0.85em';
            });
            document.querySelectorAll('.energy-percentage').forEach(percentage => {
                percentage.style.fontSize = '0.6em';
            });

            // Battery node specific styling
            document.querySelector('#battery-node .energy-percentage').style.fontSize = '0.9em';
            document.querySelector('#battery-node .energy-percentage').style.top = '-83px';
            document.querySelector('#battery-node .energy-percentage').style.left = '-40px';

            // Smart Load node specific styling
            document.querySelector('#smart-load-node .energy-node-label').style.fontSize = '0.6em';

            // Node positioning for large screens
            document.querySelector('#zappi-node').style.top = '300px';
            document.querySelector('#car-node').style.top = '300px';
            document.querySelector('#zappi-node').style.left = '85%';
            document.querySelector('#grid-node').style.top = '50px';
            document.querySelector('#pv1-node').style.top = '50px';
            document.querySelector('#inverter-node').style.top = '125px';
            document.querySelector('#battery-node').style.top = '370px';
            document.querySelector('#ups-node').style.top = '370px';
            document.querySelector('#smart-load-node').style.top = '370px';
            document.querySelector('#home-load-node').style.top = '370px';
            document.querySelector('#pv2-node').style.top = '200px';
        }

        if (containerWidth < 580) {
            svg.setAttribute('viewBox', `0 -50 ${baseWidth} ${baseHeight}`);
            document.querySelector('#pv-combined-power-node').style.top = '120px';
        }

        if (containerWidth < 530) {
            document.querySelector('#ups-node').style.top = '320px';
            document.querySelector('#smart-load-node').style.top = '320px';
            document.querySelector('#home-load-node').style.top = '320px';
            document.querySelector('#zappi-node').style.top = '280px';
            document.querySelector('#car-node').style.top = '280px';
            document.querySelector('#battery-node').style.top = '320px';
            document.querySelector('#inverter-node').style.top = '110px';
        }

        if (containerWidth < 510) {
            document.querySelector('#grid-node').style.top = '50px';
            document.querySelector('#pv1-node').style.top = '50px';
            document.querySelector('#battery-node').style.top = '300px';
            document.querySelector('#ups-node').style.top = '300px';
            document.querySelector('#smart-load-node').style.top = '300px';
            document.querySelector('#home-load-node').style.top = '300px';
            document.querySelector('#pv2-node').style.top = '190px';
            document.querySelector('#inverter-node').style.top = '110px';
            document.querySelector('#zappi-node').style.top = '260px';
            document.querySelector('#car-node').style.top = '260px';
        }

        if (containerWidth < 483) {
            svg.setAttribute('viewBox', `0 -80 ${baseWidth} ${baseHeight}`);
            document.querySelector('#zappi-node').style.top = '230px';
            document.querySelector('#car-node').style.top = '230px';
        }

        // Handle small screens (<350px)
        if (containerWidth < 350) {
            container.style.minHeight = `320px`;
            container.style.top = `-100px`;
            svg.setAttribute('viewBox', `0 -180 ${baseWidth} ${baseHeight}`);

            // Combined load element styling
            document.querySelector('#combined-load').style.top = '185px';
            document.querySelector('#combined-load').style.width = '60px';
            document.querySelector('#combined-load-value').style.fontSize = '10px';
            document.querySelector('#combined-load-value').style.lineHeight = '10px';
            document.querySelector('#combined-load-value').style.padding = '1px';
            document.querySelector('#combined-energy-flow').style.minHeight = '330px';

            // Path coordinates for small screens
            document.querySelector('#path-car-to-zappi').setAttribute('d', 'M 600 300 L 625 300 L 640 300 L 650 300');
            document.querySelector('#path-zappi-to-grid').setAttribute('d', 'M 500 175 L 500 300 L 530 300 L 535 300');

            // Font sizes for small screens
            document.querySelectorAll('.energy-node-label').forEach(label => {
                label.style.fontSize = '0.5em';
            });
            document.querySelectorAll('.energy-value').forEach(value => {
                value.style.fontSize = '0.4em';
            });
            document.querySelectorAll('.energy-percentage').forEach(percentage => {
                percentage.style.fontSize = '0.3em';
            });

            // Battery node specific styling
            document.querySelector('#battery-node .energy-percentage').style.fontSize = '0.6em';
            document.querySelector('#battery-node .energy-percentage').style.top = '-43px';
            document.querySelector('#battery-node .energy-percentage').style.left = '5px';

            // Smart Load node specific styling
            document.querySelector('#smart-load-node .energy-node-label').style.fontSize = '0.4em';

            // Zappi node specific styling
            document.querySelector('#zappi-node').style.top = '200px';
            document.querySelector('#car-node').style.top = '200px';
            document.querySelector('#car-consumption-node').style.width = '50px';
            document.querySelector('#zappi-node').style.left = '80%';

            // Node positioning for small screens
            document.querySelector('#grid-node').style.top = '90px';
            document.querySelector('#pv1-node').style.top = '90px';
            document.querySelector('#battery-node').style.top = '220px';
            document.querySelector('#ups-node').style.top = '220px';
            document.querySelector('#smart-load-node').style.top = '220px';
            document.querySelector('#home-load-node').style.top = '220px';
            document.querySelector('#pv2-node').style.top = '175px';
            document.querySelector('#inverter-node').style.top = '110px';
        }
       
        
        // Adjust image sizes based on their type
        document.querySelectorAll('.energy-node img').forEach(img => {
            const alt = img.alt.toLowerCase();
            let imageType = 'solar-panel'; // default type
            
            // Determine image type from alt text
            for (const type in imageSizes) {
                if (alt.includes(type)) {
                    imageType = type;
                    break;
                }
            }
            
            // Calculate and apply new image size
            const { base, min } = imageSizes[imageType];
            const newSize = Math.max(min, base - (adjustmentFactor * (base - min)));
            
            img.style.width = `${newSize}px`;
            img.style.height = `${newSize}px`;
        });
    }
    
    // Initial adjustment
    adjustViewBox();
    
    // Adjust on window resize
    window.addEventListener('resize', adjustViewBox);
});

// Auto-refresh toggle logic
let autoRefreshInterval = null;
const AUTO_REFRESH_KEY = 'autoRefreshEnabled';

function setAutoRefresh(enabled) {
    const status = document.getElementById('auto-refresh-status');
    if (enabled) {
        if (!autoRefreshInterval) {
            autoRefreshInterval = setInterval(function() {
                window.location.reload();
            }, 60000);
        }
        status.textContent = 'Auto-refresh enabled (every 1 minute)';
    } else {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        status.textContent = 'Auto-refresh disabled';
    }
    localStorage.setItem(AUTO_REFRESH_KEY, enabled ? '1' : '0');
}

document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('autoRefreshToggle');
    // Set initial state from localStorage
    const saved = localStorage.getItem(AUTO_REFRESH_KEY);
    if (saved === '0') {
        toggle.checked = false;
        setAutoRefresh(false);
    } else {
        toggle.checked = true;
        setAutoRefresh(true);
    }
    toggle.addEventListener('change', function() {
        setAutoRefresh(toggle.checked);
    });
});

// Add dynamic relative time updating for all .js-relative-time elements
function updateRelativeTimes() {
    const elements = document.querySelectorAll('.js-relative-time');
    const now = new Date();
    elements.forEach(el => {
        const iso = el.getAttribute('data-timestamp');
        if (!iso) return;
        const then = new Date(iso);
        let diff = Math.floor((now - then) / 1000); // seconds
        let text = '';
        if (isNaN(diff)) {
            text = 'As of: N/A';
        } else if (diff < 5) {
            text = 'As of: just now';
        } else if (diff < 60) {
            text = `As of: ${diff} sec ago`;
        } else if (diff < 3600) {
            const min = Math.floor(diff / 60);
            text = `As of: ${min} min${min === 1 ? '' : 's'} ago`;
        } else if (diff < 86400) {
            const hr = Math.floor(diff / 3600);
            text = `As of: ${hr} hr${hr === 1 ? '' : 's'} ago`;
        } else {
            const days = Math.floor(diff / 86400);
            text = `As of: ${days} day${days === 1 ? '' : 's'} ago`;
        }
        el.textContent = text;
    });
}
setInterval(updateRelativeTimes, 1000);
document.addEventListener('DOMContentLoaded', updateRelativeTimes);

// SS value toggle logic
const SS_TOGGLE_KEY = 'showSSValues';
function setSSValuesVisible(visible) {
    document.querySelectorAll('.energy-value-ss').forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
    localStorage.setItem(SS_TOGGLE_KEY, visible ? '1' : '0');
}
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    // SS value toggle
    const ssToggle = document.getElementById('ssValueToggle');
    const ssSaved = localStorage.getItem(SS_TOGGLE_KEY);
    if (ssSaved === '1') {
        ssToggle.checked = true;
        setSSValuesVisible(true);
    } else {
        ssToggle.checked = false;
        setSSValuesVisible(false);
    }
    ssToggle.addEventListener('change', function() {
        setSSValuesVisible(ssToggle.checked);
    });
});


</script>
@endsection 