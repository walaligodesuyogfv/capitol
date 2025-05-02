document.addEventListener('DOMContentLoaded', function() {
    // Add analytics button to the dashboard header
    const dashboardHeader = document.querySelector('.dashboard-header');
    if (dashboardHeader) {
        const analyticsButton = document.createElement('button');
        analyticsButton.className = 'btn btn-primary btn-sm ms-2';
        analyticsButton.setAttribute('data-bs-toggle', 'modal');
        analyticsButton.setAttribute('data-bs-target', '#poAnalyticsModal');
        analyticsButton.innerHTML = '<i class="bi bi-graph-up me-1"></i> PO Predictions';
        
        // Find notification dropdown and insert button before it
        const notificationDropdown = dashboardHeader.querySelector('.dropdown');
        if (notificationDropdown) {
            dashboardHeader.insertBefore(analyticsButton, notificationDropdown);
        } else {
            // Append to end of header if dropdown not found
            dashboardHeader.appendChild(analyticsButton);
        }
    }
    
    // Add analytics card to the dashboard
    const statsGrid = document.querySelector('.stats-grid');
    if (statsGrid) {
        const analyticsCard = document.createElement('div');
        analyticsCard.className = 'dashboard-stats prediction';
        analyticsCard.innerHTML = `
            <div class="stats-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stats-number">PO/PAR</div>
            <div class="stats-label">Predictions</div>
        `;
        
        // Add click event to open analytics modal
        analyticsCard.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('poAnalyticsModal'));
            modal.show();
        });
        
        // Add to stats grid
        statsGrid.appendChild(analyticsCard);
    }
}); 
 // Enhanced script to ensure actions are on the right of PAR and PO tables
 document.addEventListener('DOMContentLoaded', function() {
    // Initial run when the document loads
    moveActionsToRight();
    
    // Run the function after PAR and PO data are displayed
    // Override existing display functions to always ensure actions are on the right
    if (typeof window.displayPARData === 'function') {
        const originalDisplayPARData = window.displayPARData;
        window.displayPARData = function() {
            const result = originalDisplayPARData.apply(this, arguments);
            setTimeout(moveActionsToRight, 100); // Run after DOM update
            return result;
        };
    }
    
    if (typeof window.displayPOData === 'function') {
        const originalDisplayPOData = window.displayPOData;
        window.displayPOData = function() {
            const result = originalDisplayPOData.apply(this, arguments);
            setTimeout(moveActionsToRight, 100); // Run after DOM update
            return result;
        };
    }
    
    function moveActionsToRight() {
        // For PAR table headers
        const parTableHeaders = document.querySelectorAll('#parTable thead tr, .par-table thead tr');
        parTableHeaders.forEach(headerRow => {
            // Check if there's an Actions header
            const actionsHeader = Array.from(headerRow.querySelectorAll('th')).find(th => 
                th.textContent.trim().toLowerCase() === 'actions');
            
            if (actionsHeader) {
                // Move to the end if not already
                if (actionsHeader !== headerRow.lastElementChild) {
                    headerRow.appendChild(actionsHeader);
                }
                // Add text-center class if not present
                if (!actionsHeader.classList.contains('text-center')) {
                    actionsHeader.classList.add('text-center');
                }
            }
        });
        
        // For PAR table rows
        const parTableRows = document.querySelectorAll('#parTable tbody tr, .par-table tbody tr');
        parTableRows.forEach(row => {
            const actionCell = Array.from(row.querySelectorAll('td')).find(td => 
                td.querySelector('.btn-group, .action-buttons, [class*="btn-"]'));
            
            if (actionCell) {
                // Move to the end if not already
                if (actionCell !== row.lastElementChild) {
                    row.appendChild(actionCell);
                }
                // Add text-center class if not present
                if (!actionCell.classList.contains('text-center')) {
                    actionCell.classList.add('text-center');
                }
            }
        });
        
        // For PO table headers
        const poTableHeaders = document.querySelectorAll('#poTable thead tr, .po-table thead tr');
        poTableHeaders.forEach(headerRow => {
            // Check if there's an Actions header
            const actionsHeader = Array.from(headerRow.querySelectorAll('th')).find(th => 
                th.textContent.trim().toLowerCase() === 'actions');
            
            if (actionsHeader) {
                // Move to the end if not already
                if (actionsHeader !== headerRow.lastElementChild) {
                    headerRow.appendChild(actionsHeader);
                }
                // Add text-center class if not present
                if (!actionsHeader.classList.contains('text-center')) {
                    actionsHeader.classList.add('text-center');
                }
            }
        });
        
        // For PO table rows
        const poTableRows = document.querySelectorAll('#poTable tbody tr, .po-table tbody tr');
        poTableRows.forEach(row => {
            const actionCell = Array.from(row.querySelectorAll('td')).find(td => 
                td.querySelector('.btn-group, .action-buttons, [class*="btn-"]'));
            
            if (actionCell) {
                // Move to the end if not already
                if (actionCell !== row.lastElementChild) {
                    row.appendChild(actionCell);
                }
                // Add text-center class if not present
                if (!actionCell.classList.contains('text-center')) {
                    actionCell.classList.add('text-center');
                }
            }
        });
        
        // For PAR Items table headers
        const parItemsTableHeaders = document.querySelectorAll('#parItemsTable thead tr');
        parItemsTableHeaders.forEach(headerRow => {
            const lastCell = headerRow.lastElementChild;
            // Check if last header is for actions
            if (lastCell && !lastCell.classList.contains('text-center')) {
                lastCell.classList.add('text-center');
            }
        });
        
        // For PAR Items table rows
        const parItemsRows = document.querySelectorAll('#parItemsTable tbody tr');
        parItemsRows.forEach(row => {
            const actionCell = row.lastElementChild;
            if (actionCell && actionCell.querySelector('.btn, .btn-group, .action-buttons')) {
                actionCell.classList.add('text-center');
            }
        });
        
        // For PO Items table headers
        const poItemsTableHeaders = document.querySelectorAll('#poItemsTable thead tr');
        poItemsTableHeaders.forEach(headerRow => {
            const lastCell = headerRow.lastElementChild;
            // Check if last header is for actions
            if (lastCell && !lastCell.classList.contains('text-center')) {
                lastCell.classList.add('text-center');
            }
        });
        
        // For PO Items table rows
        const poItemsRows = document.querySelectorAll('#poItemsTable tbody tr');
        poItemsRows.forEach(row => {
            const actionCell = row.lastElementChild;
            if (actionCell && actionCell.querySelector('.btn, .btn-group, .action-buttons')) {
                actionCell.classList.add('text-center');
            }
        });
    }
    
    // Run the function periodically to catch any dynamically added tables
    setInterval(moveActionsToRight, 5000);
});
 // Function to fix modal backdrop issues
 function fixModalBackdrop() {
    // Check if any modal is currently open
    const openModals = document.querySelectorAll('.modal.show');
    const hasOpenModal = openModals.length > 0;
    
    // Check for backdrop elements
    const backdrops = document.querySelectorAll('.modal-backdrop');
    
    // If we have backdrops but no open modals, clean them up
    if (backdrops.length > 0 && !hasOpenModal) {
        console.log(`Cleaning up ${backdrops.length} orphaned modal backdrops`);
        
        // Remove all backdrop elements
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Reset body styles and classes
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // If multiple backdrops exist but only one modal is open, remove extras
    if (backdrops.length > 1 && openModals.length === 1) {
        console.log(`Cleaning up ${backdrops.length - 1} extra modal backdrops`);
        
        // Keep only the first backdrop
        for (let i = 1; i < backdrops.length; i++) {
            backdrops[i].remove();
        }
    }
}

// Run cleanup periodically to catch any missed backdrops
setInterval(fixModalBackdrop, 5000);
 // Add a global modal backdrop fix that runs when modals are closed
 document.addEventListener('DOMContentLoaded', function() {
    // Fix for modal backdrop not being removed properly
    const fixModalBackdrop = () => {
        // Check if any modals are still open
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
            // If no modals are open, remove all backdrops
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            // Also reset body classes
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    };
    
    // Add event listeners to all modals for the hidden.bs.modal event
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', fixModalBackdrop);
    });
    
    // Ensure modals initialize properly
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
        trigger.addEventListener('click', function() {
            // Short delay to allow modal to open before checking backdrop
            setTimeout(() => {
                // If multiple backdrops exist, remove extras
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    // Keep only the last backdrop
                    for (let i = 0; i < backdrops.length - 1; i++) {
                        backdrops[i].remove();
                    }
                }
            }, 100);
        });
    });
    
    // Special handling for PO modal
    const poModal = document.getElementById('addPOModal');
    if (poModal) {
        poModal.addEventListener('hidden.bs.modal', function() {
            // Force cleanup of modal backdrop
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            // Reset body styles and classes
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            console.log('PO modal backdrop cleanup complete');
        });
    }
    
    // Run cleanup periodically to catch any missed backdrops
    // Removed duplicate interval - already defined above
});
document.addEventListener('DOMContentLoaded', function() {
    // Fix for disappearing charts and tables
    function fixDashboardUI() {
        console.log('Running dashboard UI fix');
        
        // 1. Fix for bar charts
        fixCharts();
        
        // 2. Fix for PO table outputs
        fixPOTableOutputs();
        
        // 3. Fix for inventory data
        fixInventoryData();
        
        console.log('Dashboard UI fix complete');
    }
    
    // Fix for disappearing charts
    function fixCharts() {
        // Get all chart canvases
        const chartCanvases = document.querySelectorAll('canvas');
        
        chartCanvases.forEach(canvas => {
            // Make sure canvas is visible
            if (canvas.style.display === 'none') {
                canvas.style.display = 'block';
            }
            
            if (canvas.classList.contains('d-none')) {
                canvas.classList.remove('d-none');
            }
            
            // Check if canvas is a chart
            const chartInstance = getChartInstance(canvas);
            if (chartInstance) {
                // Ensure chart is rendering properly
                console.log(`Ensuring chart ${canvas.id} is rendering properly`);
                chartInstance.update();
            }
        });
        
        // Special handling for inventory chart
        if (window.inventoryChart && typeof window.inventoryChart.update === 'function') {
            window.inventoryChart.update();
        }
    }
    
    // Helper to get chart instance from canvas
    function getChartInstance(canvas) {
        if (!canvas || !canvas.id) return null;
        
        // Try to get chart instance from window
        if (window[canvas.id + 'Chart']) {
            return window[canvas.id + 'Chart'];
        }
        
        // Try to get chart via Chart.js internal registry (if available)
        if (window.Chart && window.Chart.instances) {
            for (let id in window.Chart.instances) {
                if (window.Chart.instances[id].canvas.id === canvas.id) {
                    return window.Chart.instances[id];
                }
            }
        }
        
        return null;
    }
    
    // Fix for PO table outputs
    function fixPOTableOutputs() {
        const poTable = document.querySelector('#poTable, .po-section table');
        const poTableBody = document.querySelector('#poTableBody, .po-section table tbody');
        
        if (!poTable || !poTableBody) {
            console.warn('PO table not found in DOM');
            return;
        }
        
        // Ensure table is visible
        poTable.style.display = 'table';
        poTable.classList.remove('d-none');
        
        // Ensure container is visible
        const container = poTable.closest('.table-responsive, .card-body, .po-section');
        if (container) {
            container.style.display = 'block';
            container.classList.remove('d-none');
        }
        
        // Check if table is empty or has invalid content
        const rows = poTableBody.querySelectorAll('tr');
        const isEmpty = rows.length === 0 || 
                      Array.from(rows).every(row => 
                          row.textContent.trim() === '' || 
                          row.innerHTML.includes('undefined') || 
                          row.innerHTML.includes('null'));
        
        // Only reload data if auto-refresh is enabled and table is empty
        if (isEmpty && typeof loadPOData === 'function') {
            console.log('PO table appears empty or has invalid data, checking auto-refresh settings...');
            
            // Check if auto-refresh is disabled
            if (window.disableAutoPORefresh !== true) {
                console.log('Auto-refresh is enabled, reloading data...');
                loadPOData();
            } else {
                console.log('Auto-refresh is disabled, not reloading data automatically');
                // Just display empty table message instead of auto-reloading
                poTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No purchase orders found. Click the refresh button to load data.</td></tr>';
            }
        }
    }
    
    // Fix for inventory data
    function fixInventoryData() {
        const inventoryTable = document.querySelector('#inventoryTable, .inventory-section table');
        const inventoryTableBody = document.querySelector('#inventoryTableBody, .inventory-section table tbody');
        
        if (!inventoryTable || !inventoryTableBody) {
            console.warn('Inventory table not found in DOM');
            return;
        }
        
        // Ensure table is visible
        inventoryTable.style.display = 'table';
        inventoryTable.classList.remove('d-none');
        
        // Ensure container is visible
        const container = inventoryTable.closest('.table-responsive, .card-body, .inventory-section');
        if (container) {
            container.style.display = 'block';
            container.classList.remove('d-none');
        }
        
        // Check if table is empty or has invalid content
        const rows = inventoryTableBody.querySelectorAll('tr');
        const isEmpty = rows.length === 0 || 
                      Array.from(rows).every(row => 
                          row.textContent.trim() === '' || 
                          row.innerHTML.includes('undefined') || 
                          row.innerHTML.includes('null'));
        
        // Reload data if table is empty or has invalid content
        if (isEmpty && typeof loadInventoryData === 'function') {
            console.log('Inventory table appears empty or has invalid data, reloading...');
            loadInventoryData();
        }
    }
    
    // Run fixes immediately
    fixDashboardUI();
    
    // Set up monitoring for UI changes
    function setupChangeMonitoring() {
        // Watch for tab/section changes
        const navLinks = document.querySelectorAll('.nav-link, .section-link, [data-toggle="tab"]');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Wait for DOM to update after section change
                setTimeout(fixDashboardUI, 500);
            });
        });
        
        // Watch for window resize (which can affect chart rendering)
        window.addEventListener('resize', function() {
            // Use debounce to avoid multiple rapid calls
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(fixCharts, 250);
        });
        
        // Periodically check that all elements are visible
        setInterval(fixDashboardUI, 30000); // Check every 30 seconds
    }
    
    // Set up monitoring
    setupChangeMonitoring();
});
    // Safety handler for PO modal to prevent black screen issues
    document.addEventListener('DOMContentLoaded', function() {
        // Add a listener for the PO modal being shown
        const poModal = document.getElementById('addPOModal');
        if (poModal) {
            poModal.addEventListener('shown.bs.modal', function() {
                console.log('PO modal shown - setting up safety handlers');
                
                // Check if the save button already has an event handler
                const saveBtn = document.getElementById('savePoBtn');
                if (saveBtn && saveBtn.getAttribute('data-handler-status') === 'unattached') {
                    console.log('Setting up safe handler for PO save button');
                    
                    // Safety mechanism: add global error handler
                    window.addEventListener('error', function(event) {
                        console.error('Global error caught:', event.error);
                        
                        // Prevent black screen by hiding the modal if an error occurs
                        if (typeof bootstrap !== 'undefined') {
                            const modalInstance = bootstrap.Modal.getInstance(poModal);
                            if (modalInstance) {
                                console.log('Safely closing modal due to error');
                                modalInstance.hide();
                            }
                        }
                        
                        // Show friendly error message
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Error',
                                text: 'An error occurred while processing the Purchase Order. Please try again.',
                                icon: 'error'
                            });
                        } else {
                            alert('An error occurred while processing the Purchase Order. Please try again.');
                        }
                    });
                    
                    // Mark button as having handler attached
                    saveBtn.setAttribute('data-handler-status', 'attached');
                }
            });
            
            // Safety check when closing the modal to clear any hung states
            poModal.addEventListener('hidden.bs.modal', function() {
                console.log('PO modal hidden - cleaning up');
                
                // Reset button status
                const saveBtn = document.getElementById('savePoBtn');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Save PO';
                }
                
                // Reset the form
                const form = poModal.querySelector('form');
                if (form) form.reset();
                
                // Enhanced backdrop cleanup
                // 1. Remove any lingering backdrops
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                    backdrop.remove();
                });
                
                // 2. Fix body styles
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // 3. Force redraw of the page
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                }, 150);
                
                console.log('Enhanced PO modal cleanup complete');
            });
        }
        
        // Global error recovery for fetch operations
        window.safetyFetch = function(url, options) {
            return fetch(url, options)
                .catch(error => {
                    console.error('Fetch error occurred:', error);
                    
                    // Show error notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Connection Error',
                            text: 'Failed to connect to the server. Please check your connection and try again.',
                            icon: 'error'
                        });
                    }
                    
                    // Hide any loading indicators
                    if (typeof hideLoading === 'function') {
                        hideLoading();
                    }
                    
                    throw error;
                });
        };
            });
              // Remove specific PAR item row with QTY=1, AMOUNT=0, Date Acquired=10/04/25
        document.addEventListener('DOMContentLoaded', function() {
            // Function to check and remove the specific PAR item
            function removeSpecificParItem() {
                // Look for all rows in PAR items tables
                const parRows = document.querySelectorAll('#parItemsTable tbody tr, .par-table tbody tr, table tbody tr');
                
                parRows.forEach(row => {
                    // Check if this is the row we want to remove
                    const qtyElement = row.querySelector('.par-qty, .qty, [name="quantity[]"], td.quantity');
                    const amountElement = row.querySelector('.par-amount, .amount, [name="amount[]"]');
                    const dateElement = row.querySelector('.par-item-date, [name="date_acquired[]"], .date-cell');
                    
                    if (qtyElement && amountElement && dateElement) {
                        // Get values from elements
                        let qty = qtyElement.tagName === 'TD' ? qtyElement.textContent.trim() : qtyElement.value;
                        let amount = amountElement.tagName === 'TD' ? amountElement.textContent.trim() : amountElement.value;
                        let date = dateElement.tagName === 'TD' ? dateElement.textContent.trim() : dateElement.value;
                        
                        // Check for exact match with the values we want to remove
                        if (qty == '1' && amount == '0' && (date == '10/04/25' || date == '2025-04-10')) {
                            console.log('Removing specific PAR item row:', row);
                            row.remove();
                            
                            // Recalculate PAR total if needed
                            if (typeof calculateParTotal === 'function') {
                                calculateParTotal();
                            }
                        }
                    }
                });
            }
            
            // Run immediately and also set up to run when modals are shown
            removeSpecificParItem();
            
            // Also run when any modal is shown (in case items are loaded dynamically)
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('shown.bs.modal', removeSpecificParItem);
            });
        });
         // Add event listener for page refresh or navigation
         window.addEventListener('beforeunload', function() {
            // Destroy charts before page refresh to prevent canvas reuse issues
            if (window.inventoryChart && typeof window.inventoryChart.destroy === 'function') {
                try {
                    window.inventoryChart.destroy();
                    window.inventoryChart = null;
                } catch (e) {
                    console.error('Error destroying inventory chart:', e);
                }
            }
            
            if (window.stockStatusChart && typeof window.stockStatusChart.destroy === 'function') {
                try {
                    window.stockStatusChart.destroy();
                    window.stockStatusChart = null;
                } catch (e) {
                    console.error('Error destroying stock status chart:', e);
                }
            }
        });
        document.addEventListener('DOMContentLoaded', function() {
    // Show Inventory by default
    document.getElementById('inventory-content').style.display = 'block';
    // ... existing code ...

    // Setup Condition Monitoring Tables button
    const setupBtn = document.getElementById('setupConditionTables');
    if (setupBtn) {
        setupBtn.addEventListener('click', setupConditionMonitoring);
    }

    // ... existing code ...
});

// ... existing code ...

// Setup Inventory Condition Monitoring Tables
function setupConditionMonitoring() {
    Swal.fire({
        title: 'Setup Condition Monitoring',
        text: 'This will install or update the necessary database tables for inventory condition monitoring. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, set up tables',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
                // Show loading indicator
            Swal.fire({
                title: 'Setting up tables...',
                html: 'Please wait while we set up the condition monitoring system.',
                allowOutsideClick: false,
                didOpen: () => {    
                    Swal.showLoading();
                }
            });

            // Make AJAX request to run SQL script
            fetch('setup_condition_tables.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            // Refresh inventory conditions table
                            updateInventoryConditions();
                        });
                        } else {
                        throw new Error(data.message || 'Failed to set up condition monitoring tables');
                        }
                    })
                    .catch(error => {
                    console.error('Error setting up condition monitoring:', error);
                    Swal.fire({
                        title: 'Error',
                        text: error.message || 'Failed to set up condition monitoring tables',
                        icon: 'error'
                    });
                });
        }
    });
}

// Helper functions for dashboard stats loading
function showLoading() {
    const loadingElement = document.getElementById('loadingIndicator');
    if (loadingElement) {
        loadingElement.style.display = 'flex';
    } else {
        // Create a loading indicator if it doesn't exist
        const loader = document.createElement('div');
        loader.id = 'loadingIndicator';
        loader.className = 'loading-overlay';
        loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100%';
        loader.style.height = '100%';
        loader.style.display = 'flex';
        loader.style.alignItems = 'center';
        loader.style.justifyContent = 'center';
        loader.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        loader.style.zIndex = '9999';
        document.body.appendChild(loader);
    }
}

function hideLoading() {
    const loadingElement = document.getElementById('loadingIndicator');
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
}

function showError(message) {
    // Create or update error container
    let errorContainer = document.getElementById('errorContainer');

    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.id = 'errorContainer';
        errorContainer.className = 'alert alert-danger alert-dismissible fade show';
        errorContainer.style.position = 'fixed';
        errorContainer.style.top = '20px';
        errorContainer.style.right = '20px';
        errorContainer.style.zIndex = '1050';
        errorContainer.style.maxWidth = '400px';
        document.body.appendChild(errorContainer);
    }

    errorContainer.innerHTML = `
        <strong>Error!</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const errorElement = document.getElementById('errorContainer');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }, 5000);
}

// Function to load dashboard statistics
function loadDashboardStats() {
    // Check if we are already loading - prevent multiple simultaneous calls
    if (window.isLoadingDashboardStats) {
        console.log('Dashboard stats already loading, skipping duplicate call');
        return;
    }

    window.isLoadingDashboardStats = true;
    showLoading();

    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded. Cannot render charts.');
        showError('Failed to load dashboard: Chart.js library is not available.');
        window.isLoadingDashboardStats = false;
        hideLoading();
        return;
    }

    // Clear existing charts before loading new data
    if (window.inventoryChart && typeof window.inventoryChart.destroy === 'function') {
        window.inventoryChart.destroy();
        window.inventoryChart = null;
    }
    
    if (window.stockStatusChart && typeof window.stockStatusChart.destroy === 'function') {
        window.stockStatusChart.destroy();
        window.stockStatusChart = null;
    }

    fetch('dashboard_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load dashboard statistics');
            }

            console.log('Dashboard data loaded successfully:', data);

            // Update statistics counters
            document.querySelectorAll('.stats-number').forEach(el => {
                const parent = el.closest('.dashboard-stats');
                if (parent) {
                    if (parent.classList.contains('total-items')) {
                        el.textContent = data.total_items || 0;
                    } else if (parent.classList.contains('inventory')) {
                        el.textContent = data.inventory_count || 0;
                    } else if (parent.classList.contains('po')) {
                        el.textContent = data.total_pos || 0;
                    } else if (parent.classList.contains('par')) {
                        el.textContent = data.total_pars || 0;
                    }
                }
            });

            // Only render charts if Chart is defined and data exists
            if (typeof Chart !== 'undefined') {
                // Render inventory chart if data exists
                if (data.chart_data && document.getElementById('inventoryChart')) {
                    // Ensure no existing chart before rendering
                    if (window.inventoryChart && typeof window.inventoryChart.destroy === 'function') {
                        window.inventoryChart.destroy();
                        window.inventoryChart = null;
                    }
                    renderInventoryChart(data.chart_data);
                } else {
                    console.warn('Inventory chart data missing or element not found');
                }

                // Render stock status chart if data exists
                if (data.stock_status && document.getElementById('stockStatusChart')) {
                    // Ensure no existing chart before rendering
                    if (window.stockStatusChart && typeof window.stockStatusChart.destroy === 'function') {
                        window.stockStatusChart.destroy();
                        window.stockStatusChart = null;
                    }
                    renderStockStatusChart(data.stock_status);
                } else {
                    console.warn('Stock status chart data missing or element not found');
                }
            } else {
                console.error('Chart.js is not loaded. Cannot render charts.');
            }

            hideLoading();
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            showError('Failed to load dashboard statistics. Please try again.');
            hideLoading();
                    })
                    .finally(() => {
            // Reset the loading flag
            window.isLoadingDashboardStats = false;
        });
}

// Add event handlers for inventory item additions
document.addEventListener('DOMContentLoaded', function() {
    // When new inventory item is added, refresh condition monitoring
    const addInventoryForm = document.getElementById('addInventoryForm');
    if (addInventoryForm) {
        addInventoryForm.addEventListener('submit', function() {
            // Wait for the form submission to complete and then refresh
            setTimeout(function() {
                // Check if we're on the inventory condition monitoring tab
                const conditionTable = document.getElementById('inventoryConditionTable');
                if (conditionTable) {
                    updateInventoryConditions();
                }
            }, 2000); // Wait 2 seconds for server processing
        });
    }

    // Also listen for the success message from inventory additions
    document.addEventListener('DOMNodeInserted', function(e) {
        // Check if the inserted element contains success message for inventory
        if (e.target && e.target.classList && e.target.classList.contains('alert-success')) {
            // If we see a success message, refresh the condition monitoring
            setTimeout(function() {
                const conditionTable = document.getElementById('inventoryConditionTable');
                if (conditionTable) {
                    updateInventoryConditions();
                }
                
                // Refresh dashboard stats when inventory is updated
                if (typeof loadDashboardStats === 'function') {
                    loadDashboardStats();
                }
            }, 1000);
        }
    });
    
    // Add event listener to ensure dashboard stats are refreshed after PO/PAR operations
    ['addPOModal', 'addPARModal'].forEach(function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                // Only update if we're on the dashboard section
                // Fixed typo in selector and added check to avoid errors
                const dashboardSection = document.querySelector('.dashboard-section.active');
                if (dashboardSection && typeof loadDashboardStats === 'function') {
                    // Destroy any existing charts before refreshing
                    if (window.inventoryChart && typeof window.inventoryChart.destroy === 'function') {
                        try {
                            window.inventoryChart.destroy();
                        } catch (e) {
                            console.error('Error destroying inventory chart:', e);
                        }
                        window.inventoryChart = null;
                    }
                    
                    if (window.stockStatusChart && typeof window.stockStatusChart.destroy === 'function') {
                        try {
                            window.stockStatusChart.destroy();
                        } catch (e) {
                            console.error('Error destroying stock status chart:', e);
                        }
                        window.stockStatusChart = null;
                    }
                    
                    // Clear canvases to ensure they're ready for new charts
                    const inventoryCanvas = document.getElementById('inventoryChart');
                    if (inventoryCanvas) {
                        inventoryCanvas.getContext('2d').clearRect(0, 0, inventoryCanvas.width, inventoryCanvas.height);
                    }
                    
                    const stockCanvas = document.getElementById('stockStatusChart');
                    if (stockCanvas) {
                        stockCanvas.getContext('2d').clearRect(0, 0, stockCanvas.width, stockCanvas.height);
                    }
                    
                    // Refresh dashboard stats after a short delay
                    setTimeout(loadDashboardStats, 1000);
                }
            });
        }
    });
});
    // Format number helper for the entire page
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Function to refresh prediction data
    function refreshPredictions() {
        fetch('ml_prediction.php?action=get_prediction&model=linear&include_historical=true&track_po=true&track_par=true')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.yearly_forecast && data.yearly_forecast.length > 0) {
                    // Process data for chart
                    const labels = [];
                    const demandData = [];
                    const poData = [];
                    const parData = [];

                    data.yearly_forecast.forEach(item => {
                        const [year, month] = item.period.split('-');
                        const monthName = new Date(year, month - 1, 1).toLocaleString('default', {
                            month: 'short'
                        });
                        labels.push(monthName + ' ' + year);
                        demandData.push(item.demand);
                        poData.push(item.po_amount);
                        parData.push(item.par_amount);
                    });

                    // Update chart
                    if (window.demandPredictionChart) {
                        window.demandPredictionChart.data.labels = labels;
                        window.demandPredictionChart.data.datasets[0].data = demandData;
                        window.demandPredictionChart.data.datasets[1].data = poData;
                        window.demandPredictionChart.data.datasets[2].data = parData;
                        window.demandPredictionChart.update();
                    }

                    // Update prediction metrics
                    let yearlyTotal = 0;
                    let parYearlyTotal = 0;

                    data.yearly_forecast.forEach(month => {
                        yearlyTotal += month.po_amount;
                        parYearlyTotal += month.par_amount;
                    });

                    const monthlyAvg = yearlyTotal / data.yearly_forecast.length;
                    const quarterlyAvg = yearlyTotal / 4;

                    // Update prediction values
                    document.getElementById('nextWeekPrediction').innerHTML =
                        '₱' + formatNumber(Math.round(yearlyTotal)) +
                        ' <small class="text-muted">(PAR: ₱' + formatNumber(Math.round(parYearlyTotal)) + ')</small>';

                    document.getElementById('nextMonthPrediction').innerHTML =
                        '₱' + formatNumber(Math.round(monthlyAvg));

                    document.getElementById('nextQuarterPrediction').innerHTML =
                        '₱' + formatNumber(Math.round(quarterlyAvg));

                    // Update certainty indicator
                    const certaintyEl = document.getElementById('predictionCertainty');
                    if (certaintyEl) {
                        const certainty = data.confidence_score || 75;
                        certaintyEl.style.width = certainty + '%';

                        if (certainty < 50) {
                            certaintyEl.className = 'progress-bar bg-danger';
                        } else if (certainty < 75) {
                            certaintyEl.className = 'progress-bar bg-warning';
                        } else {
                            certaintyEl.className = 'progress-bar bg-success';
                        }
                    }

                    // Update inventory alerts
                    if (data.alerts) {
                        updateInventoryAlerts(data.alerts);
                    }

                    // Update tracking metrics
                    updateTrackedItems(data.yearly_forecast);

                    // Update health indicators
                    if (data.inventory_health) {
                        const inventoryHealth = document.getElementById('inventoryHealth');
                        if (inventoryHealth) {
                            inventoryHealth.style.width = data.inventory_health + '%';
                            inventoryHealth.className = `progress-bar ${data.inventory_health < 50 ? 'bg-danger' : data.inventory_health < 75 ? 'bg-warning' : 'bg-success'}`;
                        }
                    }

                    if (data.po_efficiency) {
                        const poEfficiency = document.getElementById('poEfficiency');
                        if (poEfficiency) {
                            poEfficiency.style.width = data.po_efficiency + '%';
                            poEfficiency.className = `progress-bar ${data.po_efficiency < 50 ? 'bg-danger' : data.po_efficiency < 75 ? 'bg-warning' : 'bg-success'}`;
                        }
                    }

                    if (data.par_health) {
                        const parHealth = document.getElementById('parHealth');
                        if (parHealth) {
                            parHealth.style.width = data.par_health + '%';
                            parHealth.className = `progress-bar ${data.par_health < 50 ? 'bg-danger' : data.par_health < 75 ? 'bg-warning' : 'bg-success'}`;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error refreshing predictions:', error);
            });
    }

    // Update notification badge and dropdown
    function updateNotificationsBadge(alerts) {
        const badge = document.getElementById('notificationBadge');
        const expiringItems = document.getElementById('expiringItems');
        const expiredItems = document.getElementById('expiredItems');

        if (!badge || !expiringItems || !expiredItems) return;

        if (alerts && alerts.length > 0) {
            // Set badge count
            badge.textContent = alerts.length;
            badge.classList.remove('d-none');

            // Clear existing notifications
            expiringItems.innerHTML = '';
            expiredItems.innerHTML = '';

            let expiringCount = 0;
            let expiredCount = 0;

            // Sort alerts into expiring and expired
            alerts.forEach(alert => {
                const item = document.createElement('div');
                item.className = 'notification-item py-2 border-bottom';

                if (alert.type === 'warranty') {
                    // For warranty alerts
                    item.innerHTML = `
                        <div class="d-flex">
                            <div class="me-2"><i class="bi bi-clock-history text-warning"></i></div>
                            <div class="small">${alert.message}</div>
                        </div>
                    `;
                    expiringItems.appendChild(item);
                    expiringCount++;
                } else if (alert.type === 'par' || alert.type === 'expired') {
                    // For expired items
                    item.innerHTML = `
                        <div class="d-flex">
                            <div class="me-2"><i class="bi bi-x-circle text-danger"></i></div>
                            <div class="small">${alert.message}</div>
                        </div>
                    `;
                    expiredItems.appendChild(item);
                    expiredCount++;
                }
            });

            // Show "no items" message if needed
            if (expiringCount === 0) {
                expiringItems.innerHTML = '<div class="text-muted small py-2">No items expiring soon</div>';
            }

            if (expiredCount === 0) {
                expiredItems.innerHTML = '<div class="text-muted small py-2">No expired items</div>';
            }
        } else {
            // No alerts
            badge.textContent = '0';
            badge.classList.add('d-none');
            expiringItems.innerHTML = '<div class="text-muted small py-2">No items expiring soon</div>';
            expiredItems.innerHTML = '<div class="text-muted small py-2">No expired items</div>';
        }
    }

    // Update tracked items count and health indicators
    function updateTrackedItems(forecast) {
        if (!forecast || forecast.length === 0) return;

        // Calculate total PO and PAR amounts
        let totalPO = 0;
        let totalPAR = 0;
        let totalItems = 0;

        forecast.forEach(month => {
            totalPO += month.po_amount || 0;
            totalPAR += month.par_amount || 0;
            totalItems += month.demand || 0;
        });

        // Update tracked items count
        const trackedItemsEl = document.getElementById('trackedItemsCount');
        if (trackedItemsEl) {
            trackedItemsEl.textContent = totalItems;
        }

        // Update analyzed PO count
        const analyzedPOEl = document.getElementById('analyzedPOCount');
        if (analyzedPOEl) {
            // Estimate number of POs based on average PO amount
            const estimatedPOs = Math.max(1, Math.round(totalPO / 15000));
            analyzedPOEl.textContent = estimatedPOs;
        }

        // Update expiring PAR count
        const expiringPAREl = document.getElementById('expiringPARCount');
        if (expiringPAREl) {
            // Estimate number of PARs near expiration (roughly 10% of PAR value)
            const expiringCount = Math.round((totalPAR / 30000) * 10) / 10;
            expiringPAREl.textContent = Math.max(0, expiringCount);
        }

        // Update inventory health
        const inventoryHealth = document.getElementById('inventoryHealth');
        if (inventoryHealth) {
            const firstMonthDemand = forecast[0].demand || 0;
            const lastMonthDemand = forecast[forecast.length - 1].demand || 0;
            const growthRate = firstMonthDemand > 0 ? ((lastMonthDemand - firstMonthDemand) / firstMonthDemand) * 100 : 0;

            let healthScore = 75;
            if (growthRate > 20) {
                healthScore = 95;
            } else if (growthRate > 10) {
                healthScore = 85;
            } else if (growthRate < -10) {
                healthScore = 60;
            } else if (growthRate < -20) {
                healthScore = 40;
            }

            inventoryHealth.style.width = healthScore + '%';
            if (healthScore < 50) {
                inventoryHealth.className = 'progress-bar bg-danger';
            } else if (healthScore < 70) {
                inventoryHealth.className = 'progress-bar bg-warning';
            } else {
                inventoryHealth.className = 'progress-bar bg-success';
            }
        }

        // Update PO efficiency
        const poEfficiency = document.getElementById('poEfficiency');
        if (poEfficiency) {
            // Calculate efficiency based on PO to PAR ratio
            const efficiency = totalPAR > 0 ? (totalPO / totalPAR) * 100 : 100;
            const efficiencyScore = Math.min(100, Math.max(10, efficiency));

            poEfficiency.style.width = efficiencyScore + '%';
            if (efficiencyScore < 50) {
                poEfficiency.className = 'progress-bar bg-danger';
            } else if (efficiencyScore < 75) {
                poEfficiency.className = 'progress-bar bg-warning';
            } else {
                poEfficiency.className = 'progress-bar bg-success';
            }
        }

        // Update PAR health
        const parHealth = document.getElementById('parHealth');
        if (parHealth) {
            // PAR health is based on inverse of PAR to PO ratio (lower is better)
            const ratio = totalPO > 0 ? totalPAR / totalPO : 1;

            let healthScore = 85;
            if (ratio > 1.2) {
                healthScore = 40; // PAR much higher than PO - bad
            } else if (ratio > 1.0) {
                healthScore = 65; // PAR higher than PO - concerning
            } else if (ratio > 0.8) {
                healthScore = 85; // PAR close to PO - good
            } else {
                healthScore = 95; // PAR much less than PO - excellent
            }

            parHealth.style.width = healthScore + '%';
            if (healthScore < 50) {
                parHealth.className = 'progress-bar bg-danger';
            } else if (healthScore < 75) {
                parHealth.className = 'progress-bar bg-warning';
            } else {
                parHealth.className = 'progress-bar bg-success';
            }
        }
    }

    // IoT Blockchain Integration Functions
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize IoT Integration
        initializeIoT();

        // Initialize Blockchain functionality
        initializeBlockchain();

        // Initialize PO and PAR prediction functionality
        initializePredictions();

        // Initialize Inventory Condition Tracking
        initializeConditionTracking();
    });

    // Initialize IoT functionality
    function initializeIoT() {
        // Update IoT data on page load
        updateIoTData();

        // Add event listener for refresh button
        const refreshBtn = document.getElementById('refreshIoTData');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                updateIoTData(true);
            });
        }

        // Add event listener for sensors refresh button
        const sensorsRefreshBtn = document.getElementById('refreshSensorsData');
        if (sensorsRefreshBtn) {
            sensorsRefreshBtn.addEventListener('click', function() {
                updateIoTData(true);
            });
        }
    }

    // Update IoT sensor data
    function updateIoTData(showLoading = false) {
        if (showLoading) {
            // Show loading indicators
            document.getElementById('activeSensors').innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8"></span></div>';
            document.getElementById('dataPoints').innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8"></span></div>';
            document.getElementById('lastUpdate').innerHTML = '<div class="placeholder-glow"><span class="placeholder col-8"></span></div>';
        }

        // Simulate IoT data - in a real implementation, this would fetch from your IoT API
        setTimeout(function() {
            const data = {
                active_sensors: Math.floor(Math.random() * 5) + 2,
                data_points: Math.floor(Math.random() * 2000) + 500,
                last_update: Math.floor(Math.random() * 10) + 1,
                health: Math.floor(Math.random() * 15) + 85,
                sensors: [{
                        id: 'SEN-001',
                        location: 'Warehouse A',
                        type: 'Temperature',
                        reading: (20 + Math.random() * 5).toFixed(1) + '°C',
                        status: 'Normal',
                        hash: generateRandomHash()
                    },
                    {
                        id: 'SEN-002',
                        location: 'Office B',
                        type: 'Humidity',
                        reading: Math.floor(Math.random() * 30 + 50) + '%',
                        status: 'Normal',
                        hash: generateRandomHash()
                    },
                    {
                        id: 'SEN-003',
                        location: 'Storage C',
                        type: 'Motion',
                        reading: Math.random() > 0.8 ? 'Detected' : 'Clear',
                        status: Math.random() > 0.8 ? 'Alert' : 'Normal',
                        hash: generateRandomHash()
                    }
                ]
            };

            // Update the UI with IoT data
            updateIoTDisplay(data);
        }, 1000);
    }

    // Update IoT display elements with data
    function updateIoTDisplay(data) {
        // Update summary statistics
        document.getElementById('activeSensors').textContent = data.active_sensors;
        document.getElementById('dataPoints').textContent = formatNumber(data.data_points);
        document.getElementById('lastUpdate').textContent = data.last_update + ' mins ago';

        // Update health indicator
        const healthBar = document.getElementById('iotHealthStatus');
        if (healthBar) {
            healthBar.style.width = data.health + '%';
            healthBar.className = `progress-bar ${data.health < 50 ? 'bg-danger' : data.health < 75 ? 'bg-warning' : 'bg-success'}`;
        }

        // Update sensor table
        updateSensorTable(data.sensors);
    }

    // Update the sensor data table
    function updateSensorTable(sensors) {
        const tableBody = document.getElementById('iotSensorTable');
        if (!tableBody || !sensors || sensors.length === 0) return;

        let html = '';

        sensors.forEach(sensor => {
            html += `
            <tr>
                <td>${sensor.id}</td>
                <td>${sensor.location}</td>
                <td>${sensor.type}</td>
                <td>${sensor.reading}</td>
                <td><span class="badge ${sensor.status === 'Normal' ? 'bg-success' : 'bg-warning'}">${sensor.status}</span></td>
                <td><code class="small">${sensor.hash}</code></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary view-sensor" data-sensor-id="${sensor.id}">View</button>
                </td>
            </tr>
            `;
        });

        tableBody.innerHTML = html;
    }

    // Initialize blockchain functionality
    function initializeBlockchain() {
        // Update blockchain data on page load
        updateBlockchainData();

        // Set up blockchain details button
        const detailsButton = document.getElementById('viewBlockchainDetails');
        if (detailsButton) {
            detailsButton.addEventListener('click', function() {
                alert('Blockchain explorer would be displayed here in a production environment. This would show transaction history, verification status, and security checks for PO and PAR data.');
            });
        }
    }

    // Update blockchain data display
    function updateBlockchainData() {
        // Simulate blockchain data - in a real implementation, this would fetch from your blockchain API
        setTimeout(function() {
            const data = {
                total_transactions: Math.floor(Math.random() * 100) + 50,
                chain_health: Math.floor(Math.random() * 5) + 95,
                last_block: '#' + Math.floor(Math.random() * 10000 + 15000),
                recent_transactions: [{
                        type: 'PO_CREATE',
                        hash: generateRandomHash(),
                        timestamp: new Date(Date.now() - Math.random() * 3600000).toISOString()
                    },
                    {
                        type: 'PAR_UPDATE',
                        hash: generateRandomHash(),
                        timestamp: new Date(Date.now() - Math.random() * 7200000).toISOString()
                    },
                    {
                        type: 'INVENTORY',
                        hash: generateRandomHash(),
                        timestamp: new Date(Date.now() - Math.random() * 10800000).toISOString()
                    }
                ]
            };

            // Update blockchain display elements
            document.getElementById('totalTransactions').textContent = data.total_transactions;
            document.getElementById('chainHealth').textContent = data.chain_health + '%';
            document.getElementById('lastBlock').textContent = data.last_block;

            // Update recent transactions
            updateRecentTransactions(data.recent_transactions);
        }, 1000);
    }

    // Update recent blockchain transactions display
    function updateRecentTransactions(transactions) {
        const container = document.getElementById('recentTransactions');
        if (!container || !transactions || transactions.length === 0) return;

        let html = '';
        transactions.forEach(tx => {
            html += `
            <div class="transaction-item d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <span class="badge bg-info me-2">${tx.type}</span>
                    <code class="small">${tx.hash}</code>
                </div>
                <small class="text-muted">${formatTimeAgo(new Date(tx.timestamp))}</small>
            </div>
            `;
        });

        container.innerHTML = html;
    }

    // Initialize PO and PAR prediction functionality
    function initializePredictions() {
        // Set up PO prediction input
        const poInput = document.getElementById('poAmountInput');
        const poCalcBtn = document.getElementById('calculatePoPrediction');

        if (poInput && poCalcBtn) {
            poCalcBtn.addEventListener('click', function() {
                calculatePoPrediction(parseFloat(poInput.value) || 0);
            });

            poInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    calculatePoPrediction(parseFloat(poInput.value) || 0);
                }
            });
        }

        // Set up PAR prediction input
        const parInput = document.getElementById('parAmountInput');
        const parCalcBtn = document.getElementById('calculateParPrediction');

        if (parInput && parCalcBtn) {
            parCalcBtn.addEventListener('click', function() {
                calculateParPrediction(parseFloat(parInput.value) || 0);
            });

            parInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    calculateParPrediction(parseFloat(parInput.value) || 0);
                }
            });
        }

        // Initialize with default values
        calculatePoPrediction(0);
        calculateParPrediction(0);

        // Initialize with default values
        calculatePoPrediction(0);
        calculateParPrediction(0);

        // Initialize condition tracking
        initializeConditionTracking();
    }

    // Calculate PO prediction
    function calculatePoPrediction(poAmount) {
        // Update current PO amount display
        document.getElementById('currentPOAmount').textContent = '₱' + formatNumber(poAmount.toFixed(2));

        // Calculate PAR prediction (in a real implementation, this would use ML model)
        // For demo, we'll use a simple calculation with some randomness
        const parRatio = 0.6 + (Math.random() * 0.3); // 60-90% of PO amount
        const predictedPAR = poAmount * parRatio;

        // Calculate health score based on ratio
        let healthScore = 0;
        if (parRatio > 0.9) {
            healthScore = 45; // PAR almost equal to PO - concerning
        } else if (parRatio > 0.8) {
            healthScore = 65; // PAR higher than PO - concerning
        } else if (parRatio > 0.7) {
            healthScore = 85; // PAR close to PO - good
        } else {
            healthScore = 95; // PAR much less than PO - excellent
        }

        // Update prediction display
        document.getElementById('predictedPARAmount').textContent = '₱' + formatNumber(predictedPAR.toFixed(2));
        document.getElementById('poPARRatioValue').textContent = parRatio.toFixed(2);

        // Update health indicator
        const healthBar = document.getElementById('poHealthIndicator');
        if (healthBar) {
            healthBar.style.width = healthScore + '%';
            healthBar.className = `progress-bar ${healthScore < 50 ? 'bg-danger' : healthScore < 75 ? 'bg-warning' : 'bg-success'}`;
        }

        // Generate verification hash
        if (poAmount > 0) {
            document.getElementById('poVerificationHash').textContent = generateRandomHash();
        } else {
            document.getElementById('poVerificationHash').textContent = 'Not verified';
        }
    }

    // Calculate PAR prediction
    function calculateParPrediction(parAmount) {
        // Update current PAR amount display
        document.getElementById('currentPARAmount').textContent = '₱' + formatNumber(parAmount.toFixed(2));

        // Calculate related PO (in a real implementation, this would use ML model)
        // For demo, we'll use a simple calculation with some randomness
        const poRatio = 1.1 + (Math.random() * 0.4); // 110-150% of PAR amount
        const relatedPO = parAmount * poRatio;

        // Calculate utilization percentage
        const utilization = parAmount > 0 ? (parAmount / relatedPO) * 100 : 0;

        // Calculate health score based on utilization
        let healthScore = 0;
        if (utilization > 100) {
            // PAR exceeds expectations - over-utilized
            healthScore = Math.max(0, 100 - ((utilization - 100) * 0.5));
        } else if (utilization < 50) {
            // PAR under-utilized - wasteful
            healthScore = Math.max(0, utilization);
        } else {
            // Ideal range: 50-100% utilization
            healthScore = 75 + (utilization * 0.25);
        }

        // Update prediction display
        document.getElementById('relatedPOAmount').textContent = '₱' + formatNumber(relatedPO.toFixed(2));
        document.getElementById('parPOUtilization').textContent = Math.round(utilization) + '%';

        // Update health indicator
        const healthBar = document.getElementById('parHealthIndicator');
        if (healthBar) {
            healthBar.style.width = healthScore + '%';
            healthBar.className = `progress-bar ${healthScore < 50 ? 'bg-danger' : healthScore < 75 ? 'bg-warning' : 'bg-success'}`;
        }

        // Generate verification hash
        if (parAmount > 0) {
            document.getElementById('parVerificationHash').textContent = generateRandomHash();
        } else {
            document.getElementById('parVerificationHash').textContent = 'Not verified';
        }
    }

    // Initialize inventory condition tracking
    function initializeConditionTracking() {
        // Add event listener for refresh button
        const refreshBtn = document.getElementById('refreshConditionData');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                updateInventoryConditions();
            });
        }

        // Initialize with data
        updateInventoryConditions();
    }

    // Function to update inventory conditions table
    function updateInventoryConditions() {
        const tableBody = document.getElementById('inventoryConditionTable');
        if (!tableBody) return;

        // Show loading state
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-muted">Loading inventory condition data...</span>
                </td>
            </tr>
        `;

        // Fetch real data from the backend
        fetch('get_inventory_conditions.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            }) 
            .then(items => {
                let html = '';

                // Check if error message was returned
                if (items.length === 1 && items[0].error) {
                    html = `
                        <tr>
                            <td colspan="8" class="text-center py-3">
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    ${items[0].message}
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-primary" id="setupConditionTablesInline">
                                        <i class="bi bi-wrench"></i> Setup Condition Monitoring
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;

                    tableBody.innerHTML = html;

                    // Add event listener to the inline setup button
                    document.getElementById('setupConditionTablesInline').addEventListener('click', setupConditionMonitoring);
                    return;
                }

                if (items.length === 0) {
                    html = `
                        <tr>
                            <td colspan="8" class="text-center py-3">
                                <div class="text-muted">No items to display</div>
                            </td>
                        </tr>
                    `;
                } else {
                    items.forEach(item => {
                        // Get maintenance prediction if available
                        const hasPrediction = item.prediction && item.prediction.maintenance;
                        const maintenanceUrgency = hasPrediction ? item.prediction.maintenance.urgency : 'unknown';
                        const maintenanceMessage = hasPrediction ? item.prediction.maintenance.message : 'Not available';
                        const expiryStatus = hasPrediction && item.prediction.expiry ? item.prediction.expiry.status : 'unknown';
                        const expiryMessage = hasPrediction && item.prediction.expiry ? item.prediction.expiry.message : 'Not available';

                        // Determine prediction color classes
                        let maintenanceClass = 'bg-secondary';
                        switch (maintenanceUrgency) {
                            case 'immediate':
                                maintenanceClass = 'bg-danger';
                                break;
                            case 'soon':
                                maintenanceClass = 'bg-warning';
                                break;
                            case 'upcoming':
                                maintenanceClass = 'bg-info';
                                break;
                            case 'none':
                                maintenanceClass = 'bg-success';
                                break;
                        }

                        let expiryClass = 'bg-secondary';
                        switch (expiryStatus) {
                            case 'expired':
                                expiryClass = 'bg-danger';
                                break;
                            case 'critical':
                                expiryClass = 'bg-warning';
                                break;
                            case 'warning':
                                expiryClass = 'bg-info';
                                break;
                            case 'normal':
                                expiryClass = 'bg-success';
                                break;
                        }

                        html += `
                        <tr>
                            <td>${item.item_name || 'Unknown'}</td>
                            <td>${item.serial || 'N/A'}</td>
                            <td>${item.location || 'Unknown'}</td>
                            <td>${item.condition || 'Unknown'}</td>
                            <td><span class="badge ${item.status_class || 'bg-secondary'}">${item.status || 'Unknown'}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge ${maintenanceClass} mb-1" data-bs-toggle="tooltip" title="${maintenanceMessage}">
                                        ${maintenanceUrgency === 'unknown' ? 'Unknown' : maintenanceUrgency.charAt(0).toUpperCase() + maintenanceUrgency.slice(1)}
                                    </span>
                                    <span class="badge ${expiryClass}" data-bs-toggle="tooltip" title="${expiryMessage}">
                                        ${expiryStatus === 'unknown' ? 'Unknown' : expiryStatus.charAt(0).toUpperCase() + expiryStatus.slice(1)}
                                    </span>
                                </div>
                            </td>
                            <td>${item.last_updated || 'Never'}</td>
                            <td>
                    <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary track-item" data-item-id="${item.item_id}">Track</button>
                                    <button class="btn btn-sm btn-outline-warning update-condition" data-item-id="${item.item_id}" data-bs-toggle="modal" data-bs-target="#updateConditionModal">Update</button>
                                </div>
                            </td>
                        </tr>
                        `;
                    });
                }

                tableBody.innerHTML = html;

                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Add event listeners to the track buttons
                document.querySelectorAll('.track-item').forEach(button => {
                    button.addEventListener('click', () => {
                        const itemId = button.getAttribute('data-item-id');
                        showItemTrackingModal(itemId);
                    });
                });

                // Add event listeners to the update condition buttons
                document.querySelectorAll('.update-condition').forEach(button => {
                    button.addEventListener('click', () => {
                        const itemId = button.getAttribute('data-item-id');
                        prepareUpdateConditionModal(itemId);
                    });
                });
            })
            .catch(error => {
                console.error('Error fetching inventory conditions:', error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-3">
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                Error loading data: ${error.message}
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" id="retryFetchConditions">
                                    <i class="bi bi-arrow-clockwise"></i> Retry
                        </button>
                                <button class="btn btn-sm btn-outline-secondary" id="checkSetupConditionTables">
                                    <i class="bi bi-wrench"></i> Check Setup
                        </button>
                    </div>
                        </td>
                    </tr>
                `;

                // Add event listener to the retry button
                document.getElementById('retryFetchConditions').addEventListener('click', updateInventoryConditions);

                // Add event listener to the check setup button
                document.getElementById('checkSetupConditionTables').addEventListener('click', setupConditionMonitoring);
            });
    }

    // Function to show item tracking modal
    function showItemTrackingModal(itemId) {
        // Fetch item history and details
        fetch(`get_inventory_conditions.php?item_id=${itemId}&track_history=true`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    Swal.fire({
                        title: 'Item Not Found',
                        text: 'The requested item could not be found.',
                        icon: 'error'
                    });
                    return;
                }

                const item = data[0];

                // Create tracking modal content
                let modalContent = `
                    <div class="modal-header bg-gradient-primary text-white">
                        <h5 class="modal-title">Item Tracking: ${item.item_name}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted">Serial Number</div>
                                    <div class="fw-bold">${item.serial || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="small text-muted">Location</div>
                                    <div class="fw-bold">${item.location || 'Unknown'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-light rounded mb-4">
                            <div class="small text-muted">Current Condition</div>
                            <div class="d-flex align-items-center">
                                <div class="fw-bold me-2">${item.condition || 'Unknown'}</div>
                                <span class="badge ${item.status_class || 'bg-secondary'}">${item.status || 'Unknown'}</span>
                            </div>
                            <div class="mt-2 small">${item.condition_details || 'No additional details'}</div>
                        </div>
                        
                        <div class="blockchain-verification mb-4 p-3 rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-check text-success me-2"></i>
                                <div>
                                    <div class="small fw-bold">Blockchain Verification</div>
                                    <div class="verification-hash small text-muted">
                                        <code>${item.blockchain_hash || 'Not verified in blockchain'}</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${item.prediction ? `
                        <h6 class="mb-3">Maintenance & Lifecycle Prediction</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header d-flex align-items-center bg-light py-2">
                                        <i class="bi bi-tools text-primary me-2"></i>
                                        <h6 class="mb-0">Maintenance Prediction</h6>
                </div>
                <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <span class="badge ${item.prediction.maintenance.urgency === 'immediate' ? 'bg-danger' : 
                                                                    item.prediction.maintenance.urgency === 'soon' ? 'bg-warning' : 
                                                                    item.prediction.maintenance.urgency === 'upcoming' ? 'bg-info' : 'bg-success'} 
                                                                    p-2">
                                                    <i class="bi ${item.prediction.maintenance.urgency === 'immediate' ? 'bi-exclamation-triangle' : 
                                                                item.prediction.maintenance.urgency === 'soon' ? 'bi-clock-history' : 
                                                                item.prediction.maintenance.urgency === 'upcoming' ? 'bi-calendar' : 'bi-check-circle'}"></i>
                                                </span>
                        </div>
                                            <div>
                                                <div class="small text-muted">Urgency</div>
                                                <div class="fw-bold">${item.prediction.maintenance.urgency ? 
                                                                    item.prediction.maintenance.urgency.charAt(0).toUpperCase() + 
                                                                    item.prediction.maintenance.urgency.slice(1) : 'Unknown'}</div>
                    </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Days Until Maintenance</div>
                                            <div class="fw-bold">${item.prediction.maintenance.days_until_needed || 'N/A'} days</div>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar ${item.prediction.maintenance.urgency === 'immediate' ? 'bg-danger' : 
                                                                        item.prediction.maintenance.urgency === 'soon' ? 'bg-warning' : 
                                                                        item.prediction.maintenance.urgency === 'upcoming' ? 'bg-info' : 'bg-success'}" 
                                                    style="width: ${Math.min(100, Math.max(0, 100 - (item.prediction.maintenance.days_until_needed || 0)))}%"></div>
                                            </div>
                                        </div>
                            <div class="text-muted small">
                                            ${item.prediction.maintenance.message || 'No prediction available'}
                            </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-header d-flex align-items-center bg-light py-2">
                                        <i class="bi bi-hourglass-split text-primary me-2"></i>
                                        <h6 class="mb-0">Expiry Prediction</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <span class="badge ${item.prediction.expiry.status === 'expired' ? 'bg-danger' : 
                                                                    item.prediction.expiry.status === 'critical' ? 'bg-warning' : 
                                                                    item.prediction.expiry.status === 'warning' ? 'bg-info' : 'bg-success'} 
                                                                    p-2">
                                                    <i class="bi ${item.prediction.expiry.status === 'expired' ? 'bi-x-circle' : 
                                                                item.prediction.expiry.status === 'critical' ? 'bi-exclamation-circle' : 
                                                                item.prediction.expiry.status === 'warning' ? 'bi-exclamation' : 'bi-check-circle'}"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="small text-muted">Status</div>
                                                <div class="fw-bold">${item.prediction.expiry.status ? 
                                                                    item.prediction.expiry.status.charAt(0).toUpperCase() + 
                                                                    item.prediction.expiry.status.slice(1) : 'Unknown'}</div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Days Until End-of-Life</div>
                                            <div class="fw-bold">${item.prediction.expiry.days_until_expiry || 'N/A'} days</div>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar ${item.prediction.expiry.status === 'expired' ? 'bg-danger' : 
                                                                        item.prediction.expiry.status === 'critical' ? 'bg-warning' : 
                                                                        item.prediction.expiry.status === 'warning' ? 'bg-info' : 'bg-success'}" 
                                                    style="width: ${Math.min(100, Math.max(0, 100 - (item.prediction.expiry.days_until_expiry || 0) / 40))}%"></div>
                                            </div>
                                        </div>
                                        <div class="text-muted small">
                                            ${item.prediction.expiry.message || 'No prediction available'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : `
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Prediction data not available for this item.
                        </div>
                        `}
                        
                        ${item.history && item.history.length > 0 ? `
                        <h6 class="mb-3">Condition History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Condition</th>
                                        <th>Status</th>
                                        <th>Blockchain Hash</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${item.history.map(entry => `
                                    <tr>
                                        <td>${entry.timestamp}</td>
                                        <td>${entry.condition}</td>
                                        <td>${entry.status}</td>
                                        <td><code class="small">${entry.hash}</code></td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : `
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-clock-history me-2"></i>
                            No condition history available
                        </div>
                        `}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary update-condition-btn" data-item-id="${item.item_id}" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#updateConditionModal">
                            <i class="bi bi-pencil"></i> Update Condition
                            </button>
                        </div>
                `;

                // Create and show the modal
                const modalElement = document.createElement('div');
                modalElement.className = 'modal fade';
                modalElement.id = 'itemTrackingModal';
                modalElement.setAttribute('tabindex', '-1');
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            ${modalContent}
                    </div>
                </div>
                `;

                document.body.appendChild(modalElement);

                // Initialize and show the modal
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                // Add event listener to the update button inside the modal
                modalElement.querySelector('.update-condition-btn').addEventListener('click', () => {
                    prepareUpdateConditionModal(item.item_id);
                });

                // Remove the modal from DOM when it's hidden
                modalElement.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(modalElement);
                });
            })
            .catch(error => {
                console.error('Error fetching item tracking data:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to retrieve item tracking information.',
                    icon: 'error'
                });
            });
    }

    // Function to prepare update condition modal
    function prepareUpdateConditionModal(itemId) {
        // Fetch the current item data
        fetch(`get_inventory_conditions.php?item_id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    Swal.fire({
                        title: 'Item Not Found',
                        text: 'The requested item could not be found.',
                        icon: 'error'
                    });
                    return;
                }

                const item = data[0];

                // Check if update condition modal exists, create if not
                let modalElement = document.getElementById('updateConditionModal');
                if (!modalElement) {
                    modalElement = document.createElement('div');
                    modalElement.className = 'modal fade';
                    modalElement.id = 'updateConditionModal';
                    modalElement.setAttribute('tabindex', '-1');
                    modalElement.setAttribute('aria-hidden', 'true');
                    modalElement.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-white">
                                    <h5 class="modal-title">Update Item Condition</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
                                <div class="modal-body">
                                    <form id="updateConditionForm">
                                        <input type="hidden" id="updateItemId">
                                        <div class="mb-3">
                                            <label for="itemName" class="form-label">Item Name</label>
                                            <input type="text" class="form-control" id="itemName" readonly>
        </div>
                                        <div class="mb-3">
                                            <label for="itemCondition" class="form-label">Condition</label>
                                            <select class="form-select" id="itemCondition" required>
                                                <option value="">Select Condition</option>
                                                <option value="New">New</option>
                                                <option value="Good">Good</option>
                                                <option value="Fair">Fair</option>
                                                <option value="Poor">Poor</option>
                                </select>
                            </div>
                                        <div class="mb-3">
                                            <label for="conditionDetails" class="form-label">Condition Details</label>
                                            <textarea class="form-control" id="conditionDetails" rows="3" placeholder="Enter details about the item's condition..."></textarea>
                        </div>
                                    </form>
                    </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-warning" id="saveConditionBtn">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modalElement);

                    // Add event listener to the save button
                    document.getElementById('saveConditionBtn').addEventListener('click', saveItemCondition);
                }

                // Fill the form with current data
                document.getElementById('updateItemId').value = item.item_id;
                document.getElementById('itemName').value = item.item_name;
                document.getElementById('itemCondition').value = item.condition || '';
                document.getElementById('conditionDetails').value = item.condition_details || '';

                // Show the modal if it's not already visible
                const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modal.show();
            })
            .catch(error => {
                console.error('Error fetching item data for condition update:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to retrieve item information.',
                    icon: 'error'
                });
            });
    }

    // Function to save item condition
    function saveItemCondition() {
        const itemId = document.getElementById('updateItemId').value;
        const condition = document.getElementById('itemCondition').value;
        const conditionDetails = document.getElementById('conditionDetails').value;

        if (!condition) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a condition.',
                icon: 'warning'
            });
            return;
        }

        // Prepare data for API
        const data = {
            item_id: itemId,
            condition: condition,
            condition_details: conditionDetails
        };

        // Send update request
        fetch('update_item_condition.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Close the modal
                    const modal = document.getElementById('updateConditionModal');
                    bootstrap.Modal.getInstance(modal).hide();

                    // Show success message
                    Swal.fire({
                        title: 'Success',
                        text: 'Item condition updated successfully.',
                        icon: 'success'
                    });

                    // Refresh the inventory conditions table
                    updateInventoryConditions();
                } else {
                    throw new Error(result.message || 'Failed to update item condition');
                }
            })
            .catch(error => {
                console.error('Error updating item condition:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Failed to update item condition.',
                    icon: 'error'
                });
            });
    }

    // Format number with commas
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Format time ago
    function formatTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);

        let interval = Math.floor(seconds / 31536000);
        if (interval > 1) return interval + " years ago";
        if (interval === 1) return "1 year ago";

        interval = Math.floor(seconds / 2592000);
        if (interval > 1) return interval + " months ago";
        if (interval === 1) return "1 month ago";

        interval = Math.floor(seconds / 86400);
        if (interval > 1) return interval + " days ago";
        if (interval === 1) return "1 day ago";

        interval = Math.floor(seconds / 3600);
        if (interval > 1) return interval + " hours ago";
        if (interval === 1) return "1 hour ago";

        interval = Math.floor(seconds / 60);
        if (interval > 1) return interval + " minutes ago";
        if (interval === 1) return "1 minute ago";

        return Math.floor(seconds) + " seconds ago";
    }

    // Generate random hash for blockchain simulation
    function generateRandomHash() {
        const chars = '0123456789abcdef';
        let hash = '0x';
        for (let i = 0; i < 16; i++) {
            hash += chars[Math.floor(Math.random() * chars.length)];
        }
        return hash;
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize ML prediction data and charts on dashboard load
        function initPredictionSystem() {
            // Update prediction metrics on dashboard
            updatePredictionMetrics();

            // Setup prediction chart
            setupPredictionChart();

            // Add event listeners for prediction controls
            setupPredictionControls();
        }

        // Calculate predictions based on historical data
        function calculatePredictions(historicalData) {
            const predictions = {
                yearly: {
                    po: 0,
                    par: 0
                },
                monthly: {
                    po: 0,
                    par: 0
                },
                quarterly: {
                    po: 0,
                    par: 0
                }
            };

            if (!historicalData || historicalData.length === 0) {
                return predictions;
            }

            // Calculate average monthly growth rate
            let growthRate = 0;
            for (let i = 1; i < historicalData.length; i++) {
                const prevMonth = historicalData[i - 1];
                const currMonth = historicalData[i];
                if (prevMonth.po_amount > 0) {
                    growthRate += (currMonth.po_amount - prevMonth.po_amount) / prevMonth.po_amount;
                }
            }
            growthRate = growthRate / (historicalData.length - 1);

            // Get last month's values
            const lastMonth = historicalData[historicalData.length - 1];
            const basePoAmount = lastMonth.po_amount || 0;
            const baseParAmount = lastMonth.par_amount || 0;

            // Calculate yearly predictions with growth
            predictions.yearly.po = basePoAmount * 12 * (1 + growthRate);
            predictions.yearly.par = baseParAmount * 12 * (1 + growthRate * 0.8); // PAR grows slightly slower

            // Monthly and quarterly averages
            predictions.monthly.po = predictions.yearly.po / 12;
            predictions.monthly.par = predictions.yearly.par / 12;
            predictions.quarterly.po = predictions.yearly.po / 4;
            predictions.quarterly.par = predictions.yearly.par / 4;

            return predictions;
        }

        // Update prediction metrics display
        function updatePredictionMetrics() {
            // Get historical data from PO and PAR tables
            const historicalData = getHistoricalData();
            const predictions = calculatePredictions(historicalData);

            // Update display elements
            document.getElementById('nextWeekPrediction').innerHTML =
                '₱' + formatNumber(Math.round(predictions.yearly.po)) +
                ' <small class="text-muted">(PAR: ₱' + formatNumber(Math.round(predictions.yearly.par)) + ')</small>';

            document.getElementById('nextMonthPrediction').innerHTML =
                '₱' + formatNumber(Math.round(predictions.monthly.po));

            document.getElementById('nextQuarterPrediction').innerHTML =
                '₱' + formatNumber(Math.round(predictions.quarterly.po));

            // Update certainty indicator
            const certaintyEl = document.getElementById('predictionCertainty');
            const certainty = calculatePredictionCertainty(historicalData);
            certaintyEl.style.width = certainty + '%';
            certaintyEl.className = `progress-bar ${certainty < 50 ? 'bg-danger' : certainty < 75 ? 'bg-warning' : 'bg-success'}`;
        }

        // Get historical data from PO and PAR tables
        function getHistoricalData() {
            // This would normally come from your database
            // For now, we'll generate some sample data
            const today = new Date();
            const data = [];

            for (let i = 11; i >= 0; i--) {
                const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
                const baseAmount = 100000 + Math.random() * 50000;

                data.push({
                    period: date.toISOString().slice(0, 7),
                    po_amount: baseAmount,
                    par_amount: baseAmount * 0.8,
                    demand: Math.round(baseAmount / 1000)
                });
            }

            return data;
        }

        // Calculate prediction certainty based on data consistency
        function calculatePredictionCertainty(historicalData) {
            if (!historicalData || historicalData.length < 2) return 50;

            let volatility = 0;
            for (let i = 1; i < historicalData.length; i++) {
                const prevMonth = historicalData[i - 1];
                const currMonth = historicalData[i];
                if (prevMonth.po_amount > 0) {
                    const change = Math.abs((currMonth.po_amount - prevMonth.po_amount) / prevMonth.po_amount);
                    volatility += change;
                }
            }

            volatility = volatility / (historicalData.length - 1);
            const certainty = Math.max(10, Math.min(95, 100 - (volatility * 100)));
            return Math.round(certainty);
        }

        // Setup prediction chart
        function setupPredictionChart() {
            const ctx = document.getElementById('demandPredictionChart').getContext('2d');
            const historicalData = getHistoricalData();

            // Prepare data for chart
            const labels = historicalData.map(item => {
                const [year, month] = item.period.split('-');
                return new Date(year, month - 1).toLocaleString('default', {
                    month: 'short',
                    year: '2-digit'
                });
            });

            const poData = historicalData.map(item => item.po_amount);
            const parData = historicalData.map(item => item.par_amount);

            // Calculate future predictions
            const predictions = calculatePredictions(historicalData);
            const futurePeriods = 6; // Show 6 months of predictions

            for (let i = 0; i < futurePeriods; i++) {
                const date = new Date(historicalData[historicalData.length - 1].period);
                date.setMonth(date.getMonth() + i + 1);
                labels.push(date.toLocaleString('default', {
                    month: 'short',
                    year: '2-digit'
                }) + '*');

                const monthlyGrowth = predictions.monthly.po / 12;
                poData.push(predictions.monthly.po + (monthlyGrowth * i));
                parData.push(predictions.monthly.par + (monthlyGrowth * i * 0.8));
            }

            // Create chart
            window.demandPredictionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'PO Amount',
                            data: poData,
                            borderColor: 'rgba(255, 159, 64, 1)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'PAR Amount',
                            data: parData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += '₱' + formatNumber(context.parsed.y.toFixed(0));
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (₱)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + formatNumber(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Setup prediction control event listeners
        function setupPredictionControls() {
            document.getElementById('predictionModel').addEventListener('change', updatePredictions);
            document.getElementById('seasonalityFactor').addEventListener('change', updatePredictions);
            document.getElementById('includeExternalFactors').addEventListener('change', updatePredictions);
            document.getElementById('refreshPredictionBtn').addEventListener('click', updatePredictions);
        }

        // Update predictions when controls change
        function updatePredictions() {
            updatePredictionMetrics();
            if (window.demandPredictionChart) {
                window.demandPredictionChart.destroy();
            }
            setupPredictionChart();
        }

        // Add tracking to PO form submission
        document.getElementById('savePoBtn')?.addEventListener('click', function() {
            const poForm = document.getElementById('poForm');
            if (!poForm) return;

            // Get form data
            const formData = new FormData(poForm);

            // Calculate total amount
            let totalAmount = 0;
            document.querySelectorAll('#poItemsTable .amount').forEach(field => {
                totalAmount += parseFloat(field.value || 0);
            });

            // Update tracking data
            const trackingData = {
                po_no: formData.get('po_no'),
                date: formData.get('po_date'),
                total_amount: totalAmount,
                items: Array.from(document.querySelectorAll('#poItemsTable tbody tr')).map(row => ({
                    name: row.querySelector('.item-name')?.value,
                    quantity: parseInt(row.querySelector('.qty')?.value) || 0,
                    unit_cost: parseFloat(row.querySelector('.unit-cost')?.value) || 0,
                    amount: parseFloat(row.querySelector('.amount')?.value) || 0
                }))
            };

            document.getElementById('poTrackingData').value = JSON.stringify(trackingData);

            // After saving, update predictions
            setTimeout(updatePredictions, 1000);
        });

        // Add tracking to PAR form submission
        document.getElementById('saveParBtn')?.addEventListener('click', function() {
            const parForm = document.getElementById('parForm');
            if (!parForm) return;

            // Get form data
            const formData = new FormData(parForm);

            // Calculate total amount
            let totalAmount = 0;
            document.querySelectorAll('#parItemsTable .par-amount').forEach(field => {
                totalAmount += parseFloat(field.value || 0);
            });

            // Update tracking data
            const trackingData = {
                par_no: formData.get('parNo'),
                date: formData.get('dateAcquired'),
                total_amount: totalAmount,
                items: Array.from(document.querySelectorAll('#parItemsTable tbody tr')).map(row => ({
                    description: row.querySelector('.par-description')?.value,
                    quantity: parseInt(row.querySelector('.par-qty')?.value) || 0,
                    amount: parseFloat(row.querySelector('.par-amount')?.value) || 0
                }))
            };

            document.getElementById('parTrackingData').value = JSON.stringify(trackingData);

            // After saving, update predictions
            setTimeout(updatePredictions, 1000);
        });

        // Initialize prediction system if container exists
        if (document.getElementById('predictionSystemContainer')) {
            initPredictionSystem();
        }

        // Generate recommended PO based on predictions
        function generateRecommendedPO() {
            const historicalData = getHistoricalData();
            const predictions = calculatePredictions(historicalData);

            // Calculate recommended quantities and amounts
            const lastMonth = historicalData[historicalData.length - 1];
            const averageUnitCost = lastMonth.po_amount / (lastMonth.demand || 1);
            const predictedDemand = Math.round(predictions.monthly.po / averageUnitCost);
            const recommendedAmount = predictions.monthly.po * 1.1; // Add 10% buffer

            // Calculate confidence score
            const certainty = calculatePredictionCertainty(historicalData);

            // Show recommendation dialog
            Swal.fire({
                title: 'Generating Recommended PO',
                html: 'Analyzing historical data and predicting optimal quantities...',
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();

                    setTimeout(() => {
                        // Get trend analysis
                        const trend = analyzeTrend(historicalData);
                        const parRatio = calculateParRatio(historicalData);

                        Swal.fire({
                            title: 'Recommended PO Generated',
                            html: `
                            <div class="text-start">
                                <p><strong>Based on ML predictions, we recommend:</strong></p>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Create PO for ${predictedDemand} units</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Estimated amount: ₱${formatNumber(Math.round(recommendedAmount))}</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Expected demand trend: ${trend.description}</li>
                                    <li><i class="bi bi-info-circle-fill text-info me-2"></i> PAR ratio: ${parRatio}%</li>
                                </ul>
                                <div class="alert alert-info mt-3">
                                    <small>
                                        <i class="bi bi-lightbulb me-2"></i>
                                        ${generateRecommendationText(trend, parRatio)}
                                    </small>
            </div>
                                <p class="mt-3 text-muted small">Prediction certainty: ${certainty}%</p>
                        </div>
                        `,
                            icon: 'success',
                            confirmButtonText: 'Create PO',
                            showCancelButton: true,
                            cancelButtonText: 'Close'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Open PO modal with pre-filled values
                                const poModal = new bootstrap.Modal(document.getElementById('addPOModal'));
                                poModal.show();

                                // Pre-fill with recommended values
                                setTimeout(() => {
                                    // Generate PO number
                                    const poNo = 'AUTO-' + new Date().getTime().toString().slice(-6);
                                    document.getElementById('poNo').value = poNo;
                                    document.getElementById('poDate').valueAsDate = new Date();

                                    // Add recommended items
                                    const itemsTable = document.getElementById('poItemsTable').getElementsByTagName('tbody')[0];
                                    const recommendedItems = generateRecommendedItems(historicalData, predictedDemand, averageUnitCost);

                                    // Clear existing items
                                    itemsTable.innerHTML = '';

                                    // Add recommended items
                                    recommendedItems.forEach(item => {
                                        const row = itemsTable.insertRow();
                                        row.innerHTML = `
                                        <td><input type="text" class="form-control form-control-sm item-name" name="item_name[]" value="${item.name}"></td>
                                        <td><input type="text" class="form-control form-control-sm item-unit" name="unit[]" value="${item.unit}"></td>
                                        <td><textarea class="form-control form-control-sm item-description" name="item_description[]" rows="2">${item.description}</textarea></td>
                                        <td><input type="number" class="form-control form-control-sm qty" name="quantity[]" value="${item.quantity}"></td>
                                        <td><input type="number" class="form-control form-control-sm unit-cost" name="unit_cost[]" value="${item.unitCost}"></td>
                                        <td><input type="text" class="form-control form-control-sm amount" name="amount[]" value="${item.amount}" readonly></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    `;
                                    });

                                    // Update total amount
                                    updateTotalAmount();
                                }, 500);
                            }
                        });
                    }, 1500);
                }
            });
        }

        // Analyze trend from historical data
        function analyzeTrend(historicalData) {
            let growthRate = 0;
            for (let i = 1; i < historicalData.length; i++) {
                const prevMonth = historicalData[i - 1];
                const currMonth = historicalData[i];
                if (prevMonth.po_amount > 0) {
                    growthRate += (currMonth.po_amount - prevMonth.po_amount) / prevMonth.po_amount;
                }
            }
            growthRate = (growthRate / (historicalData.length - 1)) * 100;

            return {
                rate: growthRate,
                description: growthRate > 10 ? 'Strong increase' : growthRate > 5 ? 'Moderate increase' : growthRate > -5 ? 'Stable' : growthRate > -10 ? 'Moderate decrease' : 'Strong decrease'
            };
        }

        // Calculate PAR to PO ratio
        function calculateParRatio(historicalData) {
            const recentMonths = historicalData.slice(-3); // Last 3 months
            let totalPO = 0;
            let totalPAR = 0;

            recentMonths.forEach(month => { 
                totalPO += month.po_amount;
                totalPAR += month.par_amount;
            });

            return totalPO > 0 ? Math.round((totalPAR / totalPO) * 100) : 0;
        }

        // Generate recommendation text
        function generateRecommendationText(trend, parRatio) {
            let text = [];

            if (trend.rate > 10) {
                text.push('Consider increasing order quantities to meet growing demand.');
            } else if (trend.rate < -10) {
                text.push('Consider reducing order quantities due to decreasing demand.');
            }

            if (parRatio > 90) {
                text.push('High PAR utilization suggests need for increased inventory.');
            } else if (parRatio < 50) {
                text.push('Low PAR utilization indicates possible excess inventory.');
            }

            return text.join(' ') || 'Current inventory levels appear optimal.';
        }

        // Generate recommended items based on historical data
        function generateRecommendedItems(historicalData, predictedDemand, averageUnitCost) {
            // Analyze most common items from historical data
           

            // Calculate amounts
            return commonItems.map(item => ({
                ...item,
                amount: (item.quantity * item.unitCost).toFixed(2)
            }));
        }

        // Add event listener for generate PO button
        document.getElementById('generatePOBtn')?.addEventListener('click', generateRecommendedPO);

        // Update total amount calculation
        function updateTotalAmount() {
            let total = 0;
            document.querySelectorAll('#poItemsTable .amount').forEach(field => {
                total += parseFloat(field.value || 0);
            });
            document.getElementById('totalAmount').value = '₱' + formatNumber(total.toFixed(2));
        }
    });
    function updateURL(section) {
        // Update URL hash
        window.location.hash = section;
        
        // Make API call
        fetch(`/api/${section}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Handle API response
            console.log(`Loaded ${section} data:`, data);
        })
        .catch(error => {
            console.error(`Error loading ${section}:`, error);
        });
    }   