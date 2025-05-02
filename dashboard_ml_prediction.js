/**
 * Dashboard ML Prediction Script
 * Handles ML predictions display and tracking for the dashboard
 */

// Global variables
let mlPredictionData = null;
let newInventoryItems = [];

// Initialize the ML prediction dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the dashboard page
    if (document.querySelector('.dashboard-section')) {
        initMLPredictionDashboard();
        
        // Add refresh button to dashboard
        addRefreshButtonToDashboard();
    }
    
    // Add event listeners for inventory form submission
    const inventoryForm = document.getElementById('addInventoryForm');
    if (inventoryForm) {
        prepareInventoryForm();
    }

    // Load predictions when dashboard tab is shown
    document.getElementById('dashboard-link').addEventListener('click', function() {
        loadPredictions();
    });

    // Refresh predictions button
    document.getElementById('refreshPredictions').addEventListener('click', function() {
        loadPredictions(true);
    });

    // Notification dropdown event listeners
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        // Load recently added inventory when notification dropdown is shown
        notificationDropdown.addEventListener('show.bs.dropdown', function() {
            // Load recently added inventory items
            loadNewInventoryItems(false);
        });

        // Refresh button for notifications
        const refreshActivitiesBtn = document.getElementById('refreshActivitiesBtn');
        if (refreshActivitiesBtn) {
            refreshActivitiesBtn.addEventListener('click', function() {
                loadNewInventoryItems(true);
            });
        }
    }

    // Initial load if dashboard is active
    if (document.querySelector('.dashboard-section').classList.contains('active') || 
        !document.querySelector('.dashboard-section').classList.contains('d-none')) {
        loadPredictions();
    }
});

// Initialize the ML prediction dashboard
function initMLPredictionDashboard() {
    // Fetch initial ML prediction data
    fetchMLPredictionData();
    
    // Set up refresh button
    const refreshBtn = document.getElementById('refreshPredictions');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', fetchMLPredictionData);
    }
}

// Fetch ML prediction data from the server
function fetchMLPredictionData() {
    // Show loading indicators
    document.getElementById('maintenanceLoading').classList.remove('d-none');
    document.getElementById('suggestionsLoading').classList.remove('d-none');
    document.getElementById('maintenanceItems').classList.add('d-none');
    document.getElementById('inventoryItems').classList.add('d-none');
    document.getElementById('noMaintenanceItems').classList.add('d-none');
    document.getElementById('noInventoryItems').classList.add('d-none');
    
    // Fetch data from the server via our PHP handler
    fetch('fetch_ml_predictions.php?refresh=true')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Check if data is valid
            if (!data || !data.success) {
                console.error('Error in ML prediction data:', data?.error || 'Unknown error');
                showMLError();
                return;
            }
            
            // Store the data
            mlPredictionData = data;
            
            // Update the dashboard with the data
            updateMLPredictionDashboard(data);
        })
        .catch(error => {
            console.error('Error fetching ML prediction data:', error);
            showMLError();
        });
}

// Update the ML prediction dashboard with new data
function updateMLPredictionDashboard(data) {
    // Check if we have ML prediction data
    if (!data || !data.ml_predictions) {
        showMLError();
        return;
    }
    
    const mlData = data.ml_predictions;
    
    // Update maintenance predictions
    updateMaintenancePredictions(mlData.maintenance_predictions);
    
    // Update inventory suggestions
    updateInventorySuggestions(mlData.inventory_suggestions);
    
    // Update new inventory items if available
    if (mlData.new_inventory_items && mlData.new_inventory_items.length > 0) {
        newInventoryItems = mlData.new_inventory_items;
        updateNewInventoryItems(newInventoryItems);
    }
}

// Update maintenance predictions section
function updateMaintenancePredictions(predictions) {
    const container = document.querySelector('.maintenance-list');
    if (!container) return;
    
    // Hide loading indicator
    document.getElementById('maintenanceLoading').classList.add('d-none');
    
    // Check if we have predictions
    if (!predictions || predictions.length === 0) {
        document.getElementById('noMaintenanceItems').classList.remove('d-none');
        return;
    }
    
    // Show predictions container
    document.getElementById('maintenanceItems').classList.remove('d-none');
    
    // Clear container
    container.innerHTML = '';
    
    // Add predictions
    predictions.forEach(prediction => {
        const daysText = prediction.days_until === 1 ? 'day' : 'days';
        
        // Determine urgency styling based on multiple factors
        let urgencyClass = 'text-success'; // Default - low urgency
        
        if (prediction.warranty_expired && prediction.condition === 'Poor') {
            urgencyClass = 'text-danger fw-bold'; // Critical - red and bold
        } else if (prediction.warranty_expired || prediction.condition === 'Poor') {
            urgencyClass = 'text-danger'; // High urgency - red
        } else if (prediction.warranty_expiring_soon) {
            urgencyClass = 'text-warning'; // Medium urgency - orange/yellow
        } else if (prediction.days_until < 30) {
            urgencyClass = 'text-warning'; // Medium urgency - orange/yellow
        } else if (prediction.days_until < 60) {
            urgencyClass = 'text-info'; // Moderate urgency - blue
        }
        
        // Determine priority badge class
        let priorityBadgeClass = 'bg-success';
        if (prediction.priority === 'Critical') {
            priorityBadgeClass = 'bg-danger';
        } else if (prediction.priority === 'High') {
            priorityBadgeClass = 'bg-warning text-dark';
        } else if (prediction.priority === 'Medium') {
            priorityBadgeClass = 'bg-info';
        }
        
        // Build warranty status text
        let warrantyText = '';
        if (prediction.warranty_expired) {
            warrantyText = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Warranty expired</span>`;
        } else if (prediction.warranty_days_left !== undefined) {
            if (prediction.warranty_expiring_soon) {
                warrantyText = `<span class="text-warning"><i class="bi bi-clock-history me-1"></i>Warranty: ${prediction.warranty_days_left} days left</span>`;
            } else if (prediction.warranty_days_left > 0) {
                warrantyText = `<span class="text-muted small">Warranty: ${prediction.warranty_days_left} days left</span>`;
            }
        }
        
        const itemName = prediction.item_name || 'Unknown Item';
        const brandModel = prediction.brand_model || '';
        const itemCode = prediction.item_code || prediction.id || '';
        const condition = prediction.condition || 'Unknown';
        
        // Create a condition badge with appropriate color
        let conditionBadgeClass = 'bg-success';
        if (condition === 'Poor') {
            conditionBadgeClass = 'bg-danger';
        } else if (condition === 'Fair') {
            conditionBadgeClass = 'bg-warning text-dark';
        } else if (condition === 'Good') {
            conditionBadgeClass = 'bg-info';
        }
        
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2';
        
        // Add a special border/highlight for critical items
        if (prediction.priority === 'Critical') {
            item.classList.add('border-danger', 'border-start', 'ps-2', 'bg-danger', 'bg-opacity-10');
        } else if (prediction.warranty_expired || prediction.condition === 'Poor') {
            item.classList.add('border-warning', 'border-start', 'ps-2');
        }
        
        item.innerHTML = `
            <div>
                <div class="fw-bold ${prediction.priority === 'Critical' ? 'text-danger' : ''}">${itemName}</div>
                <p class="mb-0 small text-muted">${brandModel} ${itemCode ? `(${itemCode})` : ''}</p>
                <div class="d-flex align-items-center mt-1 gap-2">
                    <span class="badge ${conditionBadgeClass} rounded-pill">${condition}</span>
                    <span class="badge ${priorityBadgeClass} rounded-pill">${prediction.priority}</span>
                </div>
                <div class="mt-1">${warrantyText}</div>
            </div>
            <div class="text-end">
                <span class="badge bg-primary rounded-pill mb-1">${prediction.probability}%</span>
                <p class="mb-0 small ${urgencyClass}">
                    <i class="bi bi-tools me-1"></i>${prediction.days_until} ${daysText}
                </p>
            </div>
        `;
        
        container.appendChild(item);
    });
}

// Update inventory suggestions section
function updateInventorySuggestions(suggestions) {
    const container = document.querySelector('.inventory-list');
    if (!container) return;
    
    // Hide loading indicator
    document.getElementById('suggestionsLoading').classList.add('d-none');
    
    // Check if we have suggestions
    if (!suggestions || suggestions.length === 0) {
        document.getElementById('noInventoryItems').classList.remove('d-none');
        return;
    }
    
    // Show suggestions container
    document.getElementById('inventoryItems').classList.remove('d-none');
    
    // Clear container
    container.innerHTML = '';
    
    // Add suggestions
    suggestions.forEach(suggestion => {
        // Determine action class
        let actionClass = 'bg-primary';
        let actionIcon = 'bi-arrow-right-circle';
        
        if (suggestion.action === 'Increase') {
            actionClass = 'bg-success';
            actionIcon = 'bi-plus-circle';
        } else if (suggestion.action === 'Decrease' || suggestion.action === 'Reduce') {
            actionClass = 'bg-danger';
            actionIcon = 'bi-dash-circle';
        } else if (suggestion.action === 'Maintain') {
            actionClass = 'bg-info';
            actionIcon = 'bi-check-circle';
        } else if (suggestion.action === 'Replace' || suggestion.action === 'Upgrade') {
            actionClass = 'bg-warning text-dark';
            actionIcon = 'bi-arrow-repeat';
        } else if (suggestion.action === 'Optimize') {
            actionClass = 'bg-secondary';
            actionIcon = 'bi-gear';
        } else if (suggestion.action === 'Renew') {
            actionClass = 'bg-info';
            actionIcon = 'bi-arrow-clockwise';
        } else if (suggestion.action === 'Plan') {
            actionClass = 'bg-primary';
            actionIcon = 'bi-calendar-check';
        } else if (suggestion.action === 'Evaluate') {
            actionClass = 'bg-secondary';
            actionIcon = 'bi-clipboard-data';
        }
        
        // Create count text if available
        let countText = '';
        if (suggestion.current_count !== undefined) {
            countText = `<span class="badge bg-secondary rounded-pill ms-2">${suggestion.current_count}</span>`;
        }
        
        // Create reason text if available
        let reasonText = '';
        if (suggestion.reason) {
            reasonText = `<span class="d-block small text-muted mt-1">${suggestion.reason}</span>`;
        }
        
        // Create specific recommendation text if available
        let recommendationText = '';
        if (suggestion.specific_recommendation) {
            recommendationText = `
                <div class="mt-2 p-2 bg-light rounded small">
                    <i class="bi bi-lightbulb text-warning me-1"></i>
                    ${suggestion.specific_recommendation}
                </div>
            `;
        }
        
        // Special highlight for warranty-related suggestions
        let itemClass = 'border-0 px-0 py-3';
        if (suggestion.category.includes('Warranty')) {
            itemClass += ' border-warning border-start ps-2';
        } else if (suggestion.action === 'Replace') {
            itemClass += ' border-danger border-start ps-2';
        }
        
        const item = document.createElement('div');
        item.className = `list-group-item d-flex justify-content-between align-items-start ${itemClass}`;
        item.innerHTML = `
            <div class="flex-grow-1">
                <div class="d-flex align-items-center">
                    <span class="fw-bold">${suggestion.category}</span>
                    ${countText}
                </div>
                ${reasonText}
                ${recommendationText}
            </div>
            <div class="text-end ms-3">
                <span class="badge ${actionClass} rounded-pill d-flex align-items-center gap-1">
                    <i class="bi ${actionIcon}"></i> ${suggestion.action}
                </span>
                <p class="mb-0 small text-muted mt-1">${suggestion.confidence}% confidence</p>
            </div>
        `;
        
        container.appendChild(item);
    });
}

// Update new inventory items section
function updateNewInventoryItems(items) {
    // Update both the dashboard section (if exists) and notification dropdown
    updateDashboardInventoryItems(items);
    updateNotificationInventoryItems(items);
}

// Update the dashboard section with recently added inventory items
function updateDashboardInventoryItems(items) {
    const recentInventoryList = document.getElementById('recentInventoryList');
    if (!recentInventoryList) return;
    
    // Hide loading indicator
    const loadingIndicator = document.getElementById('recentInventoryLoading');
    if (loadingIndicator) {
        loadingIndicator.classList.add('d-none');
    }
    
    // Check for no items container
    const noItemsContainer = document.getElementById('noRecentInventory');
    
    // Check if we have items
    if (!items || items.length === 0) {
        if (noItemsContainer) {
            noItemsContainer.classList.remove('d-none');
        }
        recentInventoryList.innerHTML = '';
        return;
    }
    
    // Hide the no items message if it exists
    if (noItemsContainer) {
        noItemsContainer.classList.add('d-none');
    }
    
    // Clear container
    recentInventoryList.innerHTML = '';
    
    // Add items
    items.forEach((item, index) => {
        const addedDate = item.added_date ? new Date(item.added_date) : null;
        const dateText = addedDate ? addedDate.toLocaleDateString() : 'Recently added';
        
        // Create appropriate icon based on item type
        let itemIcon = 'bi-laptop';
        let iconColor = 'primary';
        
        if (item.item_name) {
            const itemNameLower = item.item_name.toLowerCase();
            if (itemNameLower.includes('printer')) {
                itemIcon = 'bi-printer';
                iconColor = 'info';
            } else if (itemNameLower.includes('server')) {
                itemIcon = 'bi-server';
                iconColor = 'danger';
            } else if (itemNameLower.includes('monitor')) {
                itemIcon = 'bi-display';
                iconColor = 'success';
            } else if (itemNameLower.includes('mouse') || itemNameLower.includes('keyboard')) {
                itemIcon = 'bi-keyboard';
                iconColor = 'warning';
            } else if (itemNameLower.includes('phone') || itemNameLower.includes('mobile')) {
                itemIcon = 'bi-phone';
                iconColor = 'dark';
            }
        }
        
        const itemElement = document.createElement('div');
        itemElement.className = 'inventory-item-container p-3 border-bottom';
        itemElement.innerHTML = `
            <div class="row g-0">
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <div class="item-icon bg-${iconColor} bg-opacity-10 p-3 rounded-circle">
                        <i class="bi ${itemIcon} text-${iconColor} fs-4"></i>
                    </div>
                </div>
                <div class="col-md-8 ps-md-3">
                    <h6 class="mb-1 fw-semibold">${item.item_name || 'Unknown Item'}</h6>
                    <div class="d-flex flex-wrap gap-2 mb-1">
                        <span class="text-muted small">
                            <i class="bi bi-tag text-muted me-1"></i>${item.brand_model || 'No Brand/Model'}
                        </span>
                        <span class="text-muted small">
                            <i class="bi bi-upc-scan text-muted me-1"></i>${item.serial_number || 'No S/N'}
                        </span>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-calendar-plus text-muted me-1"></i>${dateText}
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <span class="badge bg-${item.condition === 'New' ? 'success' : item.condition === 'Good' ? 'info' : item.condition === 'Fair' ? 'warning' : 'danger'} mb-2">
                        ${item.condition || 'Unknown'}
                    </span>
                    <div>
                        <button class="btn btn-sm btn-outline-primary view-item-btn" data-item-id="${item.id || ''}">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        recentInventoryList.appendChild(itemElement);
    });
}

// Update the notification dropdown with recently added inventory items
function updateNotificationInventoryItems(items) {
    const notificationList = document.getElementById('recentInventoryListNotif');
    if (!notificationList) return;
    
    const noItemsNotif = document.getElementById('noRecentInventoryNotif');
    
    // Check if we have items
    if (!items || items.length === 0) {
        if (noItemsNotif) {
            noItemsNotif.classList.remove('d-none');
        }
        notificationList.innerHTML = '';
        return;
    }
    
    // Hide the no items message
    if (noItemsNotif) {
        noItemsNotif.classList.add('d-none');
    }
    
    // Clear container
    notificationList.innerHTML = '';
    
    // Add items (limit to 5 in notifications)
    const displayItems = items.slice(0, 5);
    
    displayItems.forEach((item) => {
        const addedDate = item.added_date ? new Date(item.added_date) : null;
        const dateText = addedDate ? addedDate.toLocaleDateString() : 'Recently added';
        
        const itemElement = document.createElement('div');
        itemElement.className = 'notification-item d-flex justify-content-between align-items-start p-2 border-bottom';
        itemElement.innerHTML = `
            <div>
                <span class="fw-medium">${item.item_name || 'Unknown Item'}</span>
                <p class="mb-0 small text-muted">${item.serial_number || 'No S/N'}</p>
                <small class="text-muted">${dateText}</small>
            </div>
            <span class="badge bg-success rounded-pill">${item.condition || 'New'}</span>
        `;
        
        // Add click event to view item details
        itemElement.addEventListener('click', function() {
            // Close dropdown
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
            
            // Navigate to inventory section and highlight item
            document.getElementById('inventory-link').click();
            
            // Highlight the item in the inventory list (needs implementation)
            highlightInventoryItem(item.id || item.serial_number);
        });
        
        notificationList.appendChild(itemElement);
    });
    
    // Add notification badge update
    updateNotificationBadge(items.length);
}

// Highlight an inventory item in the inventory list
function highlightInventoryItem(identifier) {
    // This function would need to be implemented to find and highlight the item
    // in the inventory table based on ID or serial number
    console.log(`Highlighting inventory item: ${identifier}`);
    
    // Simple implementation - search the inventory table and flash highlight the row
    setTimeout(() => {
        const inventoryTable = document.getElementById('inventoryTableBody');
        if (!inventoryTable) return;
        
        const rows = inventoryTable.querySelectorAll('tr');
        for (const row of rows) {
            const cells = row.querySelectorAll('td');
            for (const cell of cells) {
                if (cell.textContent.includes(identifier)) {
                    // Found the item, highlight the row
                    row.classList.add('highlight-row');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Remove highlight after a few seconds
                    setTimeout(() => {
                        row.classList.remove('highlight-row');
                    }, 3000);
                    return;
                }
            }
        }
    }, 500);
}

// Update the notification badge with the count of new items
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (!badge) return;
    
    // Get the current count
    const currentCount = parseInt(badge.textContent.trim()) || 0;
    
    // Update the badge
    badge.textContent = currentCount + count;
    
    // Show/hide the badge based on count
    if (currentCount + count > 0) {
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

// Show error in ML prediction dashboard
function showMLError() {
    // Hide loading indicators
    document.getElementById('maintenanceLoading').classList.add('d-none');
    document.getElementById('suggestionsLoading').classList.add('d-none');
    
    // Show error message
    document.getElementById('maintenanceItems').classList.add('d-none');
    document.getElementById('inventoryItems').classList.add('d-none');
    document.getElementById('noMaintenanceItems').classList.remove('d-none');
    document.getElementById('noInventoryItems').classList.remove('d-none');
    
    // Update error message
    document.getElementById('noMaintenanceItems').innerHTML = `
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
            <p>Could not load maintenance predictions</p>
        </div>
    `;
    
    document.getElementById('noInventoryItems').innerHTML = `
        <div class="text-center py-4">
            <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
            <p>Could not load inventory suggestions</p>
        </div>
    `;
}

// Prepare inventory form for ML tracking
function prepareInventoryForm() {
    // Get the form element
    const form = document.getElementById('addInventoryForm');
    if (!form) return;
    
    // Add a hidden input for tracking data
    if (!document.getElementById('inventoryTrackingData')) {
        const trackingInput = document.createElement('input');
        trackingInput.type = 'hidden';
        trackingInput.id = 'inventoryTrackingData';
        trackingInput.name = 'tracking_data';
        form.appendChild(trackingInput);
    }
    
    // Add event listener to save button
    const saveButton = document.getElementById('saveItemBtn');
    if (saveButton) {
        // Override the click event
        saveButton.addEventListener('click', function(event) {
            // Add the tracking data to the form
            const trackingInput = document.getElementById('inventoryTrackingData');
            if (trackingInput) {
                // Gather form data
                const formData = new FormData(form);
                const trackingData = {};
                
                // Convert FormData to object
                for (const [key, value] of formData.entries()) {
                    trackingData[key] = value;
                }
                
                // Set the tracking data
                trackingInput.value = JSON.stringify(trackingData);
            }
            
            // Continue with the original saveInventoryItem function
            saveInventoryItem();
        }, { capture: true });
    }
}

// Track inventory item in ML system
function trackInventoryItemML(itemData) {
    // Send the item data to the tracking endpoint
    fetch('track_inventory_for_ml.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(itemData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Inventory tracking result:', data);
        
        // If successful, refresh the ML prediction dashboard
        if (data.success && document.querySelector('.dashboard-section')) {
            // Refresh the predictions after a slight delay to ensure data is processed
            setTimeout(function() {
                fetchMLPredictionData();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error tracking inventory item:', error);
    });
}

// Override the original saveInventoryItem function to include ML tracking
const originalSaveInventoryItem = window.saveInventoryItem;
window.saveInventoryItem = function() {
    // Call the original function first (if it exists)
    if (typeof originalSaveInventoryItem === 'function') {
        originalSaveInventoryItem();
    } else {
        // If the original function doesn't exist, implement basic functionality
        const form = document.getElementById('addInventoryForm');
        if (!form) return;
        
        // Gather form data
        const formData = new FormData(form);
        const itemData = {};
        
        // Convert FormData to object
        for (const [key, value] of formData.entries()) {
            itemData[key] = value;
        }
        
        // TODO: Save the inventory item to the database
        // This would typically be done via AJAX
        
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addInventoryModal'));
        if (modal) {
            modal.hide();
        }
    }
    
    // Track the ML data
    trackInventoryDataForML();
    
    // Set a timeout to refresh the inventory notification after the item is saved
    setTimeout(() => {
        // Refresh inventory items in notification
        loadNewInventoryItems(true);
        
        // Increment the notification badge
        updateNotificationBadge(1);
    }, 1000);
};

/**
 * Load both maintenance predictions and inventory suggestions
 * @param {boolean} forceRefresh - Whether to force a refresh from the server (ignoring cache)
 */
function loadPredictions(forceRefresh = false) {
    // Show loading indicators
    document.getElementById('maintenanceLoading').classList.remove('d-none');
    document.getElementById('suggestionsLoading').classList.remove('d-none');
    document.getElementById('maintenanceItems').classList.add('d-none');
    document.getElementById('inventoryItems').classList.add('d-none');
    document.getElementById('noMaintenanceItems').classList.add('d-none');
    document.getElementById('noInventoryItems').classList.add('d-none');
    
    // If force refresh, regenerate predictions first
    if (forceRefresh) {
        // Call the PHP script to generate new predictions
        fetch('generate_predictions.php?force=true')
            .then(response => response.json())
            .then(data => {
                console.log('Generated new predictions:', data);
                // After generating predictions, fetch them
                fetchMLPredictionData();
            })
            .catch(error => {
                console.error('Error generating predictions:', error);
                // Still try to fetch existing predictions
                fetchMLPredictionData();
            });
    } else {
        // Just fetch current predictions
        fetchMLPredictionData();
    }
}

// Load maintenance predictions
function loadMaintenancePredictions(forceRefresh = false) {
    // Show loading indicator
    document.getElementById('maintenanceLoading').classList.remove('d-none');
    document.getElementById('maintenanceItems').classList.add('d-none');
    document.getElementById('noMaintenanceItems').classList.add('d-none');
    
    // Fetch maintenance predictions
    fetch('DataAnalytics/get_ml_predictions.py?type=maintenance' + (forceRefresh ? '&refresh=true' : ''))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            try {
                // Parse the response
                const jsonData = JSON.parse(data);
                
                if (jsonData.success && jsonData.data) {
                    // Update the UI with the predictions
                    updateMaintenancePredictions(jsonData.data);
                } else {
                    throw new Error('Invalid prediction data');
                }
            } catch (error) {
                console.error('Error parsing maintenance predictions:', error);
                showMaintenanceError();
            }
        })
        .catch(error => {
            console.error('Error fetching maintenance predictions:', error);
            showMaintenanceError();
        });
}

// Load inventory suggestions
function loadInventorySuggestions(forceRefresh = false) {
    // Show loading indicator
    document.getElementById('suggestionsLoading').classList.remove('d-none');
    document.getElementById('inventoryItems').classList.add('d-none');
    document.getElementById('noInventoryItems').classList.add('d-none');
    
    // Fetch inventory suggestions
    fetch('DataAnalytics/get_ml_predictions.py?type=inventory' + (forceRefresh ? '&refresh=true' : ''))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            try {
                // Parse the response
                const jsonData = JSON.parse(data);
                
                if (jsonData.success && jsonData.data) {
                    // Update the UI with the suggestions
                    updateInventorySuggestions(jsonData.data);
                } else {
                    throw new Error('Invalid suggestion data');
                }
            } catch (error) {
                console.error('Error parsing inventory suggestions:', error);
                showInventorySuggestionsError();
            }
        })
        .catch(error => {
            console.error('Error fetching inventory suggestions:', error);
            showInventorySuggestionsError();
        });
}

// Load new inventory items
function loadNewInventoryItems(forceRefresh = false) {
    // Check if we already have data and if refresh is not forced
    if (newInventoryItems.length > 0 && !forceRefresh) {
        // Use existing data
        updateNewInventoryItems(newInventoryItems);
        return;
    }
    
    // Show loading indicator in dashboard section if it exists
    const loadingIndicator = document.getElementById('recentInventoryLoading');
    if (loadingIndicator) {
        loadingIndicator.classList.remove('d-none');
    }
    
    // Fetch new inventory items from server
    fetch('fetch_new_inventory.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Check if data is valid
            if (!data || !data.success) {
                console.error('Error in new inventory data:', data?.error || 'Unknown error');
                showNewInventoryError();
                return;
            }
            
            // Store data
            newInventoryItems = data.items || [];
            
            // Update UI with data
            updateNewInventoryItems(newInventoryItems);
            
            // Update notification badge count (if this is an initial load)
            if (!forceRefresh) {
                updateNotificationBadge(newInventoryItems.length);
            }
        })
        .catch(error => {
            console.error('Error fetching new inventory items:', error);
            showNewInventoryError();
        });
}

// Show error for new inventory items
function showNewInventoryError() {
    const container = document.querySelector('.new-inventory-list');
    if (!container) return;
    
    // Hide loading indicator
    const loadingIndicator = document.getElementById('recentInventoryLoading');
    if (loadingIndicator) {
        loadingIndicator.classList.add('d-none');
    }
    
    // Display error message
    container.innerHTML = `
        <div class="text-center py-3">
            <i class="bi bi-exclamation-triangle text-warning"></i>
            <p class="mb-1 small">Could not load recently added items</p>
            <button id="retryInventoryBtn" class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        </div>
    `;
    
    // Add retry button functionality
    const retryBtn = document.getElementById('retryInventoryBtn');
    if (retryBtn) {
        retryBtn.addEventListener('click', function() {
            loadNewInventoryItems(true);
        });
    }
}

/**
 * Track inventory data for ML when adding a new item
 * This function prepares the data for tracking before form submission
 */
function trackInventoryDataForML() {
    // Get form data
    const itemName = document.getElementById('itemName').value;
    const brandModel = document.getElementById('Brand/model').value;
    const serialNumber = document.getElementById('serialNumber').value;
    const purchaseDate = document.getElementById('purchaseDate').value;
    const warrantyDate = document.getElementById('warrantyDate').value;
    const assignedTo = document.getElementById('assignedTo').value;
    const location = document.getElementById('location').value;
    const condition = document.getElementById('condition').value;
    const notes = document.getElementById('notes').value;
    
    // Prepare tracking data
    const trackingData = {
        item_name: itemName,
        brand_model: brandModel,
        serial_number: serialNumber,
        purchase_date: purchaseDate,
        warranty_expiration: warrantyDate,
        assigned_to: assignedTo,
        location: location,
        condition: condition,
        notes: notes,
        tracked_at: new Date().toISOString()
    };
    
    // Set the tracking data in the hidden form field
    document.getElementById('inventoryTrackingData').value = JSON.stringify(trackingData);
}

// Add event listener to the inventory form submit button to track data
document.addEventListener('DOMContentLoaded', function() {
    const saveItemBtn = document.getElementById('saveItemBtn');
    if (saveItemBtn) {
        saveItemBtn.addEventListener('click', function() {
            trackInventoryDataForML();
            // Form submission is handled elsewhere in the code
        });
    }
});

// Show error for maintenance predictions
function showMaintenanceError() {
    document.getElementById('maintenanceLoading').classList.add('d-none');
    document.getElementById('maintenanceItems').classList.add('d-none');
    document.getElementById('noMaintenanceItems').classList.remove('d-none');
    
    // Update error message
    const errorContainer = document.getElementById('noMaintenanceItems');
    errorContainer.innerHTML = `
        <div class="text-center py-3">
            <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
            <p>Could not load maintenance predictions</p>
            <button id="retryMaintenanceBtn" class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        </div>
    `;
    
    // Add retry functionality
    document.getElementById('retryMaintenanceBtn').addEventListener('click', function() {
        loadMaintenancePredictions(true);
    });
}

// Show error for inventory suggestions
function showInventorySuggestionsError() {
    document.getElementById('suggestionsLoading').classList.add('d-none');
    document.getElementById('inventoryItems').classList.add('d-none');
    document.getElementById('noInventoryItems').classList.remove('d-none');
    
    // Update error message
    const errorContainer = document.getElementById('noInventoryItems');
    errorContainer.innerHTML = `
        <div class="text-center py-3">
            <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
            <p>Could not load inventory suggestions</p>
            <button id="retrySuggestionsBtn" class="btn btn-sm btn-outline-primary mt-2">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        </div>
    `;
    
    // Add retry functionality
    document.getElementById('retrySuggestionsBtn').addEventListener('click', function() {
        loadInventorySuggestions(true);
    });
}

// Add refresh button to dashboard
function addRefreshButtonToDashboard() {
    // Check if we're on the dashboard page
    const dashboardSection = document.querySelector('.dashboard-section');
    if (!dashboardSection) return;
    
    // Find the ML prediction dashboard
    const mlDashboard = document.querySelector('.ml-prediction-dashboard');
    if (!mlDashboard) return;
    
    // Get the header
    const dashboardHeader = mlDashboard.querySelector('.card-header');
    if (!dashboardHeader) return;
    
    // Check if button already exists
    if (dashboardHeader.querySelector('#regeneratePredictionsBtn')) return;
    
    // Create the refresh button
    const refreshButton = document.createElement('button');
    refreshButton.id = 'regeneratePredictionsBtn';
    refreshButton.className = 'btn btn-sm btn-primary ms-2';
    refreshButton.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Generate New Predictions';
    refreshButton.title = 'Generate new predictions from scratch using Python ML';
    
    // Add event listener to regenerate predictions
    refreshButton.addEventListener('click', function() {
        // Show loading state
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        // Call the PHP script to generate new predictions
        fetch('generate_predictions.php?force=true')
            .then(response => response.json())
            .then(data => {
                console.log('Generated new predictions:', data);
                
                // Show success message
                const oldHtml = this.innerHTML;
                this.className = 'btn btn-sm btn-success ms-2';
                this.innerHTML = '<i class="bi bi-check-circle"></i> Success!';
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    this.disabled = false;
                    this.className = 'btn btn-sm btn-primary ms-2';
                    this.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Generate New Predictions';
                    
                    // Refresh the dashboard data
                    fetchMLPredictionData();
                }, 2000);
            })
            .catch(error => {
                console.error('Error generating predictions:', error);
                
                // Show error state
                this.disabled = false;
                this.className = 'btn btn-sm btn-danger ms-2';
                this.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error';
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    this.className = 'btn btn-sm btn-primary ms-2';
                    this.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Generate New Predictions';
                }, 2000);
            });
    });
    
    // Find the existing refresh button
    const existingButton = dashboardHeader.querySelector('#refreshPredictions');
    
    // Insert the new button after the existing one
    if (existingButton) {
        existingButton.insertAdjacentElement('afterend', refreshButton);
    } else {
        // If no existing button, append to header
        dashboardHeader.appendChild(refreshButton);
    }
} 