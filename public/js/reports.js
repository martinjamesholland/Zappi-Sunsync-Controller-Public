// Reports page JavaScript
let dateRange = '30'; // Default value
let charts = {};
const API_BASE_URL = '/api';

document.addEventListener('DOMContentLoaded', function() {
    // Get the selected value from the dropdown
    const selector = document.getElementById('dateRange');
    if (selector) {
        dateRange = selector.value;
    }
    
    initializeDateRangeSelector();
    initializeCharts();
    loadAllData();
    
    // Initialize Bootstrap tooltips for cost breakdown cards
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function initializeDateRangeSelector() {
    const selector = document.getElementById('dateRange');
    if (selector) {
        selector.addEventListener('change', function() {
            dateRange = this.value; // Keep as string to handle 'ytd' and 'all'
            updateDateRangeDisplay();
            loadAllData();
        });
    }
    // Update display on initial load
    updateDateRangeDisplay();
}

function updateDateRangeDisplay() {
    const dateRangeText = document.getElementById('dateRangeText');
    if (!dateRangeText) return;
    
    let fromDate, toDate, displayText;
    
    toDate = new Date();
    
    if (dateRange === 'all') {
        displayText = 'All available data';
    } else if (dateRange === 'ytd') {
        fromDate = new Date(new Date().getFullYear(), 0, 1); // January 1st of current year
        displayText = fromDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' - ' + toDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } else {
        const days = parseInt(dateRange);
        fromDate = new Date();
        fromDate.setDate(fromDate.getDate() - days);
        displayText = fromDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' - ' + toDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    
    dateRangeText.textContent = displayText;
}

function initializeCharts() {
    // Energy Distribution Chart (Pie Chart)
    charts.energyDistribution = new Chart(document.getElementById('energyDistributionChart'), {
        type: 'pie',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed || 0;
                            return context.label + ': ' + value.toFixed(2) + 'W';
                        }
                    }
                }
            }
        }
    });

    // Load Distribution Chart (Doughnut Chart)
    charts.loadDistribution = new Chart(document.getElementById('loadDistributionChart'), {
        type: 'doughnut',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed || 0;
                            return context.label + ': ' + value.toFixed(2) + 'W';
                        }
                    }
                }
            }
        }
    });

    // Solar Yield Chart (Line Chart)
    charts.solarYield = new Chart(document.getElementById('solarYieldChart'), {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Battery Efficiency Chart (Line Chart)
    charts.batteryEfficiency = new Chart(document.getElementById('batteryEfficiencyChart'), {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Percentage (%)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Grid Interaction Chart (Line Chart)
    charts.gridInteraction = new Chart(document.getElementById('gridInteractionChart'), {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // EV Charging Activity Chart (Line Chart)
    charts.evCharging = new Chart(document.getElementById('evChargingChart'), {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Cost Trend Chart (Line Chart)
    charts.costTrend = new Chart(document.getElementById('costTrendChart'), {
        type: 'line',
        data: { datasets: [] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cost (£)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '£' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': £' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}

async function loadAllData() {
    await Promise.all([
        loadSystemStats(),
        loadHomeUsageStats(),
        loadEnergyDistribution(),
        loadLoadDistribution(),
        loadSolarYield(),
        loadBatteryEfficiency(),
        loadGridInteraction(),
        loadEvCharging(),
        loadCostBreakdown(),
        loadCostTrend()
    ]);
}

async function loadSystemStats() {
    try {
        const response = await fetch(API_BASE_URL + '/reports/system-stats');
        const stats = await response.json();
        
        if (stats.total_records === 0) {
            // Show message when no data
            document.getElementById('totalRecords').textContent = '0';
            document.getElementById('totalDays').textContent = '0';
            document.getElementById('maxSolar').textContent = '-';
            document.getElementById('oldestRecord').textContent = '-';
            
            // Show warning message
            const warningDiv = document.getElementById('noDataWarning');
            if (!warningDiv) {
                const statsContainer = document.querySelector('#systemStats').parentElement;
                statsContainer.innerHTML = `
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> No Data Available</h5>
                        <p>No energy flow data has been recorded yet. The system needs to collect data over time.</p>
                        <hr>
                        <p class="mb-0">
                            <strong>To start collecting data:</strong><br>
                            1. Go to the Home page and let it collect data every 5 minutes<br>
                            2. Check that your SunSync and Zappi credentials are configured correctly<br>
                            3. The system will automatically log energy flow data every 5 minutes
                        </p>
                        <p class="mt-2 mb-0">
                            <a href="/" class="btn btn-primary btn-sm">Go to Home Page</a>
                        </p>
                    </div>
                `;
            }
            return;
        }
        
        document.getElementById('totalRecords').textContent = stats.total_records.toLocaleString();
        document.getElementById('totalDays').textContent = stats.total_days.toLocaleString();
        document.getElementById('maxSolar').textContent = Math.round(stats.max_solar_yield || 0).toLocaleString();
        
        const oldestDate = stats.oldest_record ? new Date(stats.oldest_record).toLocaleDateString() : '-';
        document.getElementById('oldestRecord').textContent = oldestDate;
    } catch (error) {
        console.error('Error loading system stats:', error);
    }
}

async function loadHomeUsageStats() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/home-usage-stats?days=${dateRange}`);
        const stats = await response.json();
        
        // Check if we have data
        if (stats.count === 0) {
            document.getElementById('avgHomeUsage').textContent = '-';
            document.getElementById('medianHomeUsage').textContent = '-';
            document.getElementById('minHomeUsage').textContent = '-';
            document.getElementById('maxHomeUsage').textContent = '-';
            document.getElementById('stdDevHomeUsage').textContent = '-';
            document.getElementById('dataPoints').textContent = '0';
            return;
        }
        
        document.getElementById('avgHomeUsage').textContent = Math.round(stats.average).toLocaleString();
        document.getElementById('medianHomeUsage').textContent = Math.round(stats.median).toLocaleString();
        document.getElementById('minHomeUsage').textContent = Math.round(stats.min).toLocaleString();
        document.getElementById('maxHomeUsage').textContent = Math.round(stats.max).toLocaleString();
        document.getElementById('stdDevHomeUsage').textContent = Math.round(stats.std_dev).toLocaleString();
        document.getElementById('dataPoints').textContent = stats.count.toLocaleString();
    } catch (error) {
        console.error('Error loading home usage stats:', error);
    }
}

async function loadEnergyDistribution() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/energy-distribution?days=${dateRange}`);
        const data = await response.json();
        
        const solarEnergy = parseFloat(data.solar_energy) || 0;
        const gridImport = parseFloat(data.grid_import) || 0;
        const batteryDischarge = parseFloat(data.battery_discharge) || 0;
        
        charts.energyDistribution.data.labels = ['Solar', 'Grid Import', 'Battery'];
        charts.energyDistribution.data.datasets = [{
            label: 'Energy Distribution',
            data: [solarEnergy, gridImport, batteryDischarge],
            backgroundColor: [
                'rgb(251, 191, 36)',
                'rgb(239, 68, 68)',
                'rgb(34, 197, 94)'
            ]
        }];
        charts.energyDistribution.update();
    } catch (error) {
        console.error('Error loading energy distribution:', error);
    }
}

async function loadLoadDistribution() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/load-distribution?days=${dateRange}`);
        const data = await response.json();
        
        const homeLoad = parseFloat(data.home_load) || 0;
        const smartLoad = parseFloat(data.smart_load) || 0;
        const upsLoad = parseFloat(data.ups_load) || 0;
        const evLoad = parseFloat(data.ev_charging_load) || 0;
        
        charts.loadDistribution.data.labels = ['Home', 'Smart Load', 'UPS', 'EV Charging'];
        charts.loadDistribution.data.datasets = [{
            label: 'Load Distribution',
            data: [homeLoad, smartLoad, upsLoad, evLoad],
            backgroundColor: [
                'rgb(37, 99, 235)',
                'rgb(251, 191, 36)',
                'rgb(34, 197, 94)',
                'rgb(239, 68, 68)'
            ]
        }];
        charts.loadDistribution.update();
    } catch (error) {
        console.error('Error loading load distribution:', error);
    }
}

async function loadSolarYield() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/solar-yield?days=${dateRange}`);
        const data = await response.json();
        
        const labels = data.map(item => item.date);
        const avgPv = data.map(item => parseFloat(item.avg_pv_power) || 0);
        const peakPv = data.map(item => parseFloat(item.peak_pv_power) || 0);
        
        charts.solarYield.data.labels = labels;
        charts.solarYield.data.datasets = [
            {
                label: 'Average PV Power',
                data: avgPv,
                borderColor: 'rgb(251, 191, 36)',
                backgroundColor: 'rgba(251, 191, 36, 0.1)',
                tension: 0.4
            },
            {
                label: 'Peak PV Power',
                data: peakPv,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }
        ];
        charts.solarYield.update();
    } catch (error) {
        console.error('Error loading solar yield:', error);
    }
}

async function loadBatteryEfficiency() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/battery-efficiency?days=${dateRange}`);
        const data = await response.json();
        
        const labels = data.map(item => item.date);
        const avgSoc = data.map(item => parseFloat(item.avg_soc) || 0);
        const maxSoc = data.map(item => parseFloat(item.max_soc) || 0);
        const minSoc = data.map(item => parseFloat(item.min_soc) || 0);
        
        charts.batteryEfficiency.data.labels = labels;
        charts.batteryEfficiency.data.datasets = [
            {
                label: 'Average SOC (%)',
                data: avgSoc,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            },
            {
                label: 'Max SOC (%)',
                data: maxSoc,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            },
            {
                label: 'Min SOC (%)',
                data: minSoc,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }
        ];
        charts.batteryEfficiency.update();
    } catch (error) {
        console.error('Error loading battery efficiency:', error);
    }
}

async function loadGridInteraction() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/grid-interaction?days=${dateRange}`);
        const data = await response.json();
        
        const labels = data.map(item => item.date);
        const gridImport = data.map(item => parseFloat(item.grid_import) || 0);
        const gridExport = data.map(item => -parseFloat(item.grid_export) || 0);
        
        charts.gridInteraction.data.labels = labels;
        charts.gridInteraction.data.datasets = [
            {
                label: 'Grid Import',
                data: gridImport,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            },
            {
                label: 'Grid Export',
                data: gridExport,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }
        ];
        charts.gridInteraction.update();
    } catch (error) {
        console.error('Error loading grid interaction:', error);
    }
}

async function loadEvCharging() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/ev-charging?days=${dateRange}`);
        const data = await response.json();
        
        const labels = data.map(item => item.date);
        const avgCharging = data.map(item => parseFloat(item.avg_charging_power) || 0);
        const peakCharging = data.map(item => parseFloat(item.peak_charging_power) || 0);
        const chargingSessions = data.map(item => parseInt(item.charging_sessions) || 0);
        
        charts.evCharging.data.labels = labels;
        charts.evCharging.data.datasets = [
            {
                label: 'Average Charging Power',
                data: avgCharging,
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4
            },
            {
                label: 'Peak Charging Power',
                data: peakCharging,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }
        ];
        charts.evCharging.update();
    } catch (error) {
        console.error('Error loading EV charging data:', error);
    }
}

async function loadCostBreakdown() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/cost-breakdown?days=${dateRange}`);
        const data = await response.json();
        
        // Update the cost display elements
        document.getElementById('totalImportCost').textContent = '£' + data.total_import_cost.toFixed(2);
        document.getElementById('totalExportCredit').textContent = '£' + data.total_export_credit.toFixed(2);
        document.getElementById('netCost').textContent = '£' + data.net_cost.toFixed(2);
        document.getElementById('evChargingCost').textContent = '£' + data.ev_charging_cost.toFixed(2);
        document.getElementById('peakImportCost').textContent = '£' + data.peak_import_cost.toFixed(2);
        document.getElementById('offPeakImportCost').textContent = '£' + data.off_peak_import_cost.toFixed(2);
        
        // Update tooltips with actual values for better context
        updateCostTooltips(data);
        
        // Update rates display
        if (data.rates) {
            const ratesText = `Peak (${data.rates.peak_hours}): ${data.rates.peak_rate} | Off-Peak: ${data.rates.off_peak_rate} | EV Charging: ${data.rates.ev_charging_rate} | Export: ${data.rates.export_credit}`;
            document.getElementById('ratesDisplay').textContent = ratesText;
        }
    } catch (error) {
        console.error('Error loading cost breakdown:', error);
    }
}

function updateCostTooltips(data) {
    // Update tooltips with actual values for better context
    const totalImportEl = document.querySelector('#totalImportCost').closest('.stat-card');
    if (totalImportEl) {
        totalImportEl.setAttribute('data-bs-original-title', `Total cost for all energy imported from the grid: Peak (£${data.peak_import_cost.toFixed(2)}) + Off-Peak (£${data.off_peak_import_cost.toFixed(2)}) + EV (£${data.ev_charging_cost.toFixed(2)})`);
    }
    
    const exportCreditEl = document.querySelector('#totalExportCredit').closest('.stat-card');
    if (exportCreditEl && data.total_grid_export_kwh) {
        exportCreditEl.setAttribute('data-bs-original-title', `Credit received for exporting ${data.total_grid_export_kwh.toFixed(2)} kWh of solar energy back to the grid`);
    }
    
    const netCostEl = document.querySelector('#netCost').closest('.stat-card');
    if (netCostEl) {
        netCostEl.setAttribute('data-bs-original-title', `Net cost calculation: Import (£${data.total_import_cost.toFixed(2)}) - Export (£${data.total_export_credit.toFixed(2)}) = £${data.net_cost.toFixed(2)}. This is what you actually pay.`);
    }
    
    const evCostEl = document.querySelector('#evChargingCost').closest('.stat-card');
    if (evCostEl && data.total_ev_charging_kwh) {
        const evRate = data.rates ? data.rates.ev_charging_rate.replace(/[£/kWh]/g, '') : '0.07';
        evCostEl.setAttribute('data-bs-original-title', `EV Charging: ${data.total_ev_charging_kwh.toFixed(2)} kWh from Zappi charger × £${evRate}/kWh = £${data.ev_charging_cost.toFixed(2)}. Only Zappi power is counted, not total home usage.`);
    }
    
    const peakCostEl = document.querySelector('#peakImportCost').closest('.stat-card');
    if (peakCostEl) {
        peakCostEl.setAttribute('data-bs-original-title', `Cost for importing energy from the grid during peak hours (excludes EV charging)`);
    }
    
    const offPeakCostEl = document.querySelector('#offPeakImportCost').closest('.stat-card');
    if (offPeakCostEl) {
        const offPeakRegular = data.off_peak_import_cost - data.ev_charging_cost;
        offPeakCostEl.setAttribute('data-bs-original-title', `Breakdown: Regular off-peak (£${offPeakRegular.toFixed(2)}) + EV charging (£${data.ev_charging_cost.toFixed(2)}) = £${data.off_peak_import_cost.toFixed(2)}`);
    }
    
    // Re-initialize tooltips with new values
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('#costBreakdown [data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

async function loadCostTrend() {
    try {
        const response = await fetch(`${API_BASE_URL}/reports/daily-cost-breakdown?days=${dateRange}`);
        const data = await response.json();
        
        const labels = data.map(item => item.date);
        const importCosts = data.map(item => parseFloat(item.import_cost) || 0);
        const exportCredits = data.map(item => parseFloat(item.export_credit) || 0);
        const evChargingCosts = data.map(item => parseFloat(item.ev_charging_cost) || 0);
        const netCosts = data.map(item => parseFloat(item.net_cost) || 0);
        
        charts.costTrend.data.labels = labels;
        charts.costTrend.data.datasets = [
            {
                label: 'Daily Import Cost',
                data: importCosts,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            },
            {
                label: 'Daily Export Credit',
                data: exportCredits,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            },
            {
                label: 'EV Charging Cost',
                data: evChargingCosts,
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4
            },
            {
                label: 'Net Daily Cost',
                data: netCosts,
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4,
                borderWidth: 2
            }
        ];
        charts.costTrend.update();
    } catch (error) {
        console.error('Error loading cost trend:', error);
    }
}
