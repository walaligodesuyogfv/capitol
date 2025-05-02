document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts when the reports section is shown
    document.getElementById('Reports-link').addEventListener('click', function() {
        initializeReportsSection();
    });

    // Initialize the report UI elements
    function initializeReportsSection() {
        // Initialize all charts
        initializeCharts();
        
        // Set up event listeners for filters
        setupFilters();
        
        // Set up report type switching
        setupReportTypeSwitching();
        
        // Load initial sample data
        loadSampleData();
        
        // Set up Python prediction
        setupPythonPrediction();
        
        // Set up export buttons
        setupExportButtons();
    }

    // Set up the report type switching
    function setupReportTypeSwitching() {
        const reportType = document.getElementById('reportType');
        
        reportType.addEventListener('change', function() {
            // Hide all report tables
            document.querySelectorAll('.report-table').forEach(table => {
                table.classList.add('d-none');
            });
            
            // Get the selected report type
            const selectedType = reportType.value;
            
            // Show the selected report table
            if (selectedType === 'summary') {
                document.getElementById('summaryReport').classList.remove('d-none');
            } else if (selectedType === 'par_detailed') {
                document.getElementById('parDetailedReport').classList.remove('d-none');
            } else if (selectedType === 'po_detailed') {
                document.getElementById('poDetailedReport').classList.remove('d-none');
            } else if (selectedType === 'inventory_detailed') {
                document.getElementById('inventoryDetailedReport').classList.remove('d-none');
            } else if (selectedType === 'audit_logs') {
                document.getElementById('auditLogsReport').classList.remove('d-none');
            }
            
            // Get current filters
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const department = document.getElementById('departmentFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            // Prepare filters
            const filters = {
                fromDate: dateFrom,
                toDate: dateTo,
                department: department,
                status: status
            };
            
            // Fetch data for the selected report type
            fetchReportData(selectedType, 1, 10, filters, function(data) {
                // Update the table based on report type
                let tableBody;
                
                switch(selectedType) {
                    case 'summary':
                        tableBody = document.getElementById('summaryTableBody');
                        tableBody.innerHTML = '';
                        
                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${item.category || 'N/A'}</td>
                                    <td>${item.department || 'N/A'}</td>
                                    <td>${item.count || 0}</td>
                                    <td>₱${(item.value || 0).toLocaleString()}</td>
                                    <td>${item.last_updated || item.lastUpdated || 'N/A'}</td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td colspan="5" class="text-center">No summary data available</td>
                            `;
                            tableBody.appendChild(row);
                        }
                        break;
                        
                    case 'par_detailed':
                        tableBody = document.getElementById('parDetailedTableBody');
                        tableBody.innerHTML = '';
                        
                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${item.par_no || 'N/A'}</td>
                                    <td>${item.item_name || 'N/A'}</td>
                                    <td>${item.description || 'N/A'}</td>
                                    <td>${item.quantity || item.qty || 0}</td>
                                    <td>₱${(item.unit_cost || item.unitCost || 0).toLocaleString()}</td>
                                    <td>₱${(item.total_cost || item.totalCost || 0).toLocaleString()}</td>
                                    <td>${item.employee || item.received_by || 'N/A'}</td>
                                    <td>${item.department || 'N/A'}</td>
                                    <td>${item.date_issued || item.date_acquired || 'N/A'}</td>
                                    <td><span class="badge bg-success">${item.status || 'Active'}</span></td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td colspan="10" class="text-center">No PAR data available</td>
                            `;
                            tableBody.appendChild(row);
                        }
                        break;
                        
                    case 'po_detailed':
                        tableBody = document.getElementById('poDetailedTableBody');
                        tableBody.innerHTML = '';
                        
                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${item.po_no || 'N/A'}</td>
                                    <td>${item.supplier || item.supplier_name || 'N/A'}</td>
                                    <td>${item.item_description || 'N/A'}</td>
                                    <td>${item.quantity || 0}</td>
                                    <td>₱${(item.unit_price || 0).toLocaleString()}</td>
                                    <td>₱${(item.total_amount || 0).toLocaleString()}</td>
                                    <td>${item.date_ordered || item.po_date || 'N/A'}</td>
                                    <td>${item.date_delivered || item.delivery_date || 'N/A'}</td>
                                    <td><span class="badge ${item.status === 'Completed' ? 'bg-success' : 'bg-warning'}">${item.status || 'Pending'}</span></td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td colspan="9" class="text-center">No PO data available</td>
                            `;
                            tableBody.appendChild(row);
                        }
                        break;
                        
                    // Add cases for other report types as needed
                }
                
                // Update the page info
                document.getElementById('reportPageInfo').textContent = `Showing 1-${data ? data.length : 0} of ${data ? data.length : 0} records`;
                
                // Update charts for the report type
                updateChartsForReportType(selectedType);
            });
        });
    }

    // Initialize all charts
    function initializeCharts() {
        // Assets by Department Chart
        const assetsByDepartmentCtx = document.getElementById('assetsByDepartmentChart').getContext('2d');
        window.assetsByDepartmentChart = new Chart(assetsByDepartmentCtx, {
            type: 'bar',
            data: {
                labels: ['IT', 'HR', 'Finance', 'Operations'],
                datasets: [{
                    label: 'Total Asset Value',
                    data: [25000, 15000, 18000, 12000],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value (₱)'
                        }
                    }
                }
            }
        });

        // PAR vs PO Chart
        const parVsPoCtx = document.getElementById('parVsPoChart').getContext('2d');
        window.parVsPoChart = new Chart(parVsPoCtx, {
            type: 'pie',
            data: {
                labels: ['PAR Total', 'PO Total'],
                datasets: [{
                    label: 'Total Value',
                    data: [38000, 52000],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Monthly Trend Chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        window.monthlyTrendChart = new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'PAR Values',
                    data: [12000, 18000, 15000, 22000, 19000, 25000],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }, {
                    label: 'PO Values',
                    data: [15000, 21000, 18000, 25000, 22000, 30000],
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value (₱)'
                        }
                    }
                }
            }
        });

        // Asset Count vs Total Value Chart
        const assetValueCtx = document.getElementById('assetValueChart').getContext('2d');
        window.assetValueChart = new Chart(assetValueCtx, {
            type: 'bar',
            data: {
                labels: ['Computers', 'Peripherals', 'Network Equipment', 'Monitors', 'Mobile Devices', 'Other'],
                datasets: [
                    {
                        label: 'Asset Count',
                        data: [10, 5, 8, 12, 6, 3],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Total Value',
                        data: [25000, 12000, 18000, 15000, 9000, 5000],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Count'
                        },
                        position: 'left'
                    },
                    y1: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Value (₱)'
                        },
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        // If inventory data exists from previous additions, update charts
        if (window.inventoryReportsData && typeof updateAssetValueChart === 'function') {
            setTimeout(() => {
                updateAssetValueChart();
            }, 500);
        }
    }

    // Switch chart types
    window.toggleChartType = function(chartId) {
        const chart = window[chartId];
        
        if (!chart) return;
        
        if (chart.config.type === 'bar') {
            chart.config.type = 'line';
        } else if (chart.config.type === 'line') {
            chart.config.type = 'pie';
        } else if (chart.config.type === 'pie') {
            chart.config.type = 'doughnut';
        } else {
            chart.config.type = 'bar';
        }
        
        chart.update();
    };

    // Set up the filters
    function setupFilters() {
        // Apply filters when they change
        const filtersToWatch = ['departmentFilter', 'statusFilter', 'dateFrom', 'dateTo'];
        
        filtersToWatch.forEach(filterId => {
            const filterElement = document.getElementById(filterId);
            if (filterElement) {
                filterElement.addEventListener('change', function() {
                    applyFilters();
                });
            }
        });

        // Set up confidence level display
        const confidenceLevel = document.getElementById('confidenceLevel');
        const confidenceLevelDisplay = document.getElementById('confidenceLevelDisplay');
        
        if (confidenceLevel && confidenceLevelDisplay) {
            confidenceLevel.addEventListener('input', function() {
                confidenceLevelDisplay.textContent = Math.round(confidenceLevel.value * 100) + '%';
            });
        }
        
        // Set up chart type and time interval changes
        const chartTypeSelect = document.getElementById('chartType');
        const timeIntervalSelect = document.getElementById('timeInterval');
        
        if (chartTypeSelect) {
            chartTypeSelect.addEventListener('change', function() {
                updateAllChartTypes(chartTypeSelect.value);
            });
        }
        
        if (timeIntervalSelect) {
            timeIntervalSelect.addEventListener('change', function() {
                updateAllCharts();
            });
        }
    }
    
    // Function to apply current filters and refresh data
    function applyFilters() {
        // Get current filters
        const reportType = document.getElementById('reportType').value;
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const department = document.getElementById('departmentFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        // Prepare filters object
        const filters = {
            fromDate: dateFrom,
            toDate: dateTo,
            department: department,
            status: status
        };
        
        // Show a loading spinner
        Swal.fire({
            title: 'Applying Filters',
            html: 'Please wait...',
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
                
                // Fetch data with filters applied
                fetchReportData(reportType, 1, 10, filters, function(data) {
                    // Update the appropriate table based on report type
                    let tableBody;
                    
                    switch(reportType) {
                        case 'summary':
                            tableBody = document.getElementById('summaryTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.category || 'N/A'}</td>
                                        <td>${item.department || 'N/A'}</td>
                                        <td>${item.count || 0}</td>
                                        <td>₱${(item.value || 0).toLocaleString()}</td>
                                        <td>${item.last_updated || item.lastUpdated || 'N/A'}</td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="5" class="text-center">No summary data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        case 'par_detailed':
                            tableBody = document.getElementById('parDetailedTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.par_no || 'N/A'}</td>
                                        <td>${item.item_name || 'N/A'}</td>
                                        <td>${item.description || 'N/A'}</td>
                                        <td>${item.quantity || item.qty || 0}</td>
                                        <td>₱${(item.unit_cost || item.unitCost || 0).toLocaleString()}</td>
                                        <td>₱${(item.total_cost || item.totalCost || 0).toLocaleString()}</td>
                                        <td>${item.employee || item.received_by || 'N/A'}</td>
                                        <td>${item.department || 'N/A'}</td>
                                        <td>${item.date_issued || item.date_acquired || 'N/A'}</td>
                                        <td><span class="badge bg-success">${item.status || 'Active'}</span></td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="10" class="text-center">No PAR data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        case 'po_detailed':
                            tableBody = document.getElementById('poDetailedTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.po_no || 'N/A'}</td>
                                        <td>${item.supplier || item.supplier_name || 'N/A'}</td>
                                        <td>${item.item_description || 'N/A'}</td>
                                        <td>${item.quantity || 0}</td>
                                        <td>₱${(item.unit_price || 0).toLocaleString()}</td>
                                        <td>₱${(item.total_amount || 0).toLocaleString()}</td>
                                        <td>${item.date_ordered || item.po_date || 'N/A'}</td>
                                        <td>${item.date_delivered || item.delivery_date || 'N/A'}</td>
                                        <td><span class="badge ${item.status === 'Completed' ? 'bg-success' : 'bg-warning'}">${item.status || 'Pending'}</span></td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="9" class="text-center">No PO data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        // Add cases for other report types as needed
                    }
                    
                    // Also update the charts with filtered data
                    updateChartsWithFilters(filters);
                    
                    // Update the page info
                    document.getElementById('reportPageInfo').textContent = `Showing 1-${data ? data.length : 0} of ${data ? data.length : 0} records`;
                    
                    // Close the loading dialog
                    Swal.close();
                });
            }
        });
    }

    // Update all charts with current data
    function updateAllCharts() {
        // Get current filters
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const department = document.getElementById('departmentFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        // Prepare filters
        const filters = {
            fromDate: dateFrom,
            toDate: dateTo,
            department: department,
            status: status
        };
        
        // Update charts with these filters
        updateChartsWithFilters(filters);
        
        // Also update asset value chart from inventory data if available
        if (typeof updateAssetValueChart === 'function') {
            updateAssetValueChart();
        }
        
        // Get the chart type selection
        const chartType = document.getElementById('chartType').value;
        
        // Update all chart types if needed
        updateAllChartTypes(chartType);
    }

    // Update all chart types
    function updateAllChartTypes(chartType) {
        if (!chartType) return;
        
        const charts = [
            window.assetsByDepartmentChart,
            window.parVsPoChart,
            window.assetValueChart
        ];
        
        charts.forEach(chart => {
            if (chart.config.type !== 'line' && chart.config.type !== chartType) {
                chart.config.type = chartType;
                chart.update();
            }
        });
    }

    // Update charts based on report type
    function updateChartsForReportType(reportType) {
        if (reportType === 'par_detailed') {
            // Update with PAR-specific data
            window.assetsByDepartmentChart.data.datasets[0].label = 'PAR Value by Department';
            window.assetsByDepartmentChart.update();
        } else if (reportType === 'po_detailed') {
            // Update with PO-specific data
            window.assetsByDepartmentChart.data.datasets[0].label = 'PO Value by Department';
            window.assetsByDepartmentChart.update();
        }
    }

    // Load sample data for tables
    function loadSampleData() {
        // Fetch real data from the API
        fetchReportData('summary', 1, 10, {}, function(summaryData) {
            // Populate Summary Report table
            const summaryTableBody = document.getElementById('summaryTableBody');
            summaryTableBody.innerHTML = '';
            
            if (summaryData && summaryData.length > 0) {
                summaryData.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.category || 'N/A'}</td>
                        <td>${item.department || 'N/A'}</td>
                        <td>${item.count || 0}</td>
                        <td>₱${(item.value || 0).toLocaleString()}</td>
                        <td>${item.last_updated || item.lastUpdated || 'N/A'}</td>
                    `;
                    summaryTableBody.appendChild(row);
                });
            } else {
                // Display a message if no data is found
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="5" class="text-center">No summary data available</td>
                `;
                summaryTableBody.appendChild(row);
            }
            
            // Update the page info count
            document.getElementById('reportPageInfo').textContent = `Showing 1-${summaryData ? summaryData.length : 0} of ${summaryData ? summaryData.length : 0} records`;
        });
        
        // Fetch PAR Detailed Data
        fetchReportData('par_detailed', 1, 10, {}, function(parData) {
            // Populate PAR Detailed table
            const parDetailedTableBody = document.getElementById('parDetailedTableBody');
            parDetailedTableBody.innerHTML = '';
            
            if (parData && parData.length > 0) {
                parData.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.par_no || 'N/A'}</td>
                        <td>${item.item_name || 'N/A'}</td>
                        <td>${item.description || 'N/A'}</td>
                        <td>${item.quantity || item.qty || 0}</td>
                        <td>₱${(item.unit_cost || item.unitCost || 0).toLocaleString()}</td>
                        <td>₱${(item.total_cost || item.totalCost || 0).toLocaleString()}</td>
                        <td>${item.employee || item.received_by || 'N/A'}</td>
                        <td>${item.department || 'N/A'}</td>
                        <td>${item.date_issued || item.date_acquired || 'N/A'}</td>
                        <td><span class="badge bg-success">${item.status || 'Active'}</span></td>
                    `;
                    parDetailedTableBody.appendChild(row);
                });
            } else {
                // Display a message if no data is found
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="10" class="text-center">No PAR data available</td>
                `;
                parDetailedTableBody.appendChild(row);
            }
        });
        
        // Fetch PO Detailed Data
        fetchReportData('po_detailed', 1, 10, {}, function(poData) {
            // Populate PO Detailed table
            const poDetailedTableBody = document.getElementById('poDetailedTableBody');
            poDetailedTableBody.innerHTML = '';
            
            if (poData && poData.length > 0) {
                poData.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.po_no || 'N/A'}</td>
                        <td>${item.supplier || item.supplier_name || 'N/A'}</td>
                        <td>${item.item_description || 'N/A'}</td>
                        <td>${item.quantity || 0}</td>
                        <td>₱${(item.unit_price || 0).toLocaleString()}</td>
                        <td>₱${(item.total_amount || 0).toLocaleString()}</td>
                        <td>${item.date_ordered || item.po_date || 'N/A'}</td>
                        <td>${item.date_delivered || item.delivery_date || 'N/A'}</td>
                        <td><span class="badge ${item.status === 'Completed' ? 'bg-success' : 'bg-warning'}">${item.status || 'Pending'}</span></td>
                    `;
                    poDetailedTableBody.appendChild(row);
                });
            } else {
                // Display a message if no data is found
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="9" class="text-center">No PO data available</td>
                `;
                poDetailedTableBody.appendChild(row);
            }
        });
        
        // Also update charts with real data from PAR and PO
        updateChartsWithRealData();
    }
    
    // Function to fetch report data from the API
    function fetchReportData(reportType, page, perPage, filters, callback) {
        // Build the URL with parameters
        const url = new URL('get_report_data.php', window.location.origin);
        url.searchParams.append('type', reportType);
        url.searchParams.append('page', page);
        url.searchParams.append('per_page', perPage);
        
        // Add any filters
        if (filters.fromDate) url.searchParams.append('from', filters.fromDate);
        if (filters.toDate) url.searchParams.append('to', filters.toDate);
        if (filters.department) url.searchParams.append('department', filters.department);
        if (filters.status) url.searchParams.append('status', filters.status);
        
        // Add cache busting parameter
        url.searchParams.append('_', new Date().getTime());
        
        // Make the AJAX request
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(data.data);
                } else {
                    console.error('Error fetching report data:', data.error);
                    callback([]);
                }
            })
            .catch(error => {
                console.error('Error fetching report data:', error);
                callback([]);
            });
    }
    
    // Function to update charts with real data
    function updateChartsWithRealData() {
        // Make an AJAX call to get the real data for charts
        fetch('reports_api.php?action=department_summary')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.department_summary) {
                    // Update Assets by Department chart
                    const deptData = data.department_summary;
                    window.assetsByDepartmentChart.data.labels = deptData.labels;
                    window.assetsByDepartmentChart.data.datasets[0].data = deptData.values;
                    window.assetsByDepartmentChart.update();
                }
            })
            .catch(error => console.error('Error fetching department summary:', error));
        
        // Get PAR vs PO totals
        fetch('reports_api.php?action=summary')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update PAR vs PO chart
                    window.parVsPoChart.data.datasets[0].data = [
                        data.par_total || 38000,
                        data.po_total || 52000
                    ];
                    window.parVsPoChart.update();
                }
            })
            .catch(error => console.error('Error fetching PAR/PO summary:', error));
        
        // Get monthly trend data
        fetch('reports_api.php?action=monthly_trend&time_range=monthly')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.par_trend && data.po_trend) {
                    // Update Monthly Trend chart
                    window.monthlyTrendChart.data.labels = data.par_trend.labels;
                    window.monthlyTrendChart.data.datasets[0].data = data.par_trend.values;
                    window.monthlyTrendChart.data.datasets[1].data = data.po_trend.values;
                    window.monthlyTrendChart.update();
                }
            })
            .catch(error => console.error('Error fetching monthly trend:', error));
    }

    // Set up Python prediction functionality
    function setupPythonPrediction() {
        const runPredictionBtn = document.getElementById('runPredictionBtn');
        
        runPredictionBtn.addEventListener('click', function() {
            const predictionType = document.getElementById('predictionType').value;
            const timeHorizon = document.getElementById('timeHorizon').value;
            const confidenceLevel = document.getElementById('confidenceLevel').value;
            
            // Show loading indicator
            document.getElementById('predictionLoading').classList.remove('d-none');
            document.getElementById('predictionResults').classList.add('d-none');
            
            // Simulate API call to Python script
            setTimeout(function() {
                // Hide loading indicator
                document.getElementById('predictionLoading').classList.add('d-none');
                document.getElementById('predictionResults').classList.remove('d-none');
                
                // Call Python script via AJAX
                callPythonPrediction(predictionType, timeHorizon, confidenceLevel);
            }, 1500);
        });
    }

    // Call Python prediction script
    function callPythonPrediction(predictionType, timeHorizon, confidenceLevel) {
        // In a real application, this would make an AJAX call to a PHP endpoint
        // that would then call the Python script
        
        // For demonstration, we'll simulate the response
        let labels, historicalData, predictionsData;
        
        if (predictionType === 'future_par') {
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
            historicalData = [12000, 18000, 15000, 22000, 19000, 25000];
            predictionsData = Array(6).fill(null).concat([28000, 30000, 32000]);
        } else if (predictionType === 'future_po') {
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
            historicalData = [15000, 20000, 18000, 25000, 22000, 28000];
            predictionsData = Array(6).fill(null).concat([32000, 35000, 38000]);
        } else if (predictionType === 'asset_depreciation') {
            labels = ['2023', '2024', '2025', '2026', '2027', '2028'];
            historicalData = [100000, 85000, 72000];
            predictionsData = Array(3).fill(null).concat([61000, 52000, 44000]);
        } else {
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
            historicalData = [5, 8, 12, 10, 15, 18];
            predictionsData = Array(6).fill(null).concat([22, 25, 30]);
        }
        
        // Update the prediction chart
        window.predictionChart.data.labels = labels;
        window.predictionChart.data.datasets[0].data = historicalData;
        window.predictionChart.data.datasets[1].data = predictionsData;
        
        // Update chart title based on prediction type
        if (predictionType === 'future_par') {
            window.predictionChart.options.plugins = {
                title: {
                    display: true,
                    text: 'PAR Value Prediction'
                }
            };
        } else if (predictionType === 'future_po') {
            window.predictionChart.options.plugins = {
                title: {
                    display: true,
                    text: 'PO Value Prediction'
                }
            };
        } else if (predictionType === 'asset_depreciation') {
            window.predictionChart.options.plugins = {
                title: {
                    display: true,
                    text: 'Asset Depreciation Prediction'
                }
            };
        } else {
            window.predictionChart.options.plugins = {
                title: {
                    display: true,
                    text: 'Maintenance Prediction'
                }
            };
        }
        
        window.predictionChart.update();
    }

    // Setup export buttons
    function setupExportButtons() {
        // Excel export
        document.getElementById('exportReportBtn').addEventListener('click', function() {
            exportToExcel();
        });
        
        // PDF export
        document.getElementById('exportPdfBtn').addEventListener('click', function() {
            exportToPDF();
        });
        
        // Print report
        document.getElementById('printReportBtn').addEventListener('click', function() {
            printReport();
        });
        
        // Generate report
        document.getElementById('generateReportBtn').addEventListener('click', function() {
            generateReport();
        });
    }

    // Export to Excel function
    function exportToExcel() {
        alert('Export to Excel functionality will be implemented here');
        // In real implementation, this would use a library like SheetJS to generate Excel files
    }

    // Export to PDF function
    function exportToPDF() {
        alert('Export to PDF functionality will be implemented here');
        // In real implementation, this would use a library like jsPDF to generate PDF files
    }

    // Print report function
    function printReport() {
        window.print();
    }

    // Generate report function
    function generateReport() {
        // Show loading indicator for report generation
        Swal.fire({
            title: 'Generating Report',
            html: 'Please wait while we generate your report...',
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
                
                // Get the current filters and report type
                const reportType = document.getElementById('reportType').value;
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                const department = document.getElementById('departmentFilter').value;
                const status = document.getElementById('statusFilter').value;
                
                // Prepare filters object
                const filters = {
                    fromDate: dateFrom,
                    toDate: dateTo,
                    department: department,
                    status: status
                };
                
                // Fetch data with filters applied
                fetchReportData(reportType, 1, 25, filters, function(data) {
                    // Update the appropriate table based on report type
                    let tableBody;
                    
                    switch(reportType) {
                        case 'summary':
                            tableBody = document.getElementById('summaryTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.category || 'N/A'}</td>
                                        <td>${item.department || 'N/A'}</td>
                                        <td>${item.count || 0}</td>
                                        <td>₱${(item.value || 0).toLocaleString()}</td>
                                        <td>${item.last_updated || item.lastUpdated || 'N/A'}</td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="5" class="text-center">No summary data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        case 'par_detailed':
                            tableBody = document.getElementById('parDetailedTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.par_no || 'N/A'}</td>
                                        <td>${item.item_name || 'N/A'}</td>
                                        <td>${item.description || 'N/A'}</td>
                                        <td>${item.quantity || item.qty || 0}</td>
                                        <td>₱${(item.unit_cost || item.unitCost || 0).toLocaleString()}</td>
                                        <td>₱${(item.total_cost || item.totalCost || 0).toLocaleString()}</td>
                                        <td>${item.employee || item.received_by || 'N/A'}</td>
                                        <td>${item.department || 'N/A'}</td>
                                        <td>${item.date_issued || item.date_acquired || 'N/A'}</td>
                                        <td><span class="badge bg-success">${item.status || 'Active'}</span></td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="10" class="text-center">No PAR data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        case 'po_detailed':
                            tableBody = document.getElementById('poDetailedTableBody');
                            tableBody.innerHTML = '';
                            
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${item.po_no || 'N/A'}</td>
                                        <td>${item.supplier || item.supplier_name || 'N/A'}</td>
                                        <td>${item.item_description || 'N/A'}</td>
                                        <td>${item.quantity || 0}</td>
                                        <td>₱${(item.unit_price || 0).toLocaleString()}</td>
                                        <td>₱${(item.total_amount || 0).toLocaleString()}</td>
                                        <td>${item.date_ordered || item.po_date || 'N/A'}</td>
                                        <td>${item.date_delivered || item.delivery_date || 'N/A'}</td>
                                        <td><span class="badge ${item.status === 'Completed' ? 'bg-success' : 'bg-warning'}">${item.status || 'Pending'}</span></td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            } else {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td colspan="9" class="text-center">No PO data available for the selected filters</td>
                                `;
                                tableBody.appendChild(row);
                            }
                            break;
                            
                        case 'inventory_detailed':
                        case 'audit_logs':
                            // Handle other report types similarly
                            // This would be expanded for the actual implementation
                            break;
                    }
                    
                    // Also update the charts if needed based on the filters
                    updateChartsWithFilters(filters);
                    
                    // Close the loading dialog
                    Swal.close();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Report Generated',
                        text: 'Your report has been generated successfully!'
                    });
                    
                    // Update the page info
                    document.getElementById('reportPageInfo').textContent = `Showing 1-${data ? data.length : 0} of ${data ? data.length : 0} records`;
                });
            }
        });
    }
    
    // Function to update charts with filters
    function updateChartsWithFilters(filters) {
        // Update charts based on the filters
        const timeInterval = document.getElementById('timeInterval').value;
        
        // Make API request with filters
        fetch(`reports_api.php?action=monthly_trend&time_range=${timeInterval}${filters.fromDate ? '&from_date='+filters.fromDate : ''}${filters.toDate ? '&to_date='+filters.toDate : ''}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.par_trend && data.po_trend) {
                    // Update Monthly Trend chart
                    window.monthlyTrendChart.data.labels = data.par_trend.labels;
                    window.monthlyTrendChart.data.datasets[0].data = data.par_trend.values;
                    window.monthlyTrendChart.data.datasets[1].data = data.po_trend.values;
                    window.monthlyTrendChart.update();
                }
            })
            .catch(error => console.error('Error fetching filtered trend data:', error));
        
        // Update department summary chart
        if (filters.department) {
            // If department filter is set, just show that department
            const deptLabels = [filters.department];
            const deptValues = [0]; // Will be updated from the API if available
            
            fetch(`reports_api.php?action=department_summary${filters.department ? '&department='+filters.department : ''}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.department_summary) {
                        window.assetsByDepartmentChart.data.labels = data.department_summary.labels;
                        window.assetsByDepartmentChart.data.datasets[0].data = data.department_summary.values;
                    } else {
                        window.assetsByDepartmentChart.data.labels = deptLabels;
                        window.assetsByDepartmentChart.data.datasets[0].data = deptValues;
                    }
                    window.assetsByDepartmentChart.update();
                })
                .catch(() => {
                    window.assetsByDepartmentChart.data.labels = deptLabels;
                    window.assetsByDepartmentChart.data.datasets[0].data = deptValues;
                    window.assetsByDepartmentChart.update();
                });
        } else {
            // If no department filter, show all departments
            fetch('reports_api.php?action=department_summary')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.department_summary) {
                        window.assetsByDepartmentChart.data.labels = data.department_summary.labels;
                        window.assetsByDepartmentChart.data.datasets[0].data = data.department_summary.values;
                        window.assetsByDepartmentChart.update();
                    }
                })
                .catch(error => console.error('Error fetching department summary:', error));
        }
    }
});

// PHP and Python integration function
function getPythonAnalytics(reportType, timeRange, filters) {
    // This function would make an AJAX call to a PHP endpoint
    // which would then execute a Python script for data analysis
    
    return new Promise((resolve, reject) => {
        // Simulate AJAX call
        setTimeout(() => {
            // Mock data that would come from Python
            const mockData = {
                success: true,
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'Actual Values',
                            values: [12000, 18000, 15000, 22000, 19000, 25000]
                        },
                        {
                            label: 'Predicted Values',
                            values: [13000, 17500, 16000, 21000, 20000, 24000]
                        }
                    ],
                    metrics: {
                        accuracy: 0.92,
                        mae: 1200,
                        mse: 1800000
                    }
                }
            };
            
            resolve(mockData);
        }, 1000);
    });
} 