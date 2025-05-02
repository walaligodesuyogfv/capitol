/**
 * Dashboard Analytics
 * This script handles the rendering of charts and analytics data on the admin dashboard
 */

// Initialize dashboard analytics when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts with monthly data by default
    initializeAnalyticsCharts('monthly');
    
    // Set up event listeners for chart period buttons
    document.getElementById('weeklyChartBtn').addEventListener('click', function() {
        initializeAnalyticsCharts('weekly');
    });
    
    document.getElementById('monthlyChartBtn').addEventListener('click', function() {
        initializeAnalyticsCharts('monthly');
    });
    
    document.getElementById('quarterlyChartBtn').addEventListener('click', function() {
        initializeAnalyticsCharts('quarterly');
    });
    
    // Initialize PO and PAR forecast charts
    initializeForecastCharts();
    
    // Set up refresh listeners
    document.getElementById('refreshPoPredictionBtn').addEventListener('click', function() {
        refreshPOForecast();
    });
    
    document.getElementById('refreshParPredictionBtn').addEventListener('click', function() {
        refreshPARForecast();
    });

    // Setup listeners for ML tracking data
    setupMLTrackingListeners();
});

/**
 * Set up event listeners for ML tracking
 */
function setupMLTrackingListeners() {
    // Track inventory data when item is saved
    if (document.getElementById('saveItemBtn')) {
        document.getElementById('saveItemBtn').addEventListener('click', function() {
            // Get tracking data from the form
            const trackingData = getInventoryTrackingData();
            
            // Set the tracking data to the hidden input
            if (trackingData && document.getElementById('inventoryTrackingData')) {
                document.getElementById('inventoryTrackingData').value = JSON.stringify(trackingData);
                
                // Add listener for successful form submission
                const inventoryForm = document.getElementById('addInventoryForm');
                if (inventoryForm) {
                    // Use a MutationObserver to detect when the modal is hidden (which happens after successful form submission)
                    const inventoryModal = document.getElementById('addInventoryModal');
                    if (inventoryModal) {
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'class' && !inventoryModal.classList.contains('show')) {
                                    // Modal was hidden, refresh the analytics chart
                                    setTimeout(function() {
                                        refreshAnalyticsAfterAdd('inventory');
                                    }, 1000);
                                    observer.disconnect(); // Stop observing once we've handled the event
                                }
                            });
                        });
                        
                        observer.observe(inventoryModal, { attributes: true });
                    }
                }
            }
        });
    }
    
    // Track PO data when PO is saved
    if (document.getElementById('savePoBtn')) {
        document.getElementById('savePoBtn').addEventListener('click', function() {
            // Get tracking data from the form
            const trackingData = getPOTrackingData();
            
            // Set the tracking data to the hidden input
            if (trackingData && document.getElementById('poTrackingData')) {
                document.getElementById('poTrackingData').value = JSON.stringify(trackingData);
                
                // Add listener for successful form submission
                const poModal = document.getElementById('addPOModal');
                if (poModal) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'class' && !poModal.classList.contains('show')) {
                                // Modal was hidden, refresh the analytics chart
                                setTimeout(function() {
                                    refreshAnalyticsAfterAdd('po');
                                }, 1000);
                                observer.disconnect(); // Stop observing once we've handled the event
                            }
                        });
                    });
                    
                    observer.observe(poModal, { attributes: true });
                }
            }
        });
    }
    
    // Track PAR data when PAR is saved
    if (document.getElementById('saveParBtn')) {
        document.getElementById('saveParBtn').addEventListener('click', function() {
            // Get tracking data from the form
            const trackingData = getPARTrackingData();
            
            // Set the tracking data to the hidden input
            if (trackingData && document.getElementById('parTrackingData')) {
                document.getElementById('parTrackingData').value = JSON.stringify(trackingData);
                
                // Add listener for successful form submission
                const parModal = document.getElementById('addPARModal');
                if (parModal) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'class' && !parModal.classList.contains('show')) {
                                // Modal was hidden, refresh the analytics chart
                                setTimeout(function() {
                                    refreshAnalyticsAfterAdd('par');
                                }, 1000);
                                observer.disconnect(); // Stop observing once we've handled the event
                            }
                        });
                    });
                    
                    observer.observe(parModal, { attributes: true });
                }
            }
        });
    }
}

/**
 * Refresh analytics charts after adding a new item
 * @param {string} type - The type of item added (inventory, po, par)
 */
function refreshAnalyticsAfterAdd(type) {
    console.log(`Refreshing analytics after adding ${type}`);
    
    // Check if we're on the dashboard section
    if (!document.querySelector('.dashboard-section')) {
        // If we're not on the dashboard, we need to fetch the current period
        // from localStorage or use monthly as default
        const currentPeriod = localStorage.getItem('analytics_period') || 'monthly';
        
        // Store the current section
        const currentSection = document.querySelector('.inventory-section:not(.d-none)') ? 'inventory' :
                              document.querySelector('.po-section:not(.d-none)') ? 'po' :
                              document.querySelector('.par-section:not(.d-none)') ? 'par' : '';
        
        // Update the analytics in the background
        fetch(`get_analytics_data.php?period=${currentPeriod}&update=true`)
            .then(response => response.json())
            .then(data => {
                console.log(`Analytics data refreshed successfully after ${type} addition`);
                // Store the update time in localStorage
                localStorage.setItem('analytics_last_update', new Date().toISOString());
            })
            .catch(error => {
                console.error(`Error refreshing analytics data after ${type} addition:`, error);
            });
    } else {
        // We're on the dashboard, so refresh the chart with current period
        const currentPeriod = document.getElementById('weeklyChartBtn').classList.contains('active') ? 'weekly' :
                             document.getElementById('quarterlyChartBtn').classList.contains('active') ? 'quarterly' : 'monthly';
        
        // Store the current period in localStorage
        localStorage.setItem('analytics_period', currentPeriod);
        
        // Refresh the chart
        initializeAnalyticsCharts(currentPeriod);
    }
}

/**
 * Get inventory tracking data from the form
 * @returns {Object} The inventory data to track
 */
function getInventoryTrackingData() {
    const form = document.getElementById('addInventoryForm');
    if (!form) return null;
    
    return {
        item_code: document.getElementById('itemID')?.value || '',
        item_name: document.getElementById('itemName')?.value || '',
        brand_model: document.getElementById('Brand/model')?.value || '',
        serial_number: document.getElementById('serialNumber')?.value || '',
        purchase_date: document.getElementById('purchaseDate')?.value || '',
        warranty_expiration: document.getElementById('warrantyDate')?.value || '',
        assigned_to: document.getElementById('assignedTo')?.value || '',
        location: document.getElementById('location')?.value || '',
        condition: document.getElementById('condition')?.value || '',
        notes: document.getElementById('notes')?.value || '',
        tracking_date: new Date().toISOString()
    };
}

/**
 * Get PO tracking data from the form
 * @returns {Object} The PO data to track
 */
function getPOTrackingData() {
    const form = document.getElementById('poForm');
    if (!form) return null;
    
    // Get total amount
    const totalAmount = document.getElementById('totalAmount')?.value || '0.00';
    
    return {
        po_no: document.getElementById('poNo')?.value || '',
        supplier_name: document.getElementById('supplier')?.value || '',
        po_date: document.getElementById('poDate')?.value || '',
        ref_no: document.getElementById('refNo')?.value || '',
        total_amount: totalAmount,
        tracking_date: new Date().toISOString()
    };
}

/**
 * Get PAR tracking data from the form
 * @returns {Object} The PAR data to track
 */
function getPARTrackingData() {
    const form = document.getElementById('parForm');
    if (!form) return null;
    
    // Get total amount
    const totalAmount = document.getElementById('parTotalAmount')?.value || '0.00';
    
    return {
        par_no: document.getElementById('par_no')?.value || '',
        entity_name: document.getElementById('entity_name')?.value || '',
        date_acquired: document.getElementById('date_acquired')?.value || '',
        received_by: document.getElementById('received_by')?.value || '',
        position: document.getElementById('position')?.value || '',
        department: document.getElementById('department')?.value || '',
        total_amount: totalAmount,
        tracking_date: new Date().toISOString()
    };
}

/**
 * Send tracking data to the backend for ML processing
 * @param {string} type - The type of data (inventory, po, par)
 * @param {Object} data - The data to track
 */
function sendTrackingData(type, data) {
    fetch('track_ml_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            type: type,
            data: data
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log(`${type} data tracked successfully:`, result);
        } else {
            console.warn(`Error tracking ${type} data:`, result);
        }
    })
    .catch(error => {
        console.error(`Error sending ${type} tracking data:`, error);
    });
}

/**
 * Initialize the performance analytics chart with the specified period data
 * @param {string} period - The period to display (weekly, monthly, quarterly)
 */
function initializeAnalyticsCharts(period) {
    // Make AJAX request to get analytics data
    fetch(`get_analytics_data.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update the main analytics chart
            updateAnalyticsChart(data);
            
            // Mark the active button
            document.querySelectorAll('#weeklyChartBtn, #monthlyChartBtn, #quarterlyChartBtn')
                .forEach(btn => btn.classList.remove('active', 'btn-primary'));
            
            document.getElementById(`${period}ChartBtn`).classList.add('active', 'btn-primary');
        })
        .catch(error => {
            console.error('Error loading analytics data:', error);
        });
}

/**
 * Update the main analytics chart with the provided data
 * @param {Object} data - The analytics data for the chart
 */
function updateAnalyticsChart(data) {
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    
    // Check if chart already exists and destroy it
    if (window.analyticsChart instanceof Chart) {
        window.analyticsChart.destroy();
    }
    
    // Create the new chart
    window.analyticsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.months,
            datasets: [
                {
                    label: 'Inventory',
                    data: data.inventory_data,
                    backgroundColor: 'rgba(84, 112, 198, 0.7)',
                    borderColor: 'rgba(84, 112, 198, 1)',
                    borderWidth: 1
                },
                {
                    label: 'PO',
                    data: data.po_data,
                    backgroundColor: 'rgba(145, 204, 117, 0.7)',
                    borderColor: 'rgba(145, 204, 117, 1)',
                    borderWidth: 1
                },
                {
                    label: 'PAR',
                    data: data.par_data,
                    backgroundColor: 'rgba(250, 200, 88, 0.7)',
                    borderColor: 'rgba(250, 200, 88, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Performance Analytics',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Time Period'
                    }
                }
            }
        }
    });
    
    // If forecast data is available, update the forecast charts
    if (data.forecast) {
        updatePOForecastChart(data.forecast);
        updatePARForecastChart(data.forecast);
    }
}

/**
 * Initialize the forecast charts
 */
function initializeForecastCharts() {
    // Initialize PO Forecast Chart
    const poCtx = document.getElementById('poForecastChart').getContext('2d');
    window.poForecastChart = new Chart(poCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'PO Forecast',
                data: [],
                borderColor: 'rgba(145, 204, 117, 1)',
                backgroundColor: 'rgba(145, 204, 117, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(145, 204, 117, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });
    
    // Initialize PAR Forecast Chart
    const parCtx = document.getElementById('parForecastChart').getContext('2d');
    window.parForecastChart = new Chart(parCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'PAR Forecast',
                data: [],
                borderColor: 'rgba(250, 200, 88, 1)',
                backgroundColor: 'rgba(250, 200, 88, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(250, 200, 88, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    });
}

/**
 * Update the PO forecast chart with new data
 * @param {Object} forecastData - The forecast data object
 */
function updatePOForecastChart(forecastData) {
    // Update PO Forecast Chart
    window.poForecastChart.data.labels = forecastData.months;
    window.poForecastChart.data.datasets[0].data = forecastData.po.data;
    window.poForecastChart.update();
    
    // Update the forecast values in the UI
    document.getElementById('poNextMonthPrediction').textContent = forecastData.po.next_month_prediction;
    document.getElementById('poAccuracyScore').textContent = forecastData.po.accuracy + '%';
}

/**
 * Update the PAR forecast chart with new data
 * @param {Object} forecastData - The forecast data object
 */
function updatePARForecastChart(forecastData) {
    // Update PAR Forecast Chart
    window.parForecastChart.data.labels = forecastData.months;
    window.parForecastChart.data.datasets[0].data = forecastData.par.data;
    window.parForecastChart.update();
    
    // Update the forecast values in the UI
    document.getElementById('parNextMonthPrediction').textContent = forecastData.par.next_month_prediction;
    document.getElementById('parAccuracyScore').textContent = forecastData.par.accuracy + '%';
}

/**
 * Refresh the PO forecast data
 */
function refreshPOForecast() {
    // Show loading state
    document.getElementById('poNextMonthPrediction').textContent = 'Loading...';
    document.getElementById('poAccuracyScore').textContent = 'Loading...';
    
    // Call backend to get fresh forecast data
    fetch('fetch_po_forecast.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the chart with fresh data
                const ctx = document.getElementById('poForecastChart').getContext('2d');
                if (window.poForecastChart instanceof Chart) {
                    window.poForecastChart.destroy();
                }
                
                window.poForecastChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.months,
                        datasets: [{
                            label: 'PO Forecast',
                            data: data.forecast,
                            borderColor: 'rgba(145, 204, 117, 1)',
                            backgroundColor: 'rgba(145, 204, 117, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(145, 204, 117, 1)',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        }
                    }
                });
                
                // Update prediction values
                document.getElementById('poNextMonthPrediction').textContent = data.next_month_prediction;
                document.getElementById('poAccuracyScore').textContent = data.accuracy + '%';
            } else {
                console.error('Error refreshing PO forecast:', data.message);
                document.getElementById('poNextMonthPrediction').textContent = 'Error';
                document.getElementById('poAccuracyScore').textContent = 'N/A';
            }
        })
        .catch(error => {
            console.error('Error fetching PO forecast:', error);
            document.getElementById('poNextMonthPrediction').textContent = 'Error';
            document.getElementById('poAccuracyScore').textContent = 'N/A';
        });
}

/**
 * Refresh the PAR forecast data
 */
function refreshPARForecast() {
    // Show loading state
    document.getElementById('parNextMonthPrediction').textContent = 'Loading...';
    document.getElementById('parAccuracyScore').textContent = 'Loading...';
    
    // Call backend to get fresh forecast data
    fetch('fetch_par_forecast.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the chart with fresh data
                const ctx = document.getElementById('parForecastChart').getContext('2d');
                if (window.parForecastChart instanceof Chart) {
                    window.parForecastChart.destroy();
                }
                
                window.parForecastChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.months,
                        datasets: [{
                            label: 'PAR Forecast',
                            data: data.forecast,
                            borderColor: 'rgba(250, 200, 88, 1)',
                            backgroundColor: 'rgba(250, 200, 88, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(250, 200, 88, 1)',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        }
                    }
                });
                
                // Update prediction values
                document.getElementById('parNextMonthPrediction').textContent = data.next_month_prediction;
                document.getElementById('parAccuracyScore').textContent = data.accuracy + '%';
            } else {
                console.error('Error refreshing PAR forecast:', data.message);
                document.getElementById('parNextMonthPrediction').textContent = 'Error';
                document.getElementById('parAccuracyScore').textContent = 'N/A';
            }
        })
        .catch(error => {
            console.error('Error fetching PAR forecast:', error);
            document.getElementById('parNextMonthPrediction').textContent = 'Error';
            document.getElementById('parAccuracyScore').textContent = 'N/A';
        });
}

/**
 * Initialize ML predictions dashboard
 */
function initializeMLDashboard(data) {
    const rootElement = document.getElementById('mlDashboardRoot');
    if (!rootElement) return;
    
    if (!data || !data.ml_predictions) {
        rootElement.innerHTML = '<div class="p-4 text-center text-muted">No ML prediction data available</div>';
        return;
    }
    
    const predictions = data.ml_predictions;
    
    // Create dashboard content
    let dashboardHtml = `
        <div class="p-3">
            <div class="row g-3">
                <!-- Health Score -->
                <div class="col-md-3">
                    <div class="p-3 rounded bg-light h-100">
                        <h6 class="text-primary mb-2">Inventory Health Score</h6>
                        <div class="d-flex align-items-center">
                            <div class="circular-progress me-3" style="--value: ${predictions.inventory_health}; --size: 80px; --thickness: 8px;">
                                <div class="inner-circle">
                                    <span class="progress-value">${predictions.inventory_health}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1">Overall Health</div>
                                <div class="small text-muted">Based on conditions and maintenance history</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Optimization -->
                <div class="col-md-3">
                    <div class="p-3 rounded bg-light h-100">
                        <h6 class="text-primary mb-2">Budget Optimization</h6>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <div class="display-5 fw-bold text-success">${predictions.budget_optimization}%</div>
                                <div class="small text-muted">Potential savings</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PO-PAR Ratio -->
                <div class="col-md-3">
                    <div class="p-3 rounded bg-light h-100">
                        <h6 class="text-primary mb-2">PO-PAR Ratio</h6>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <div class="display-5 fw-bold ${predictions.po_par_ratio.health === 'Good' ? 'text-success' : 'text-warning'}">${predictions.po_par_ratio.value}</div>
                                <div class="small text-muted">Status: ${predictions.po_par_ratio.health}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Predictions -->
                <div class="col-md-3">
                    <div class="p-3 rounded bg-light h-100">
                        <h6 class="text-primary mb-2">Maintenance Needed</h6>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <div class="display-5 fw-bold text-warning">${predictions.maintenance_predictions.length}</div>
                                <div class="small text-muted">Items need attention</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Details -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="p-3 rounded bg-light">
                        <h6 class="text-primary mb-3">Maintenance Predictions</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item ID</th>
                                        <th>Name</th>
                                        <th>Probability</th>
                                        <th>Days Until</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${predictions.maintenance_predictions.map(item => `
                                        <tr>
                                            <td>${item.item_id}</td>
                                            <td>${item.name}</td>
                                            <td>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-${item.probability > 80 ? 'danger' : 'warning'}" 
                                                         role="progressbar" 
                                                         style="width: ${item.probability}%" 
                                                         aria-valuenow="${item.probability}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="small">${item.probability}%</span>
                                            </td>
                                            <td>${item.days_until} days</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory Suggestions -->
                <div class="col-md-6">
                    <div class="p-3 rounded bg-light">
                        <h6 class="text-primary mb-3">Inventory Suggestions</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Suggested Action</th>
                                        <th>Confidence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${predictions.inventory_suggestions.map(item => `
                                        <tr>
                                            <td>${item.category}</td>
                                            <td>
                                                <span class="badge bg-${
                                                    item.action === 'Increase' ? 'success' : 
                                                    item.action === 'Decrease' ? 'danger' : 'info'
                                                }">${item.action}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar" 
                                                         role="progressbar" 
                                                         style="width: ${item.confidence}%" 
                                                         aria-valuenow="${item.confidence}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="small">${item.confidence}%</span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    rootElement.innerHTML = dashboardHtml;
}

// Load ML Dashboard when data is available
document.addEventListener('DOMContentLoaded', function() {
    fetch('get_analytics_data.php?period=monthly')
        .then(response => response.json())
        .then(data => {
            initializeMLDashboard(data);
        })
        .catch(error => {
            console.error('Error loading ML dashboard data:', error);
        });
}); 