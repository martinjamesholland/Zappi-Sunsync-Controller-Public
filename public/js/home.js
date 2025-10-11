// Add constants for localStorage keys
const TIMEFRAME_KEY = 'energy_flow_timeframe';
const CUSTOM_DATE_KEY = 'energy_flow_custom_date';
const DATASET_VISIBILITY_KEY = 'energy_flow_dataset_visibility';

// Global variable for available dates
let availableDates = [];

// D3.js Chart variables
let d3Chart = null;
let d3Data = [];
let d3Timeframe = '24h';

// Dataset configuration for D3.js
const d3Datasets = [
    { key: 'pv1_power', label: 'PV1 Power', color: '#c9af16', visible: false },
    { key: 'pv2_power', label: 'PV2 Power', color: '#ad9715', visible: false },
    { key: 'total_pv_power', label: 'Total PV Power', color: '#fff200', visible: true },
    { key: 'grid_power', label: 'Grid Power', color: '#FF8C00', visible: true },
    { key: 'battery_power', label: 'Battery Power', color: '#4c58af', visible: true },
    { key: 'battery_soc', label: 'Battery SOC', color: '#66BB6A', visible: true, yAxis: 'right' },
    { key: 'ups_load_power', label: 'UPS Load Power', color: '#7986CB', visible: true },
    { key: 'smart_load_power', label: 'Smart Load Power', color: '#4DB6AC', visible: true },
    { key: 'home_load_power', label: 'Home Load Power', color: '#cc2121', visible: true },
    { key: 'total_load_power', label: 'Total Load Power', color: '#c40202', visible: true },
    { key: 'home_load_sunsync', label: 'Home Load SunSync', color: '#c42c02', visible: true },
    { key: 'combined_load_node_sunsync', label: 'Combined Load Node SunSync', color: '#673AB7', visible: true },
    { key: 'combined_load_node', label: 'Combined Load Node', color: '#3F51B5', visible: true },
    { key: 'zappi_node', label: 'Zappi Node', color: '#2196F3', visible: true }
];

// EV Status configuration
const evStatusConfig = {
    key: 'car_node_connection',
    label: 'EV Connection Status',
    visible: true,
    yAxis: 'evStatus'
};

// Modify the document ready handler to retrieve and apply saved state
document.addEventListener('DOMContentLoaded', function() {

    ////console.log('Script loaded');
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
        const baseHeight = 900; // Original design height
        
        // Calculate adjustment factor for responsive scaling
        const adjustmentFactor = Math.max(0, (baseWidth - containerWidth) / baseWidth);
        
        // Set base viewBox
        svg.setAttribute('viewBox', `0 0 ${baseWidth} ${baseHeight}`);
       // //console.log(containerWidth);

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
    


    //--------------------------------

    // Add jQuery check
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        alert('jQuery is required but not loaded. Please check your network connection and refresh the page.');
        return;
    }
    
    ////console.log('DOM content loaded');
    
    // Test if Bootstrap datepicker is loaded
    if (typeof $.fn.datepicker === 'undefined') {
        console.error('Bootstrap datepicker is not loaded!');
        alert('Calendar library is not loaded. Please check your network connection and refresh the page.');
    } else {
        //console.log('Bootstrap datepicker is loaded correctly');
    }
    
// Initial adjustment
adjustViewBox();
    
    // Adjust on window resize
    window.addEventListener('resize', adjustViewBox);

    //--------------------------------
/*
    // Initialize chart
    const chart = initEnergyFlowChart();
    
*/
    // Get saved state
    const savedTimeframe = localStorage.getItem(TIMEFRAME_KEY) || '24h';
    const savedCustomDate = localStorage.getItem(CUSTOM_DATE_KEY);
    
    // Initialize with saved timeframe
    ////console.log('Restoring saved state:', savedTimeframe, savedCustomDate);
    
    // First, fetch available dates to ensure datepicker is ready
    fetchAvailableDates().then(() => {
        // After dates are loaded, apply saved state
        if (savedTimeframe === 'custom' && savedCustomDate) {
            // If we had a custom date saved, switch to custom view
            activateTimeframe('custom');
            // Show the datepicker
            document.getElementById('customDatePicker').style.display = 'block';
            // Set the date in the input
            $('#datePicker').val(savedCustomDate);
            // Load data for that date
            fetchEnergyFlowData('custom', savedCustomDate);
        } else {
            // Otherwise load the saved timeframe
            activateTimeframe(savedTimeframe);
           // fetchEnergyFlowData(savedTimeframe);
        }
    });

    // Add event listeners for timeframe buttons
    document.querySelectorAll('.timeframe-btn').forEach(button => {
        button.addEventListener('click', function() {
            const timeframe = this.dataset.timeframe;
            //  //console.log('Timeframe button clicked:', timeframe);
            
            // Save selected timeframe to localStorage
            localStorage.setItem(TIMEFRAME_KEY, timeframe);
            
            // If switching away from custom, clear the saved custom date
            if (timeframe !== 'custom') {
                localStorage.removeItem(CUSTOM_DATE_KEY);
            }
            
            // Activate the timeframe
            activateTimeframe(timeframe);
            
            // Show/hide date picker based on selection
            const datePicker = document.getElementById('customDatePicker');
            if (datePicker) {
                if (timeframe === 'custom') {
                    datePicker.style.display = 'block';
                } else {
                    datePicker.style.display = 'none';
                    fetchEnergyFlowData(timeframe);
                }
            } else {
                console.error('Custom date picker container not found');
                fetchEnergyFlowData(timeframe);
            }
        });
    });

    // Auto-refresh data every minute (only for non-custom timeframes)
    setInterval(() => {
        const activeButton = document.querySelector('.timeframe-btn.active');
        if (activeButton && activeButton.dataset.timeframe !== 'custom') {
            const activeTimeframe = activeButton.dataset.timeframe;
            fetchEnergyFlowData(activeTimeframe);
        }
    }, 60000);
});

// Helper function to activate a timeframe button
function activateTimeframe(timeframe) {
    // Update active button state
    document.querySelectorAll('.timeframe-btn').forEach(btn => {
        if (btn.dataset.timeframe === timeframe) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Show/hide date picker
    const datePicker = document.getElementById('customDatePicker');
    if (datePicker) {
        datePicker.style.display = timeframe === 'custom' ? 'block' : 'none';
    }
}

// Modify fetchAvailableDates to return a Promise for better control flow
async function fetchAvailableDates() {
    try {
        const response = await fetch('api/energy-flow/available-dates');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get dates from API
        availableDates = await response.json();
        ////console.log('Available dates:', availableDates);
        
        if (!Array.isArray(availableDates) || availableDates.length === 0) {
            console.warn('No available dates returned from API');
            const datePickerError = document.getElementById('datePickerError');
            if (datePickerError) {
                datePickerError.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        No data available for any dates. Please check that you have energy flow data in your database.
                    </div>
                `;
            }
            return Promise.resolve(); // Return resolved promise even on error
        }
        
        // Initialize Bootstrap Datepicker
        const $datePicker = $('#datePicker');
        ////console.log('Initializing datepicker on element:', $datePicker.length > 0 ? 'Found' : 'Not found');
        
        // Destroy previous instance if exists
        if ($datePicker.data('datepicker')) {
            $datePicker.datepicker('destroy');
        }
        
        // Initialize Bootstrap Datepicker
        $datePicker.datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            clearBtn: false,
            maxViewMode: 2,
            beforeShowDay: function(date) {
                // Convert date to yyyy-mm-dd format
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const dateString = `${year}-${month}-${day}`;
                
                // Check if this date is in our available dates
                const available = availableDates.includes(dateString);
                
                // Return configuration object with disabled status for unavailable dates
                return {
                    enabled: available,
                    classes: available ? 'available' : 'disabled',
                    tooltip: available ? 'Data available' : 'No data available'
                };
            }
        });
        
        // Set default date to the most recent date
        if (availableDates.length > 0) {
            const latestDate = availableDates[availableDates.length - 1];
            $datePicker.datepicker('setDate', new Date(latestDate));
            ////console.log('Set default date to:', latestDate);
        }
        
        // Add a direct DOM manipulation after calendar is shown to forcibly style disabled dates
        $datePicker.on('show', function() {
            setTimeout(function() {
                // Apply styles directly to all disabled days
                $('.datepicker .day.disabled').css({
                    'color': '#999999',
                    'background-color': '#f0f0f0',
                    'opacity': '0.6'
                });
                
                // Make available dates stand out
                $('.datepicker .day:not(.disabled)').css({
                    'font-weight': 'bold',
                    'color': '#3b82f6'
                });
                
                $('table.table-condensed').css({
                    'width': '200px',
                    'text-align': 'center'
                });
            }, 10);
        });
        
        // Handle apply button click - update to save the selected date
        $('#applyDateBtn').off('click').on('click', function() {
            const selectedDate = $datePicker.val();
            //  //console.log('Selected date:', selectedDate);
            if (selectedDate && availableDates.includes(selectedDate)) {
                // Save the custom date to localStorage
                localStorage.setItem(CUSTOM_DATE_KEY, selectedDate);
                fetchD3EnergyFlowData('custom', selectedDate);
            } else {
                const datePickerError = document.getElementById('datePickerError');
                if (datePickerError) {
                    datePickerError.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Please select a valid date with available data.
                        </div>
                    `;
                }
            }
        });
        
        return Promise.resolve();
    } catch (error) {
        console.error('Error fetching available dates:', error);
        // Show error message to user
        const datePickerError = document.getElementById('datePickerError');
        if (datePickerError) {
            datePickerError.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Error loading available dates: ${error.message}
                </div>
            `;
        }
        return Promise.resolve(); // Return resolved promise even on error
    }
}

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



// Modify the initEnergyFlowChart function to restore and save dataset visibility
function initD3EnergyFlowChart() {
    const ctx = document.getElementById('energyFlowChart').getContext('2d');
    
    // Default datasets configuration
    const datasets = [
        {
            label: 'PV1 Power',
            borderColor: '#FFD700',
            backgroundColor: 'rgba(255, 215, 0, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'PV2 Power',
            borderColor: '#FFA500',
            backgroundColor: 'rgba(255, 165, 0, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Total PV Power',
            borderColor: '#FF8C00',
            backgroundColor: 'rgba(255, 140, 0, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Grid Power',
            borderColor: '#FF6B6B',
            backgroundColor: 'rgba(255, 107, 107, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Battery Power',
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Battery SOC',
            borderColor: '#66BB6A',
            backgroundColor: 'rgba(102, 187, 106, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4,
            yAxisID: 'soc'
        },
        {
            label: 'UPS Load Power',
            borderColor: '#7986CB',
            backgroundColor: 'rgba(121, 134, 203, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Smart Load Power',
            borderColor: '#4DB6AC',
            backgroundColor: 'rgba(77, 182, 172, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Home Load Power',
            borderColor: '#F06292',
            backgroundColor: 'rgba(240, 98, 146, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Total Load Power',
            borderColor: '#E91E63',
            backgroundColor: 'rgba(233, 30, 99, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Home Load SunSync',
            borderColor: '#9C27B0',
            backgroundColor: 'rgba(156, 39, 176, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Combined Load Node SunSync',
            borderColor: '#673AB7',
            backgroundColor: 'rgba(103, 58, 183, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Combined Load Node',
            borderColor: '#3F51B5',
            backgroundColor: 'rgba(63, 81, 181, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'Zappi Node',
            borderColor: '#2196F3',
            backgroundColor: 'rgba(33, 150, 243, 0.1)',
            data: [],
            pointRadius: 0,
            borderWidth: 2,
            tension: 0.4
        },
        {
            label: 'EV Connection Status',
            borderColor: '#9E9E9E',
            backgroundColor: 'rgba(158, 158, 158, 0.1)',
            data: [],
            borderWidth: 2,
            tension: 0,
            showLine: false,
            yAxisID: 'evStatus',
            pointStyle: function(context) {
                const dataPoint = context.raw;
                if (!dataPoint) return 'circle';
                return dataPoint.pointStyle || 'circle';
            },
            pointBackgroundColor: function(context) {
                const dataPoint = context.raw;
                if (!dataPoint) return '#aaaaaa';
                return dataPoint.backgroundColor || '#aaaaaa';
            },
            pointBorderColor: function(context) {
                const dataPoint = context.raw;
                if (!dataPoint) return '#aaaaaa';
                return dataPoint.borderColor || '#aaaaaa';
            },
            pointRadius: function(context) {
                const dataPoint = context.raw;
                if (!dataPoint) return 6;
                return dataPoint.radius || 6;
            }
        }
    ];
    
    // Apply saved visibility states to datasets
    let savedVisibility;
    try {
        savedVisibility = JSON.parse(localStorage.getItem(DATASET_VISIBILITY_KEY));
    } catch (e) {
        savedVisibility = null;
    }
    
    if (savedVisibility && Array.isArray(savedVisibility)) {
        // Apply saved visibility to datasets if available
        savedVisibility.forEach((visible, index) => {
            if (index < datasets.length) {
                datasets[index].hidden = !visible;
            }
        });
        //  //console.log('Restored dataset visibility from localStorage');
    }
    /*
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            elements: {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 0
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'minute',
                        displayFormats: {
                            minute: 'MMM d, HH:mm',
                            hour: 'MMM d, HH:mm',
                            day: 'MMM d',
                            week: 'MMM d',
                            month: 'MMM yyyy'
                        },
                        tooltipFormat: 'MMM d, yyyy HH:mm'
                    },
                    title: {
                        display: true,
                        text: 'Date & Time'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Power (W)'
                    }
                },
                soc: {
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Battery SOC (%)'
                    },
                    min: 0,
                    max: 100,
                    grid: {
                        drawOnChartArea: false
                    }
                },
                evStatus: {
                    position: 'right',
                    title: {
                        display: false
                    },
                    min: 0,
                    max: 1,
                    grid: {
                        drawOnChartArea: false,
                        display: false
                    },
                    ticks: {
                        display: false
                    },
                    border: {
                        display: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title: function(context) {
                            // Format the date nicely
                            if (context[0]) {
                                const date = new Date(context[0].parsed.x);
                                return date.toLocaleString();
                            }
                            return '';
                        },
                        label: function(context) {
                            // Format battery SOC with % and EV status with text
                            if (context.dataset.label === 'Battery SOC') {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            } else if (context.dataset.label === 'EV Connection Status') {
                                // Get the raw item to access the status code
                                const datapoint = context.raw;
                                const statusMap = {
                                    'A': 'EV Disconnected',
                                    'B1': 'EV Connected',
                                    'B2': 'Waiting for EV',
                                    'C1': 'EV Ready to Charge',
                                    'C2': 'Charging',
                                    'F': 'Fault'
                                };
                                const statusText = statusMap[datapoint.status] || 'Unknown';
                                return `EV Status: ${datapoint.status} (${statusText})`;
                            } else {
                                return context.dataset.label + ': ' + context.parsed.y + 'W';
                            }
                        }
                    }
                },
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    },
                    onClick: function(e, legendItem, legend) {
                        // Run the original legend click handler
                        Chart.defaults.plugins.legend.onClick.call(this, e, legendItem, legend);
                        
                        // Save current visibility state to localStorage
                        const visibilityState = chart.data.datasets.map(dataset => !dataset.hidden);
                        localStorage.setItem(DATASET_VISIBILITY_KEY, JSON.stringify(visibilityState));
                        //   //console.log('Saved dataset visibility to localStorage');
                    }
                }
            }
        });
    */
    
    // After chart setup
    createD3Legend();

    return chart;
}
/*
async function fetchEnergyFlowData(timeframe = '24h', customDate = null) {
    try {
        let url = `api/energy-flow/history?timeframe=${timeframe}`;
        if (customDate) {
            url = `api/energy-flow/history?date=${customDate}`;
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        // Update chart data
        const chart = Chart.getChart('energyFlowChart');
        if (chart) {
            chart.data.datasets[0].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.pv1_power,
                label: 'PV1 Power',
                hidden: true
            }));
            chart.data.datasets[1].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.pv2_power,
                label: 'PV2 Power',
                hidden: true
            }));
            chart.data.datasets[2].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.total_pv_power,
                label: 'Total PV Power' 
            }));
            chart.data.datasets[3].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.grid_power,
                label: 'Grid Power'
            }));
            chart.data.datasets[4].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.battery_power,
                label: 'Battery Power'
            }));
            chart.data.datasets[5].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.battery_soc,
                label: 'Battery SOC'
            }));
            chart.data.datasets[6].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.ups_load_power,
                label: 'UPS Load Power'
            }));
            chart.data.datasets[7].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.smart_load_power,
                label: 'Smart Load Power'
            }));
            chart.data.datasets[8].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.home_load_power,
                label: 'Home Load Power'
            }));
            chart.data.datasets[9].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.total_load_power,
                label: 'Total Load Power'
            }));
            chart.data.datasets[10].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.home_load_sunsync,
                label: 'Home Load SunSync'
            }));
            chart.data.datasets[11].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.combined_load_node_sunsync,
                label: 'Combined Load Node SunSync'
            }));
            chart.data.datasets[12].data = data.map(item => ({
                x: new Date(item.created_at),
                y: item.combined_load_node,
                label: 'Combined Load Node'
            }));
            chart.data.datasets[13].data = data.map(item => ({
                x: new Date(item.created_at),
                    y: item.zappi_node,
                label: 'Zappi Node'
            }));
            
            // Add EV Connection Status
            chart.data.datasets[14] = {
                label: 'EV Connection Status',
                borderWidth: 2,
                tension: 0,
                showLine: false,
                yAxisID: 'evStatus',
                // Add a custom draw function for the legend
                pointStyle: function(context) {
                    const dataPoint = context.raw;
                    if (!dataPoint) return 'circle';
                    return dataPoint.pointStyle || 'circle';
                },
                pointBackgroundColor: function(context) {
                    const dataPoint = context.raw;
                    if (!dataPoint) return '#aaaaaa';
                    return dataPoint.backgroundColor || '#aaaaaa';
                },
                pointBorderColor: function(context) {
                    const dataPoint = context.raw;
                    if (!dataPoint) return '#aaaaaa';
                    return dataPoint.borderColor || '#aaaaaa';
                },
                pointRadius: function(context) {
                    const dataPoint = context.raw;
                    if (!dataPoint) return 6;
                    return dataPoint.radius || 6;
                },
                data: data.map(item => {
                    // Map status codes to numeric values for display
                    let statusValue = 0;
                    let status = item.car_node_connection || 'Unknown';
                    /* 
                    switch(status) {
                        case 'A': statusValue = 1; break; // Disconnected
                        case 'B1': statusValue = 1; break; // Connected 
                        case 'B2': statusValue = 1; break; // Waiting
                        case 'C1': statusValue = 1; break; // Ready
                        case 'C2': statusValue = 6; break; // Charging
                        case 'F': statusValue = 6; break; // Fault
                        default: statusValue = 0;
                    } 
                    
                    // Force all status values to a fixed position at the bottom of the graph
                    // We use 0 to position them at the very bottom
                    statusValue = 0;
                    
                    // Get color and style based on the status
                    let config = {
                        x: new Date(item.created_at),
                        y: statusValue,
                        status: status
                    };
                    
                    // Set specific styles based on the status
                    switch(status) {
                        case 'A':
                            config.backgroundColor = '#aaaaaa'; 
                            config.borderColor = '#888888';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                            break;
                        case 'B1':
                            config.backgroundColor = '#90caf9'; 
                            config.borderColor = '#2196F3';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                            break;
                        case 'B2':
                            config.backgroundColor = '#9c27b0'; 
                            config.borderColor = '#7B1FA2';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                            break;
                        case 'C1':
                            config.backgroundColor = '#ffc354'; 
                            config.borderColor = '#FF9800';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                            break;
                        case 'C2':
                            config.backgroundColor = '#66bb6a'; 
                            config.borderColor = '#388E3C';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                            break;
                        case 'F':
                            config.backgroundColor = '#f44336'; 
                            config.borderColor = '#D32F2F';
                            config.pointStyle = 'circle';
                            config.radius = 5;
                            break;
                        default:
                            config.backgroundColor = '#aaaaaa';
                            config.borderColor = '#888888';
                            config.pointStyle = 'circle';
                            config.radius = 3;
                    }
                    
                    return config;
                })
            };
            
            chart.update();
        }
    } catch (error) {
        console.error('Error fetching energy flow data:', error);
    }
}
*/
// Test function to create sample data for debugging
function createTestData() {
    const now = new Date();
    const testData = [];
    
    for (let i = 0; i < 24; i++) {
        const time = new Date(now.getTime() - (23 - i) * 60 * 60 * 1000);
        testData.push({
            created_at: time.toISOString(),
            pv1_power: Math.random() * 1000 + 500,
            pv2_power: Math.random() * 800 + 400,
            total_pv_power: Math.random() * 1500 + 800,
            grid_power: Math.random() * 2000 - 1000,
            battery_power: Math.random() * 1000 - 500,
            battery_soc: Math.random() * 100,
            ups_load_power: Math.random() * 500 + 100,
            smart_load_power: Math.random() * 300 + 50,
            home_load_power: Math.random() * 800 + 200,
            total_load_power: Math.random() * 1200 + 400,
            home_load_sunsync: Math.random() * 600 + 150,
            combined_load_node_sunsync: Math.random() * 1000 + 300,
            combined_load_node: Math.random() * 1200 + 400,
            zappi_node: Math.random() * 2000 + 500,
            car_node_connection: ['A', 'B1', 'B2', 'C1', 'C2'][Math.floor(Math.random() * 5)]
        });
    }
    
    return testData;
}

// D3.js Chart Functions
function createD3EnergyFlowChart() {
    //console.log('Creating D3 energy flow chart...');
    const container = document.getElementById('d3-energy-flow-chart');
    if (!container) {
        console.error('D3 chart container not found');
        return;
    }
    //console.log('Container found, dimensions:', container.clientWidth, 'x', container.clientHeight);

    // Clear existing content
    container.innerHTML = '';
    //console.log('Container cleared, creating SVG...');

    // Set up responsive dimensions
    const margin = { top: 20, right: 80, bottom: 60, left: 60 };
    let width = container.clientWidth - margin.left - margin.right;
    let height = container.clientHeight - margin.top - margin.bottom;
    
    // Responsive minimum dimensions based on screen size
    const screenWidth = window.innerWidth;
    if (screenWidth <= 360) {
        // Very small screens
        margin.left = 40;
        margin.right = 40;
        margin.top = 15;
        margin.bottom = 40;
        width = Math.max(200, container.clientWidth - margin.left - margin.right);
        height = Math.max(150, container.clientHeight - margin.top - margin.bottom);
    } else if (screenWidth <= 480) {
        // Small screens
        margin.left = 50;
        margin.right = 50;
        margin.top = 15;
        margin.bottom = 50;
        width = Math.max(250, container.clientWidth - margin.left - margin.right);
        height = Math.max(180, container.clientHeight - margin.top - margin.bottom);
    } else if (screenWidth <= 768) {
        // Medium screens
        margin.left = 55;
        margin.right = 60;
        margin.top = 18;
        margin.bottom = 55;
        width = Math.max(300, container.clientWidth - margin.left - margin.right);
        height = Math.max(200, container.clientHeight - margin.top - margin.bottom);
    } else {
        // Large screens
        width = Math.max(300, container.clientWidth - margin.left - margin.right);
        height = Math.max(200, container.clientHeight - margin.top - margin.bottom);
    }
    height = 500;
    //console.log('Chart dimensions:', width, 'x', height);

    // Create SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom)
        .append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    // Create scales
    const xScale = d3.scaleTime()
        .range([0, width]);

    const yScale = d3.scaleLinear()
        .range([height, 0]);

    const yScaleRight = d3.scaleLinear()
        .range([height, 0]);

    const yScaleEvStatus = d3.scaleLinear()
        .domain([0, 1])
        .range([height, height - 20]);

    // Create axes
    const xAxis = d3.axisBottom(xScale)
        .tickFormat(d3.timeFormat('%H:%M'));

    const yAxis = d3.axisLeft(yScale)
        .tickFormat(d => `${d}W`);

    const yAxisRight = d3.axisRight(yScaleRight)
        .tickFormat(d => `${d}%`);

    // Add axes to SVG
    svg.append('g')
        .attr('class', 'x-axis')
        .attr('transform', `translate(0,${height})`)
        .call(xAxis);

    svg.append('g')
        .attr('class', 'y-axis')
        .call(yAxis);

    svg.append('g')
        .attr('class', 'y-axis-right')
        .attr('transform', `translate(${width},0)`)
        .call(yAxisRight);

    // Add axis labels
    svg.append('text')
        .attr('class', 'x-label')
        .attr('text-anchor', 'middle')
        .attr('x', width / 2)
        .attr('y', height + 40)
        .text('Date & Time');

    svg.append('text')
        .attr('class', 'y-label')
        .attr('text-anchor', 'middle')
        .attr('transform', 'rotate(-90)')
        .attr('x', -height / 2)
        .attr('y', -40)
        .text('Power (W)');

    svg.append('text')
        .attr('class', 'y-label-right')
        .attr('text-anchor', 'middle')
        .attr('transform', 'rotate(90)')
        .attr('x', height / 2)
        .attr('y', width + 40)
        .text('Battery SOC (%)');

    // Create line generators
    const line = d3.line()
        .x(d => xScale(new Date(d.created_at)))
        .y(d => yScale(d.value))
        .curve(d3.curveMonotoneX);

    const lineRight = d3.line()
        .x(d => xScale(new Date(d.created_at)))
        .y(d => yScaleRight(d.value))
        .curve(d3.curveMonotoneX);

    // Create tooltip
    const tooltip = d3.select('body')
        .append('div')
        .attr('class', 'tooltip')
        .style('position', 'absolute')
        .style('background', 'rgba(0, 0, 0, 0.9)')
        .style('color', 'white')
        .style('padding', '8px 12px')
        .style('border-radius', '4px')
        .style('font-size', '12px')
        .style('pointer-events', 'none')
        .style('opacity', 0)
        .style('z-index', '10000')
        .style('box-shadow', '0 2px 8px rgba(0, 0, 0, 0.3)')
        .style('transition', 'opacity 0.2s ease');

    // Create chart object
    d3Chart = {
        svg,
        xScale,
        yScale,
        yScaleRight,
        yScaleEvStatus,
        line,
        lineRight,
        tooltip,
        width,
        height,
        margin
    };

    // Load saved visibility states
    loadDatasetVisibility();

    // Create legend
    createD3Legend();

    // Load initial data
   // //console.log('D3 chart setup complete, loading data...');
    fetchD3EnergyFlowData();
}

function createD3Legend() {
    ////console.log('Creating D3 legend...');
    const legendContainer = document.getElementById('d3-chart-legend');
    legendContainer.className = 'chart-legend d-flex flex-wrap mb-3';
    if (!legendContainer) {
        //console.error('Legend container not found');
        return;
    }

    legendContainer.innerHTML = '';
    ////console.log('Legend container cleared, adding items...');

    // Add regular datasets
    ////console.log('Adding', d3Datasets.length, 'dataset legend items...');
    d3Datasets.forEach((dataset, index) => {
        ////console.log('Creating legend item for:', dataset.label);
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item d-flex align-items-center me-3 mb-0';
        legendItem.style.cursor = 'pointer';
        legendItem.style.opacity = dataset.visible ? '1' : '0.3';

        legendItem.innerHTML = `
            <div class="legend-color me-1" style="width: 10px; height: 2px; background-color: ${dataset.color};"></div>
            <span class="legend-label" style="font-size: 10px;">${dataset.label}</span>
        `;

        legendItem.addEventListener('click', () => {
            dataset.visible = !dataset.visible;
            legendItem.style.opacity = dataset.visible ? '1' : '0.3';
            updateD3Chart();
            saveDatasetVisibility();
        });

        legendContainer.appendChild(legendItem);
        ////console.log('Added legend item:', dataset.label, 'Total items:', legendContainer.children.length);
    });
    
    ////console.log('Legend creation complete. Total items:', legendContainer.children.length);

    // Add EV status legend
    const evLegendItem = document.createElement('div');
    evLegendItem.className = 'legend-item d-flex align-items-center me-3 mb-0';
    evLegendItem.style.cursor = 'pointer';
    evLegendItem.style.opacity = evStatusConfig.visible ? '1' : '0.3';

    evLegendItem.innerHTML = `
        <div class="legend-color me-1" style="width: 10px; height: 2px; background-color: #9E9E9E;"></div>
        <span class="legend-label" style="font-size: 10px;">${evStatusConfig.label}</span>
    `;

    evLegendItem.addEventListener('click', () => {
        evStatusConfig.visible = !evStatusConfig.visible;
        evLegendItem.style.opacity = evStatusConfig.visible ? '1' : '0.3';
        updateD3Chart();
        saveDatasetVisibility();
    });

    legendContainer.appendChild(evLegendItem);
    ////console.log('Added EV status legend item. Total items:', legendContainer.children.length);
}

function loadDatasetVisibility() {
    try {
        const savedVisibility = JSON.parse(localStorage.getItem(DATASET_VISIBILITY_KEY));
        if (savedVisibility && Array.isArray(savedVisibility)) {
            savedVisibility.forEach((visible, index) => {
                if (index < d3Datasets.length) {
                    d3Datasets[index].visible = visible;
                }
            });
        }
    } catch (e) {
        console.warn('Could not load dataset visibility from localStorage');
    }
}

function saveDatasetVisibility() {
    try {
        const visibility = d3Datasets.map(dataset => dataset.visible);
        localStorage.setItem(DATASET_VISIBILITY_KEY, JSON.stringify(visibility));
    } catch (e) {
        console.warn('Could not save dataset visibility to localStorage');
    }
}

async function fetchD3EnergyFlowData(timeframe = '24h', customDate = null) {
    try {
        //console.log('Fetching D3 energy flow data for timeframe:', timeframe, 'customDate:', customDate);
        let url = `api/energy-flow/history?timeframe=${timeframe}`;
        if (customDate) {
            url = `api/energy-flow/history?date=${customDate}`;
        }
        
        //console.log('Fetching from URL:', url);
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        //console.log('Data received:', data.length, 'records');
        d3Data = data;
        d3Timeframe = timeframe;
        
        updateD3Chart();
    } catch (error) {
        console.error('Error fetching energy flow data:', error);
        //console.log('Using test data instead...');
        d3Data = createTestData();
        d3Timeframe = timeframe;
        updateD3Chart();
    }
}

function percentile(arr, p) {
    if (!arr.length) return 0;
    const sorted = arr.slice().sort((a, b) => a - b);
    const idx = (sorted.length - 1) * p;
    const lower = Math.floor(idx);
    const upper = Math.ceil(idx);
    if (lower === upper) return sorted[lower];
    return sorted[lower] + (sorted[upper] - sorted[lower]) * (idx - lower);
}

function updateD3Chart() {
    if (!d3Chart) {
     //   console.error('D3 chart not initialized');
        return;
    }
    
    if (!d3Data.length) {
      //  //console.log('No data available for chart');
        return;
    }
    
   // //console.log('Updating D3 chart with', d3Data.length, 'data points');
    ////console.log('Sample data point:', d3Data[0]);
    ////console.log('Available keys in data:', Object.keys(d3Data[0] || {}));
    
    // Check for invalid data
    const invalidData = d3Data.filter(d => !d.created_at || isNaN(new Date(d.created_at).getTime()));
    if (invalidData.length > 0) {
        //console.warn('Found invalid data points:', invalidData.length);
        //console.warn('First invalid point:', invalidData[0]);
    }

    const { svg, xScale, yScale, yScaleRight, yScaleEvStatus, line, lineRight, tooltip } = d3Chart;

    // Clear existing chart elements
    svg.selectAll('.line-path').remove();
    svg.selectAll('.ev-point').remove();
    svg.selectAll('.no-data-text').remove();

    // Update scales
    const timeExtent = d3.extent(d3Data, d => new Date(d.created_at));
    ////console.log('Time extent:', timeExtent);
    
    // Validate time extent
    if (!timeExtent[0] || !timeExtent[1] || isNaN(timeExtent[0].getTime()) || isNaN(timeExtent[1].getTime())) {
        console.error('Invalid time extent:', timeExtent);
        svg.append('text')
            .attr('class', 'no-data-text')
            .attr('x', d3Chart.width / 2)
            .attr('y', d3Chart.height / 2.5)
            .attr('text-anchor', 'middle')
            .attr('fill', '#888')
            .attr('font-size', '12px')
           
            .text('Invalid time data');
        return;
    }
    
    xScale.domain(timeExtent);

    // Left y-axis: all visible, non-SOC datasets
    let leftPowerValues = [];
    //console.log('Checking visible datasets for power values:');
    d3Datasets.forEach(dataset => {
        //console.log(`Dataset ${dataset.key} (${dataset.label}): visible=${dataset.visible}`);
        if (dataset.visible && dataset.key !== 'battery_soc') {
            let validValues = 0;
            d3Data.forEach(d => {
                const value = typeof d[dataset.key] === 'string' ? parseFloat(d[dataset.key]) : d[dataset.key];
                if (value !== null && value !== undefined && !isNaN(value)) {
                    leftPowerValues.push(value);
                    validValues++;
                }
            });
            ////console.log(`  - Found ${validValues} valid values for ${dataset.key}`);
        }
    });
    
    ////console.log('Left power values:', leftPowerValues);
    ////console.log('Number of valid power values:', leftPowerValues.length);
    
    let leftMin, leftMax;
    
    if (leftPowerValues.length === 0) {
        //console.warn('No valid power values found, using default range');
        leftMin = -5000;
        leftMax = 5000;
    } else {
        leftMin = percentile(leftPowerValues, 1.01);
        leftMax = percentile(leftPowerValues, 1.99);
        
        // Fallback to min/max if percentiles are too close or invalid
        if (!isFinite(leftMin) || !isFinite(leftMax) || Math.abs(leftMax - leftMin) < 1e-3) {
            leftMin = Math.min(...leftPowerValues);
            leftMax = Math.max(...leftPowerValues);
            ////console.log('Percentiles too close, using min/max:', leftMin, leftMax);
        }
        
        // Ensure a minimum range
        if (leftMax - leftMin < 10) {
            const mid = (leftMax + leftMin) / 2;
            leftMin = mid - 2500;
            leftMax = mid + 2500;
            ////console.log('Range too small, expanding to 10W:', leftMin, leftMax);
        }
        
        let leftPadding = (leftMax - leftMin) * 0.1;
        if (!isFinite(leftPadding) || leftPadding === 0) leftPadding = 500;
        leftMin = leftMin - leftPadding - 250;
        leftMax = leftMax + leftPadding;
        
        // If all values are positive, don't let min go below zero
        if (leftMin > 0 && leftPowerValues.every(v => v >= 0)) leftMin = 0;
    }
    
    if (!isFinite(leftMin) || !isFinite(leftMax)) {
        console.warn('Invalid y-axis domain, showing no data message.');
        ////console.log('Final leftMin:', leftMin, 'leftMax:', leftMax);
        svg.append('text')
            .attr('class', 'no-data-text')
            .attr('x', d3Chart.width / 2)
            .attr('y', d3Chart.height / 2)
            .attr('text-anchor', 'middle')
            .attr('fill', '#888')
            .attr('font-size', '12px')
            .text('No data to display');
        return;
    }
    yScale.domain([leftMin, leftMax]);
    ////console.log('Y-scale domain:', [leftMin, leftMax]);

    // Right y-axis: only battery_soc
    let socValues = d3Data.map(d => {
        const value = typeof d.battery_soc === 'string' ? parseFloat(d.battery_soc) : d.battery_soc;
        return value;
    }).filter(v => v !== null && v !== undefined && !isNaN(v));
    ////console.log('SOC values:', socValues);
    ////console.log('Number of valid SOC values:', socValues.length);
    
    let socMin, socMax;
    
    if (socValues.length === 0) {
        //console.warn('No valid SOC values found, using default range');
        socMin = 0;
        socMax = 100;
    } else {
        socMin = percentile(socValues, 0.01);
        socMax = percentile(socValues, 0.99);
        
        if (!isFinite(socMin) || !isFinite(socMax) || Math.abs(socMax - socMin) < 1e-3) {
            socMin = Math.min(...socValues);
            socMax = Math.max(...socValues);
            ////console.log('SOC percentiles too close, using min/max:', socMin, socMax);
        }
        
        if (socMax - socMin < 5) {
            const mid = (socMax + socMin) / 2;
            socMin = mid - 2.5;
            socMax = mid + 2.5;
            ////console.log('SOC range too small, expanding to 5%:', socMin, socMax);
        }
        
        let socPadding = (socMax - socMin) * 0.1;
        if (!isFinite(socPadding) || socPadding === 0) socPadding = 5;
        socMin = 0;//socMin - socPadding - 20;
        socMax = 100; //socMax + socPadding;
        
        if (socMin < 0) socMin = 0;
        if (socMax > 100) socMax = 100;
    }
    
    yScaleRight.domain([socMin, socMax]);
    ////console.log('Y-scale-right domain:', [socMin, socMax]);

    // Update axes
    svg.select('.x-axis').call(d3.axisBottom(xScale).tickFormat(d3.timeFormat('%H:%M')));
    svg.select('.y-axis').call(d3.axisLeft(yScale).tickFormat(d => `${d/1000} kW`));
    svg.select('.y-axis-right').call(d3.axisRight(yScaleRight).tickFormat(d => `${d}%`));

    // Draw lines for visible datasets
    let anyLineDrawn = false;
    d3Datasets.forEach(dataset => {
        if (!dataset.visible) return;

        const lineData = d3Data.map(d => ({
            created_at: d.created_at,
            value: typeof d[dataset.key] === 'string' ? parseFloat(d[dataset.key]) : d[dataset.key]
        })).filter(d => {
            // Filter out invalid data points
            return d.value !== null && 
                   d.value !== undefined && 
                   !isNaN(d.value) && 
                   d.created_at !== null && 
                   d.created_at !== undefined;
        });

        if (lineData.length === 0) {
           // //console.log(`No valid data for dataset: ${dataset.key}`);
            return;
        }
        
        ////console.log(`Drawing line for ${dataset.key} with ${lineData.length} points`);
        anyLineDrawn = true;

        // Use right y-axis only for battery_soc
        const lineGenerator = dataset.key === 'battery_soc' ? lineRight : line;

        // Validate that the line generator will produce valid coordinates
        const testPoint = lineData[0];
        if (testPoint) {
            const testX = xScale(new Date(testPoint.created_at));
            const testY = dataset.key === 'battery_soc' ? yScaleRight(testPoint.value) : yScale(testPoint.value);
            
            if (isNaN(testX) || isNaN(testY)) {
                //console.error(`Invalid coordinates for ${dataset.key}: x=${testX}, y=${testY}`);
                //console.error(`Test point:`, testPoint);
                return;
            }
        }

        // Draw area under battery_power line down to zero
        if (dataset.key === 'battery_power' || dataset.key === 'grid_power' || dataset.key === 'total_pv_power' || dataset.key === 'total_load_power') {
            const area = d3.area()
                .x(d => xScale(new Date(d.created_at)))
                .y0(yScale(0))
                .y1(d => yScale(d.value))
                .curve(d3.curveMonotoneX);

            svg.append('path')
                .datum(lineData)
                .attr('class', 'line-path')
                .attr('fill', dataset.color)
                .attr('fill-opacity', 0.1)
                .attr('stroke', 'none')
                .attr('d', area);
        }

        // Draw the line for all datasets (including battery_power)
        svg.append('path')
            .datum(lineData)
            .attr('class', 'line-path')
            .attr('fill', 'none')
            .attr('stroke', dataset.color)
            .attr('stroke-width', 2)
            .attr('d', lineGenerator)
            .on('mouseover', function(event, d) {
                d3.select(this).attr('stroke-width', 3);
                tooltip.style('opacity', 1);
            })
            .on('mousemove', function(event, d) {
                // Find the closest data point to the mouse
                const mouse = d3.pointer(event, this);
                const xm = xScale.invert(mouse[0]);
                // Find the closest data point by time
                const bisect = d3.bisector(d => new Date(d.created_at)).left;
                const i = bisect(lineData, xm);
                let d2 = lineData[i];
                if (i > 0 && i < lineData.length) {
                    const d0 = lineData[i - 1];
                    d2 = xm - new Date(d0.created_at) > new Date(d2.created_at) - xm ? d2 : d0;
                }
                if (d2) {
                    tooltip.html(`
                        <strong>${dataset.label}</strong><br/>
                        Time: ${d3.timeFormat('%Y-%m-%d %H:%M:%S')(new Date(d2.created_at))}<br/>
                        Value: ${dataset.key === 'battery_soc' ? d2.value.toFixed(1) + '%' : d2.value.toFixed(0) + 'W'}
                    `)
                    .style('left', (event.pageX + 10) + 'px')
                    .style('top', (event.pageY - 28) + 'px')
                    .style('opacity', 1);
                }
            })
            .on('mouseout', function() {
                d3.select(this).attr('stroke-width', 2);
                tooltip.style('opacity', 0);
            });
    });

    if (!anyLineDrawn) {
        svg.append('text')
            .attr('class', 'no-data-text')
            .attr('x', d3Chart.width / 2)
            .attr('y', d3Chart.height / 2)
            .attr('text-anchor', 'middle')
            .attr('fill', '#888')
            .attr('font-size', '18px')
            .text('No data to display');
    }

    // Draw EV status points
    if (evStatusConfig.visible) {
        const evStatusColors = {
            'A': '#aaaaaa',  // Disconnected
            'B1': '#90caf9', // Connected
            'B2': '#9c27b0', // Waiting
            'C1': '#ffc354', // Ready
            'C2': '#66bb6a', // Charging
            'F': '#f44336'   // Fault
        };

        const evData = d3Data.filter(d => d.car_node_connection);

        svg.selectAll('.ev-point')
            .data(evData)
            .enter()
            .append('circle')
            .attr('class', 'ev-point')
            .attr('cx', d => xScale(new Date(d.created_at)))
            .attr('cy', d => yScaleEvStatus(0.5))
            .attr('r', d => d.car_node_connection === 'F' ? 5 : 3)
            .attr('fill', d => evStatusColors[d.car_node_connection] || '#aaaaaa')
            .attr('stroke', 'none')
            .attr('stroke-width', 0)
            .on('mouseover', function(event, d) {
                d3.select(this).attr('r', d.car_node_connection === 'F' ? 7 : 5);
                tooltip.style('opacity', 1);
            })
            .on('mousemove', function(event, d) {
                tooltip.html(`
                    <strong>${evStatusConfig.label}</strong><br/>
                    Time: ${d3.timeFormat('%Y-%m-%d %H:%M:%S')(new Date(d.created_at))}<br/>
                    Status: ${d.car_node_connection}
                `)
                .style('left', (event.pageX + 10) + 'px')
                .style('top', (event.pageY - 10) + 'px');
            })
            .on('mouseout', function(event, d) {
                d3.select(this).attr('r', d.car_node_connection === 'F' ? 5 : 3);
                tooltip.style('opacity', 0);
            });
    }
}

// Initialize D3 chart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    //console.log('DOM loaded, initializing D3 chart...');
    
    // Check if D3.js is loaded
    if (typeof d3 === 'undefined') {
        console.error('D3.js is not loaded!');
        return;
    }
    
    // Initialize D3 chart instead of Chart.js
    try {
        createD3EnergyFlowChart();
        //console.log('D3 chart initialized successfully');
    } catch (error) {
        console.error('Error initializing D3 chart:', error);
    }
    
    // Add resize handler for responsive chart
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            //console.log('Window resized, recreating D3 chart...');
            if (d3Chart && d3Data.length > 0) {
                createD3EnergyFlowChart();
            }
        }, 250); // Debounce resize events
    });
    
    // Handle orientation change on mobile devices
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            //console.log('Orientation changed, recreating D3 chart...');
            if (d3Chart && d3Data.length > 0) {
                createD3EnergyFlowChart();
            }
        }, 500); // Wait for orientation change to complete
    });
    
    // Update timeframe buttons to use D3
    document.querySelectorAll('.timeframe-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const timeframe = this.getAttribute('data-timeframe');
            //console.log('Timeframe button clicked:', timeframe);
            activateTimeframe(timeframe);
            fetchD3EnergyFlowData(timeframe);
        });
    });

    // Update auto-refresh to use D3
    const autoRefreshToggle = document.getElementById('autoRefreshToggle');
    if (autoRefreshToggle) {
        autoRefreshToggle.addEventListener('change', function() {
            setAutoRefresh(this.checked);
        });
    }

    // Set up auto-refresh for D3 chart
    setInterval(() => {
        if (autoRefreshToggle && autoRefreshToggle.checked) {
            //console.log('Auto-refreshing D3 chart...');
            fetchD3EnergyFlowData(d3Timeframe);
        }
    }, 60000); // Refresh every minute

    // Initialize date picker for D3 chart
    //console.log('Initializing date picker...');
    fetchAvailableDates().then(() => {
        //console.log('Available dates loaded for date picker');
    }).catch(error => {
        console.error('Error loading available dates:', error);
    });
});