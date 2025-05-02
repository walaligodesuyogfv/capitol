// Initialize system tab functionality
function initializeSystemTab() {
    // Save system settings button
    document.getElementById('saveSystemSettingsBtn').addEventListener('click', saveSystemSettings);

    // Create backup button
    document.getElementById('createBackupBtn').addEventListener('click', createDatabaseBackup);

    // Check for updates button
    document.getElementById('checkUpdatesBtn').addEventListener('click', checkForUpdates);

    // Clear cache button
    document.getElementById('clearCacheBtn').addEventListener('click', clearSystemCache);

    // Reset system confirmation checkbox
    const resetConfirmCheck = document.getElementById('resetConfirmCheck');
    const confirmResetBtn = document.getElementById('confirmResetBtn');
    
    if (resetConfirmCheck && confirmResetBtn) {
        resetConfirmCheck.addEventListener('change', function() {
            confirmResetBtn.disabled = !this.checked;
        });
        
        confirmResetBtn.addEventListener('click', function() {
            if (resetConfirmCheck.checked) {
                resetSystem();
            }
        });
    }
}

// Function to handle system reset
function resetSystem() {
    // Close the modal
    const resetModal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
    if (resetModal) {
        resetModal.hide();
    }
    
    // Show loading state
    Swal.fire({
        title: 'Resetting System',
        html: 'Please wait while we reset the system to defaults...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulate API call
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'System Reset Complete',
            text: 'The system has been reset to factory defaults. You will be logged out.',
            confirmButtonText: 'OK'
        }).then(() => {
            // Redirect to login page
            window.location.href = 'login.php';
        });
    }, 3000);
}

// Settings Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize settings functionality when the document is fully loaded
    initializeSettings();
    
    // Event listeners for settings tab switching
    document.querySelectorAll('#settingsTabs button').forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-bs-target').replace('#', '');
            // You can add specific actions when switching tabs if needed
            console.log(`Switched to ${targetTab} tab`);
        });
    });
    
    // Save settings button click handler
    document.getElementById('saveSettingsBtn').addEventListener('click', saveAllSettings);
    
    // Role selector change event
    document.getElementById('roleSelector').addEventListener('change', loadRolePermissions);
    
    // Add new role button click handler
    document.getElementById('addNewRoleBtn').addEventListener('click', showAddRoleModal);
    
    // Delete role button click handler
    document.getElementById('deleteRoleBtn').addEventListener('click', confirmDeleteRole);
    
    // Threshold related event listeners
    document.getElementById('defaultThreshold').addEventListener('change', updateDefaultThreshold);
    document.getElementById('enableAutoOrder').addEventListener('change', toggleAutoOrder);
    
    // System maintenance buttons
    document.getElementById('backupDatabaseBtn').addEventListener('click', backupDatabase);
    document.getElementById('clearCacheBtn').addEventListener('click', clearSystemCache);
    document.getElementById('resetSystemBtn').addEventListener('click', confirmSystemReset);
    
    // Setup tables button - Add this listener
    document.getElementById('setupSettingsTablesBtn').addEventListener('click', setupSettingsTables);
});

/**
 * Initialize settings page and load saved settings
 */
function initializeSettings() {
    // Load role permissions for default selected role
    loadRolePermissions();
    
    // Load threshold settings
    loadThresholdSettings();
    
    // Load notification settings
    loadNotificationSettings();
    
    // Load system settings
    loadSystemSettings();
    
    // Initialize threshold items table
    initializeThresholdItems();
}

/**
 * Setup the required database tables for settings
 */
function setupSettingsTables() {
    // Show loading state
    Swal.fire({
        title: 'Setting up tables...',
        html: 'Please wait while we create the necessary tables in the database.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Call the API to create the tables
    fetch('api_settings.php?action=setup_tables', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Tables Created',
                text: 'The settings tables have been successfully created in the database.',
                confirmButtonText: 'OK'
            }).then(() => {
                // Reload settings
                initializeSettings();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'There was an error creating the settings tables.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'There was an error communicating with the server: ' + error.message,
            confirmButtonText: 'OK'
        });
    });
}

/**
 * Load permissions for the selected role
 */
function loadRolePermissions() {
    const selectedRole = document.getElementById('roleSelector').value;
    console.log(`Loading permissions for role: ${selectedRole}`);
    
    // Show role description based on selected role
    const descriptions = {
        'admin': 'Full system access with all permissions enabled.',
        'manager': 'Access to most functions except user management and settings.',
        'staff': 'Limited access focused on inventory and daily operations.',
        'viewer': 'Read-only access to view reports and inventory items.'
    };
    
    document.getElementById('roleDescription').value = descriptions[selectedRole] || '';
    
    // Set checkboxes based on role permissions
    const permissionCheckboxes = document.querySelectorAll('.permission-check');
    
    // Reset all checkboxes first
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Set permissions based on role
    switch(selectedRole) {
        case 'admin':
            // Admin has all permissions
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            break;
            
        case 'manager':
            // Managers have most permissions except some admin functions
            permissionCheckboxes.forEach(checkbox => {
                const module = checkbox.getAttribute('data-module');
                const action = checkbox.getAttribute('data-action');
                
                if (module === 'users' && (action === 'create' || action === 'delete')) {
                    checkbox.checked = false;
                } else if (module === 'settings' && action !== 'view') {
                    checkbox.checked = false;
                } else {
                    checkbox.checked = true;
                }
            });
            break;
            
        case 'staff':
            // Staff have limited permissions
            permissionCheckboxes.forEach(checkbox => {
                const module = checkbox.getAttribute('data-module');
                const action = checkbox.getAttribute('data-action');
                
                if (module === 'dashboard' && action === 'view') {
                    checkbox.checked = true;
                } else if (module === 'inventory' || module === 'po' || module === 'par') {
                    if (action === 'delete') {
                        checkbox.checked = false;
                    } else {
                        checkbox.checked = true;
                    }
                } else if (module === 'reports' && action === 'view') {
                    checkbox.checked = true;
                } else {
                    checkbox.checked = false;
                }
            });
            break;
            
        case 'viewer':
            // Viewers have read-only access
            permissionCheckboxes.forEach(checkbox => {
                const action = checkbox.getAttribute('data-action');
                if (action === 'view') {
                    checkbox.checked = true;
                } else {
                    checkbox.checked = false;
                }
            });
            break;
    }
}

/**
 * Load threshold settings from the server
 */
function loadThresholdSettings() {
    // This would typically fetch settings from the server
    // For now, we'll use default values
    document.getElementById('defaultThreshold').value = 5;
    document.getElementById('enableAutoOrder').checked = true;
    document.getElementById('thresholdPercentage').value = 20;
}

/**
 * Initialize the threshold items table with sample data or from the server
 */
function initializeThresholdItems() {
    // In a real implementation, this would fetch data from the server
    // The sample data is already in the HTML table
    
    // Add event listeners to threshold inputs
    const thresholdInputs = document.querySelectorAll('.threshold-value');
    thresholdInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateItemThreshold(this);
        });
    });
}

/**
 * Update threshold value for a specific item
 * @param {HTMLElement} input - The threshold input element
 */
function updateItemThreshold(input) {
    const row = input.closest('tr');
    const itemName = row.cells[0].textContent;
    const newThreshold = parseInt(input.value);
    const currentStock = parseInt(row.cells[1].textContent);
    
    // Update status badge based on new threshold and current stock
    const statusCell = row.cells[3];
    const statusBadge = statusCell.querySelector('.badge');
    
    if (currentStock <= newThreshold * (document.getElementById('thresholdPercentage').value / 100)) {
        // Critical status
        statusBadge.className = 'badge bg-danger';
        statusBadge.textContent = 'Critical';
    } else if (currentStock <= newThreshold) {
        // Low status
        statusBadge.className = 'badge bg-warning';
        statusBadge.textContent = 'Low';
    } else {
        // Normal status
        statusBadge.className = 'badge bg-success';
        statusBadge.textContent = 'Normal';
    }
    
    console.log(`Updated threshold for ${itemName} to ${newThreshold}`);
    
    // In a real implementation, this would send the update to the server
    showToast('Threshold updated', `Updated threshold for ${itemName} to ${newThreshold} units.`);
}

/**
 * Update the default threshold value for all items
 */
function updateDefaultThreshold() {
    const defaultThreshold = parseInt(document.getElementById('defaultThreshold').value);
    console.log(`Updated default threshold to ${defaultThreshold}`);
    
    // In a real implementation, this would update the server setting
    // and potentially update all items without custom thresholds
    showToast('Default threshold updated', `Default threshold set to ${defaultThreshold} units.`);
}

/**
 * Toggle the auto-order functionality
 */
function toggleAutoOrder() {
    const enabled = document.getElementById('enableAutoOrder').checked;
    console.log(`Auto-order ${enabled ? 'enabled' : 'disabled'}`);
    
    // In a real implementation, this would update the server setting
    showToast('Auto-order setting updated', `Auto-order has been ${enabled ? 'enabled' : 'disabled'}.`);
}

/**
 * Load notification settings from the server
 */
function loadNotificationSettings() {
    // This would typically fetch settings from the server
    // For now, default values are already set in the HTML
}

/**
 * Load system settings from the server
 */
function loadSystemSettings() {
    // This would typically fetch settings from the server
    // For now, default values are already set in the HTML
}

/**
 * Save all settings from all tabs
 */
function saveAllSettings() {
    // Get all settings from the UI
    
    // Role permissions
    const selectedRole = document.getElementById('roleSelector').value;
    const permissions = [];
    document.querySelectorAll('.permission-check').forEach(checkbox => {
        if (checkbox.checked) {
            permissions.push({
                module: checkbox.getAttribute('data-module'),
                action: checkbox.getAttribute('data-action'),
                role: selectedRole
            });
        }
    });
    
    // Threshold settings
    const thresholdSettings = {
        defaultThreshold: parseInt(document.getElementById('defaultThreshold').value),
        enableAutoOrder: document.getElementById('enableAutoOrder').checked,
        thresholdPercentage: parseInt(document.getElementById('thresholdPercentage').value)
    };
    
    // Item-specific thresholds
    const itemThresholds = [];
    document.querySelectorAll('#thresholdsTableBody tr').forEach(row => {
        itemThresholds.push({
            itemName: row.cells[0].textContent,
            currentStock: parseInt(row.cells[1].textContent),
            threshold: parseInt(row.querySelector('.threshold-value').value),
            autoOrder: row.querySelector('.form-check-input').checked
        });
    });
    
    // Notification settings
    const notificationSettings = {
        alerts: {
            lowStock: document.getElementById('lowStockAlert').checked,
            expiringWarranty: document.getElementById('expiringWarrantyAlert').checked,
            maintenanceReminder: document.getElementById('maintenanceReminderAlert').checked,
            newItem: document.getElementById('newItemAlert').checked,
            poStatus: document.getElementById('poStatusAlert').checked
        },
        frequency: document.getElementById('alertFrequency').value,
        delivery: {
            inApp: document.getElementById('inAppNotification').checked,
            email: document.getElementById('emailNotification').checked
        },
        emailRecipients: document.getElementById('emailRecipients').value.split('\n').filter(email => email.trim() !== ''),
        retentionPeriod: parseInt(document.getElementById('retentionPeriod').value)
    };
    
    // System settings
    const systemSettings = {
        systemName: document.getElementById('systemName').value,
        organizationName: document.getElementById('organizationName').value,
        dateFormat: document.getElementById('dateFormat').value,
        timeFormat: document.getElementById('timeFormat').value,
        currency: document.getElementById('currency').value,
        logRetention: parseInt(document.getElementById('logRetention').value),
        backupFrequency: document.getElementById('backupFrequency').value
    };
    
    // Combine all settings
    const allSettings = {
        role: selectedRole,
        permissions: permissions,
        thresholds: thresholdSettings,
        itemThresholds: itemThresholds,
        notifications: notificationSettings,
        system: systemSettings
    };
    
    // In a real implementation, this would send the settings to the server
    console.log('Saving settings:', allSettings);
    
    // Simulate successful save
    showToast('Settings saved', 'All settings have been successfully saved.');
}

/**
 * Show modal to add a new role
 */
function showAddRoleModal() {
    // In a real implementation, this would show a modal to create a new role
    Swal.fire({
        title: 'Add New Role',
        html: `
            <div class="mb-3">
                <label for="newRoleName" class="form-label">Role Name</label>
                <input type="text" class="form-control" id="newRoleName" placeholder="Enter role name">
            </div>
            <div class="mb-3">
                <label for="newRoleDescription" class="form-label">Role Description</label>
                <textarea class="form-control" id="newRoleDescription" rows="3" placeholder="Enter role description"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Role',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const roleName = document.getElementById('newRoleName').value;
            const roleDescription = document.getElementById('newRoleDescription').value;
            
            if (!roleName.trim()) {
                Swal.showValidationMessage('Role name is required');
                return false;
            }
            
            return { name: roleName, description: roleDescription };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // In a real implementation, this would send the new role to the server
            console.log('New role:', result.value);
            showToast('Role added', `New role "${result.value.name}" has been added.`);
        }
    });
}

/**
 * Confirm deletion of the selected role
 */
function confirmDeleteRole() {
    const selectedRole = document.getElementById('roleSelector').value;
    
    if (selectedRole === 'admin') {
        showToast('Cannot delete role', 'The Administrator role cannot be deleted.', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Delete Role',
        text: `Are you sure you want to delete the "${selectedRole}" role? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            // In a real implementation, this would send the delete request to the server
            console.log(`Deleting role: ${selectedRole}`);
            showToast('Role deleted', `The "${selectedRole}" role has been deleted.`);
        }
    });
}

/**
 * Backup the database
 */
function backupDatabase() {
    // Show loading indicator
    Swal.fire({
        title: 'Creating Backup',
        html: 'Please wait while we backup the database...',
        timerProgressBar: true,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Simulate backup process
    setTimeout(() => {
        // In a real implementation, this would trigger a server-side backup
        const date = new Date().toISOString().split('T')[0];
        const filename = `ictd_inventory_backup_${date}.sql`;
        
        Swal.close();
        
        Swal.fire({
            title: 'Backup Complete',
            html: `
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle me-2"></i>
                    Database backup completed successfully!
                </div>
                <p>Backup file: <strong>${filename}</strong></p>
            `,
            icon: 'success',
            confirmButtonText: 'Download Backup',
            showCancelButton: true,
            cancelButtonText: 'Close'
        }).then((result) => {
            if (result.isConfirmed) {
                // In a real implementation, this would trigger download of the backup file
                console.log(`Downloading backup: ${filename}`);
            }
        });
    }, 2000);
}

/**
 * Clear the system cache
 */
function clearSystemCache() {
    Swal.fire({
        title: 'Clear System Cache',
        text: 'Are you sure you want to clear the system cache? This may temporarily slow down the system.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Clear Cache',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Clearing Cache',
                html: 'Please wait while we clear the system cache...',
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Simulate cache clearing process
            setTimeout(() => {
                Swal.close();
                showToast('Cache cleared', 'System cache has been successfully cleared.');
            }, 1500);
        }
    });
}

/**
 * Confirm system reset
 */
function confirmSystemReset() {
    Swal.fire({
        title: 'Reset System',
        html: `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                This will reset all system settings to default values. This action cannot be undone.
            </div>
            <p>Please type <strong>RESET</strong> to confirm:</p>
            <input type="text" id="resetConfirmation" class="form-control mt-2">
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reset System',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        preConfirm: () => {
            const confirmText = document.getElementById('resetConfirmation').value;
            if (confirmText !== 'RESET') {
                Swal.showValidationMessage('Please type "RESET" to confirm');
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Resetting System',
                html: 'Please wait while we reset the system settings...',
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Simulate reset process
            setTimeout(() => {
                Swal.close();
                
                // In a real implementation, this would reset all settings on the server
                // and reload the page or reset the UI
                
                Swal.fire({
                    title: 'Reset Complete',
                    text: 'The system has been reset to default settings.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Reload settings
                    initializeSettings();
                });
            }, 2000);
        }
    });
}

/**
 * Show a toast notification
 * @param {string} title - Toast title
 * @param {string} message - Toast message
 * @param {string} icon - Toast icon (success, error, warning, info)
 */
function showToast(title, message, icon = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        text: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
} 