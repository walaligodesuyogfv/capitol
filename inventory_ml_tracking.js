/**
 * Inventory ML Tracking
 * This script handles tracking inventory items for ML predictions
 */

// Track inventory for ML predictions
function trackInventoryForML(itemData) {
    console.log('Tracking inventory for ML predictions:', itemData);
    
    // Send data to tracking endpoint
    fetch('track_inventory_for_ml.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(itemData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('ML tracking result:', data);
        if (data.success) {
            // Refresh ML predictions in the dashboard if visible
            if (document.querySelector('.dashboard-section') && 
                !document.querySelector('.dashboard-section').classList.contains('d-none')) {
                // If we're on the dashboard, refresh predictions
                if (typeof fetchMLPredictionData === 'function') {
                    setTimeout(() => fetchMLPredictionData(), 1000);
                }
            }
        }
    })
    .catch(error => {
        console.error('Error tracking inventory for ML:', error);
    });
}

// Override the original saveInventoryItem function to include ML tracking
document.addEventListener('DOMContentLoaded', function() {
    // Original save function - get a reference if it exists
    const originalSaveInventoryItem = window.saveInventoryItem;
    
    // Create our enhanced save function
    window.saveInventoryItem = function() {
        console.log('Enhanced saveInventoryItem called');
        
        // Call the original function if it exists
        if (typeof originalSaveInventoryItem === 'function') {
            originalSaveInventoryItem();
        }
        
        // Get form data for ML tracking
        const form = document.getElementById('addInventoryForm');
        if (form) {
            // Get form data
            const formData = new FormData(form);
            const itemData = {};
            
            // Convert FormData to object
            for (const [key, value] of formData.entries()) {
                itemData[key] = value;
            }
            
            // Track the item for ML predictions
            trackInventoryForML(itemData);
        }
    };
    
    // Hook into the submit button
    const saveItemBtn = document.getElementById('saveItemBtn');
    if (saveItemBtn) {
        saveItemBtn.addEventListener('click', function() {
            // This will be handled by the overridden saveInventoryItem function
            console.log('Save button clicked');
        });
    }
}); 