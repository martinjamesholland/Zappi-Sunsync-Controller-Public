document.addEventListener('DOMContentLoaded', function() {
    // Function to get cleaned power value from element
    function getPowerValue(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return 0;
        
        const text = element.textContent;
        return parseFloat(text.replace(/[^\d.-]/g, '')) || 0;
    }
    
    // Function to get flow flag value
    function getFlowFlag(flagId) {
        const element = document.getElementById(flagId);
        return element && element.value === 'true';
    }
    
    // Function to set animation visibility and direction based on flow
    function setPathAnimation(pathId, imageNode, isActive, isReversed = false) {
        const path = document.getElementById(pathId);
        const image = imageNode;
        
        if (path && image) {
            if (isActive) {
                path.style.opacity = '1';
                image.style.display = 'block';
                
                // Special handling for grid path
                if (pathId === 'path-grid-to-inverter') {
                    const gridPower = getPowerValue('grid-power-value');
                    const toGrid = getFlowFlag('toGrid-flag');
                    const gridTo = getFlowFlag('gridTo-flag');
                    
                    // Determine if power is flowing to or from grid
                    const isExporting = gridPower > 0 || toGrid;
                    const isImporting = gridPower < 0 || gridTo;
                    
                    if (isExporting) {
                        // Power flowing to grid - path from grid to inverter
                        path.setAttribute('d', 'M575,350 575,120 345,120');
                        image.removeAttribute('transform');
                    } else if (isImporting) {
                        // Power flowing from grid - path from inverter to grid
                        path.setAttribute('d', 'M345,120 575,120 575,350');
                        image.setAttribute('transform', 'rotate(180)');
                    }
                }
                // Special handling for battery path
                else if (pathId === 'path-inverter-to-battery') {
                    const batteryPower = getPowerValue('battery-power-value');
                    const toBat = getFlowFlag('toBat-flag');
                    const batTo = getFlowFlag('batTo-flag');
                    
                    if (batTo) {
                        // Battery is feeding the inverter
                        path.setAttribute('d', 'M345,120 245,350');
                        image.setAttribute('transform', 'rotate(180)');
                    } else if (toBat) {
                        // Inverter is charging the battery
                        path.setAttribute('d', 'M245,350 345,120');
                        image.removeAttribute('transform');
                    } else {
                        // Default to power value direction
                        if (batteryPower > 0) {
                            path.setAttribute('d', 'M245,350 345,120');
                            image.removeAttribute('transform');
                        } else {
                            path.setAttribute('d', 'M345,120 245,350');
                            image.setAttribute('transform', 'rotate(180)');
                        }
                    }
                }
                // For other paths, just handle rotation
                else {
                    if (isReversed) {
                        image.setAttribute('transform', 'rotate(180)');
                    } else {
                        image.removeAttribute('transform');
                    }
                }
            } else {
                path.style.opacity = '0.3';
                image.style.display = 'none';
            }
        }
    }
    
    // Function to update flow paths based on direction flags
    function updateFlowPaths() {
        // Get power values
        const pv1Power = getPowerValue('solar-power-value');
        const pv2Power = getPowerValue('solar2-power-value');
        const totalPvPower = pv1Power + pv2Power;
        const gridPower = getPowerValue('grid-power-value');
        const batteryPower = getPowerValue('battery-power-value');
        const upsLoadPower = getPowerValue('ups-load-value');
        const smartLoadPower = getPowerValue('smart-load-value');
        const homeLoadPower = getPowerValue('home-load-value');
        
        // Get flow direction flags
        const pvTo = getFlowFlag('pvTo-flag');
        const toLoad = getFlowFlag('toLoad-flag');
        const toSmartLoad = getFlowFlag('toSmartLoad-flag');
        const toUpsLoad = getFlowFlag('toUpsLoad-flag');
        const toHomeLoad = getFlowFlag('toHomeLoad-flag');
        const toGrid = getFlowFlag('toGrid-flag');
        const toBat = getFlowFlag('toBat-flag');
        const batTo = getFlowFlag('batTo-flag');
        const gridTo = getFlowFlag('gridTo-flag');
        const genTo = getFlowFlag('genTo-flag');
        const existsGrid = getFlowFlag('existsGrid-flag');

        // Get all SVG paths and their associated images
        const solarPath = document.getElementById('path-solar-to-inverter');
        const solarImage = solarPath ? solarPath.nextElementSibling : null;
        const gridPath = document.getElementById('path-grid-to-inverter');
        const gridImage = gridPath ? gridPath.nextElementSibling : null;
        const batteryPath = document.getElementById('path-inverter-to-battery');
        const batteryImage = batteryPath ? batteryPath.nextElementSibling : null;
        const upsPath = document.getElementById('path-inverter-to-ups');
        const upsImage = upsPath ? upsPath.nextElementSibling : null;
        const smartPath = document.getElementById('path-inverter-to-smart');
        const smartImage = smartPath ? smartPath.nextElementSibling : null;
        const homePath = document.getElementById('path-inverter-to-home');
        const homeImage = homePath ? homePath.nextElementSibling : null;
        
        // Solar2 path (if exists)
        const solar2Path = document.getElementById('path-solar2-to-inverter');
        const solar2Image = solar2Path ? solar2Path.nextElementSibling : null;
        
        // Activate paths based on power values & flow flags
        setPathAnimation('path-solar-to-inverter', solarImage, pv1Power > 0 || pvTo);
        
        if (solar2Path && solar2Image) {
            setPathAnimation('path-solar2-to-inverter', solar2Image, pv2Power > 0 || pvTo);
        }
        
        // Grid connections - Fixed logic with arrow direction
        if (existsGrid) {
            const isExporting = gridPower > 0 || toGrid;
            const isImporting = gridPower < 0 || gridTo;
            
            if (isExporting) {
                // Power flowing to grid - arrows point towards grid
                setPathAnimation('path-grid-to-inverter', gridImage, true, true);
            } else if (isImporting) {
                // Power flowing from grid - arrows point towards inverter
                setPathAnimation('path-grid-to-inverter', gridImage, true, false);
            } else {
                setPathAnimation('path-grid-to-inverter', gridImage, false);
            }
        } else {
            setPathAnimation('path-grid-to-inverter', gridImage, false);
        }
        
        // Battery connections
        if (batteryPower !== 0 || toBat || batTo) {
            if (batTo) {
                // Battery is feeding the inverter
                setPathAnimation('path-inverter-to-battery', batteryImage, true, false);
            } else if (toBat) {
                // Inverter is charging the battery
                setPathAnimation('path-inverter-to-battery', batteryImage, true, true);
            } else {
                // Default to power value direction
                setPathAnimation('path-inverter-to-battery', batteryImage, true, batteryPower > 0);
            }
        } else {
            setPathAnimation('path-inverter-to-battery', batteryImage, false);
        }
        
        // Load connections
        setPathAnimation('path-inverter-to-ups', upsImage, upsLoadPower > 0 || toUpsLoad);
        setPathAnimation('path-inverter-to-smart', smartImage, smartLoadPower > 0 || toSmartLoad);
        setPathAnimation('path-inverter-to-home', homeImage, homeLoadPower > 0 || toHomeLoad);
    }
    
    // Initial update
    updateFlowPaths();
    
    // Update when values change
    const refreshBtn = document.getElementById('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // The page will reload, but we could implement AJAX update here
        });
    }
}); 