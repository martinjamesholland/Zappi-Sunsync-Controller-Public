@extends('layouts.app')

@section('title', 'Reports - Solar Battery EV Charger')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/heatmap.js@2.0.5/heatmap.css">
<style>
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
    
    .report-section {
        margin-bottom: 40px;
    }
    
    .stat-card {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #2563eb;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 0.875rem;
        margin-top: 5px;
    }
    
    .date-range-selector {
        margin-bottom: 20px;
    }
    
    .chart-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1f2937;
    }
    
    #costTrendChart {
        height: 400px !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="bi bi-graph-up"></i> System Reports
        </h1>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('settings.index') }}#cost-settings" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear"></i> Configure Cost Settings
            </a>
            <select id="dateRange" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="60">Last 60 days</option>
                <option value="90">Last 90 days</option>
                <option value="ytd">Year to Date</option>
                <option value="all">All Time</option>
            </select>
        </div>
    </div>

    <!-- Date Range Display -->
    <div class="alert alert-info d-flex align-items-center justify-content-between mb-4" id="dateRangeDisplay">
        <div>
            <strong><i class="bi bi-calendar-range"></i> Date Range:</strong>
            <span id="dateRangeText">Calculating...</span>
        </div>
    </div>

    <!-- System Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="systemStats">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="totalRecords">-</div>
                                <div class="stat-label">Total Records</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="totalDays">-</div>
                                <div class="stat-label">Days of Data</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="maxSolar">-</div>
                                <div class="stat-label">Max Solar (W)</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="oldestRecord">-</div>
                                <div class="stat-label">Oldest Record</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Home Usage Statistics -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-house-door-fill"></i> Home Usage Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="homeUsageStats">
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="avgHomeUsage" style="color: #2563eb;">-</div>
                                <div class="stat-label">Average (W)</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="medianHomeUsage" style="color: #22c55e;">-</div>
                                <div class="stat-label">Median (W)</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="minHomeUsage" style="color: #6b7280;">-</div>
                                <div class="stat-label">Minimum (W)</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="maxHomeUsage" style="color: #ef4444;">-</div>
                                <div class="stat-label">Maximum (W)</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="stdDevHomeUsage" style="color: #f59e0b;">-</div>
                                <div class="stat-label">Std Deviation</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-value" id="dataPoints" style="color: #8b5cf6;">-</div>
                                <div class="stat-label">Data Points</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> These statistics show the distribution of home load power (in Watts) over the selected period.
                            Average shows the mean value, while Median shows the middle value when sorted.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Energy Distribution and Load Distribution -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Energy Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="energyDistributionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Load Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="loadDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Solar Yield and Battery Efficiency -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-sun"></i> Solar Yield</h5>
                </div>
                <div class="card-body">
                    <canvas id="solarYieldChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-battery-half"></i> Battery Efficiency</h5>
                </div>
                <div class="card-body">
                    <canvas id="batteryEfficiencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Grid Interaction -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Grid Interaction</h5>
                </div>
                <div class="card-body">
                    <canvas id="gridInteractionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 5: EV Charging Activity -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-ev-front"></i> EV Charging Activity</h5>
                </div>
                <div class="card-body">
                    <canvas id="evChargingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 6: Cost Breakdown -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-currency-pound"></i> Cost Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="costBreakdown">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Total cost for all energy imported from the grid (peak + off-peak + EV charging)">
                                <div class="stat-value" id="totalImportCost" style="color: #ef4444;">-</div>
                                <div class="stat-label">Grid Import Cost</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Credit received for exporting excess solar energy back to the grid">
                                <div class="stat-value" id="totalExportCredit" style="color: #22c55e;">-</div>
                                <div class="stat-label">Export Credit</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Net cost after subtracting export credits from import costs. This is what you actually pay.">
                                <div class="stat-value" id="netCost" style="color: #2563eb;">-</div>
                                <div class="stat-label">Net Cost</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Cost for EV charging from grid (EV rate applied only to the Zappi charger power consumption, not total home usage)">
                                <div class="stat-value" id="evChargingCost" style="color: #f59e0b;">-</div>
                                <div class="stat-label">EV Charging Cost</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Cost for importing energy from the grid during peak hours (excludes EV charging)">
                                <div class="stat-value" id="peakImportCost">-</div>
                                <div class="stat-label">Peak Hours Cost</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-3">
                            <div class="stat-card" data-bs-toggle="tooltip" data-bs-placement="top" title="Cost for importing energy from the grid during off-peak hours + EV charging costs">
                                <div class="stat-value" id="offPeakImportCost" style="color: #8b5cf6;">-</div>
                                <div class="stat-label">Off-Peak Hours Cost</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3" id="costRatesInfo">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Rates:</strong> <span id="ratesDisplay">Loading rates...</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 7: Daily Cost Trend -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Daily Cost Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="costTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduler & Cron Setup -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Background Refresh: Scheduler & Cron</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        The data mart is refreshed every <strong>15 minutes</strong> via Laravel Scheduler. Ensure your server has the following cron entry:
                    </p>
                    <pre class="bg-light p-3 rounded"><code>* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
                    <p class="mb-3">
                        This runs Laravel's scheduler each minute, which in turn runs the command below every 15 minutes:
                    </p>
                    <pre class="bg-light p-3 rounded"><code>php artisan app:refresh-energy-data-mart</code></pre>
                    <p class="mb-3">
                        Prefer to call the ETL directly without the scheduler? Use this 15‑minute cron instead:
                    </p>
                    <pre class="bg-light p-3 rounded"><code>*/15 * * * * cd {{ base_path() }} &amp;&amp; php artisan app:refresh-energy-data-mart &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
                    <div class="alert alert-info mt-3" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        You can also run a one‑off backfill from the terminal:
                        <code>php artisan app:refresh-energy-data-mart</code>
                    </div>

                    <hr>
                    <h6 class="mb-2">Trigger via URL (optional)</h6>
                    <p class="mb-2">Set <code>ETL_WEBHOOK_KEY</code> in <code>.env</code>, then call:</p>
                    <pre class="bg-light p-3 rounded"><code>{{ route('reports.refresh-data-mart') }}?token=YOUR_ETL_WEBHOOK_KEY</code></pre>
                    <p class="mb-0 text-muted">This endpoint is throttled and also works if you are logged in (no token required).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="{{ asset('js/reports.js') }}"></script>
@endsection
