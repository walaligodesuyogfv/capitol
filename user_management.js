/**
 * User Management JavaScript
 * Handles user-related functionalities in the admin dashboard
 */

// Global variables
let currentPage = 1;
let totalPages = 1;
let editMode = false;

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user section event listeners
    initUserManagement();
});

/**
 * Initialize user management functionality
 */
function initUserManagement() {
    // Check if users table exists first
    fetch('check_user_table.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!data.table_exists) {
                // Show alert that table doesn't exist
                showAlert('Users database table not found. Please click "Setup Users Table" to create it.', 'warning');
                
                // Show setup button
                const setupUsersBtn = document.getElementById('setupUsersBtn');
                if (setupUsersBtn) {
                    setupUsersBtn.classList.add('btn-warning');
                    setupUsersBtn.classList.remove('btn-outline-secondary');
                }
            } else {
                // Load users if table exists
                loadUsers(1);
            }
        }
    })
    .catch(error => {
        console.error('Error checking users table:', error);
    });

    // Button event listeners
    const addNewUserBtn = document.getElementById('addNewUserBtn');
    if (addNewUserBtn) {
        addNewUserBtn.addEventListener('click', resetUserForm);
    }
    
    const saveUserBtn = document.getElementById('saveUserBtn');
    if (saveUserBtn) {
        saveUserBtn.addEventListener('click', saveUser);
    }
    
    // Add setup button listener if it exists
    const setupUsersBtn = document.getElementById('setupUsersBtn');
    if (setupUsersBtn) {
        setupUsersBtn.addEventListener('click', function() {
            setupUsersTable(function(data) {
                // Reload users after setup
                loadUsers(1);
            });
        });
    }
    
    // Pagination button event listeners
    const userPrevBtn = document.getElementById('userPrevBtn');
    if (userPrevBtn) {
        userPrevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadUsers(currentPage);
            }
        });
    }
    
    const userNextBtn = document.getElementById('userNextBtn');
    if (userNextBtn) {
        userNextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                loadUsers(currentPage);
            }
        });
    }
}

/**
 * Reset user form when opening the modal
 */
function resetUserForm() {
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.reset();
        document.getElementById('userId').value = '';
        
        // Make password fields required for new users
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirmPassword');
        
        if (passwordField) passwordField.required = true;
        if (confirmPasswordField) confirmPasswordField.required = true;
        
        // Set edit mode to false
        editMode = false;
        
        // Update modal title
        const userModalLabel = document.getElementById('userModalLabel');
        if (userModalLabel) {
            userModalLabel.textContent = 'Add New User';
        }
        
        // Update button text
        const saveUserBtn = document.getElementById('saveUserBtn');
        if (saveUserBtn) {
            saveUserBtn.textContent = 'Save User';
        }
    }
}

/**
 * Load users from the server with pagination
 * @param {number} page - Page number to load
 */
function loadUsers(page = 1) {
    getUsers(page, 7, function(data) {
        if (data.success) {
            // Store pagination info
            currentPage = data.pagination.page;
            totalPages = data.pagination.pages;
            
            // Update pagination controls
            updatePaginationControls(data.pagination);
            
            // Render users
            renderUsers(data.data);
        } else {
            // Error is handled in the API client
        }
    });
}

/**
 * Render users in the table
 * @param {Array} users - Array of user objects
 */
function renderUsers(users) {
    const tableBody = document.getElementById('userTableBody');
    if (!tableBody) return; // Exit if table body doesn't exist
    
    // Clear table body
    tableBody.innerHTML = '';
    
    if (!users || users.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-people text-muted mb-3" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0">No users found</p>
                        <p class="text-muted small">Add users to get started</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    // Add each user to the table
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Format date
        let formattedDate = 'N/A';
        if (user.created_at) {
            const createdDate = new Date(user.created_at);
            formattedDate = createdDate.toLocaleDateString() + ' ' + createdDate.toLocaleTimeString();
        }
        
        // Determine status badge class
        const statusBadgeClass = user.status === 'active' ? 'bg-success' : 'bg-secondary';
        
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>
                <span class="badge bg-${getRoleBadgeColor(user.role)}">${user.role || 'N/A'}</span>
            </td>
            <td><span class="badge ${statusBadgeClass}">${user.status || 'N/A'}</span></td>
            <td>${formattedDate}</td>
            <td class="text-center">
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary edit-user-btn" data-user-id="${user.user_id}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.user_id}" data-username="${user.username}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        // Add event listeners to buttons
        const editBtn = row.querySelector('.edit-user-btn');
        if (editBtn) {
            editBtn.addEventListener('click', () => editUser(user.user_id));
        }
        
        const deleteBtn = row.querySelector('.delete-user-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteUser(user.user_id, this.getAttribute('data-username'), function(data) {
                    // Reload users after successful deletion
                    loadUsers(currentPage);
                });
            });
        }
        
        tableBody.appendChild(row);
    });
}

/**
 * Get appropriate badge color for role
 * @param {string} role - User role
 * @returns {string} - Bootstrap color class
 */
function getRoleBadgeColor(role) {
    switch(role) {
        case 'admin':
            return 'primary';
        case 'manager':
            return 'info';
        case 'staff':
            return 'secondary';
        default:
            return 'secondary';
    }
}

/**
 * Update pagination controls
 * @param {Object} pagination - Pagination information
 */
function updatePaginationControls(pagination) {
    const prevBtn = document.getElementById('userPrevBtn');
    const nextBtn = document.getElementById('userNextBtn');
    const pageInfo = document.getElementById('userPageInfo');
    
    if (!prevBtn || !nextBtn || !pageInfo) return; // Exit if elements don't exist
    
    // Update pagination info text
    const start = pagination.total === 0 ? 0 : (pagination.page - 1) * pagination.limit + 1;
    const end = Math.min(pagination.page * pagination.limit, pagination.total);
    pageInfo.textContent = `Showing ${start}-${end} of ${pagination.total} users`;
    
    // Enable/disable previous button
    prevBtn.disabled = pagination.page <= 1;
    
    // Enable/disable next button
    nextBtn.disabled = pagination.page >= pagination.pages;
}

/**
 * Edit user
 * @param {number} userId - User ID to edit
 */
function editUser(userId) {
    // Get user data using API client
    getUser(userId, function(data) {
        if (data.success) {
            // Get form elements
            const userForm = document.getElementById('userForm');
            const userId = document.getElementById('userId');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const role = document.getElementById('userRole');
            const status = document.getElementById('status');
            
            // Fill form with user data
            if (userForm && userId && username && email && role && status) {
                userId.value = data.data.user_id;
                username.value = data.data.username;
                email.value = data.data.email;
                role.value = data.data.role || 'staff';
                status.value = data.data.status || 'active';
                
                // Make password fields optional for editing
                password.required = false;
                confirmPassword.required = false;
                
                // Clear password fields
                password.value = '';
                confirmPassword.value = '';
            }
            
            // Update modal title
            const userModalLabel = document.getElementById('userModalLabel');
            if (userModalLabel) {
                userModalLabel.textContent = 'Edit User';
            }
            
            // Update button text
            const saveUserBtn = document.getElementById('saveUserBtn');
            if (saveUserBtn) {
                saveUserBtn.textContent = 'Save Changes';
            }
            
            // Set edit mode
            editMode = true;
            
            // Show modal
            const userModal = new bootstrap.Modal(document.getElementById('userModal'));
            userModal.show();
        } else {
            // Error is handled in the API client
            showAlert('Failed to load user data', 'danger');
        }
    });
}

/**
 * Save a user (create or update)
 */
function saveUser() {
    // Get form data
    const userForm = document.getElementById('userForm');
    if (!userForm) return; // Exit if form doesn't exist
    
    // Create FormData object
    const formData = new FormData(userForm);
    
    // Convert FormData to object
    const userData = {};
    formData.forEach((value, key) => {
        userData[key] = value;
    });
    
    // Validate passwords
    if (!editMode && userData.password !== userData.confirm_password) {
        showAlert('Passwords do not match', 'danger');
        return;
    }
    
    // If editing and password is empty, remove it from data
    if (editMode && (!userData.password || userData.password.trim() === '')) {
        delete userData.password;
        delete userData.confirm_password;
    }
    
    if (editMode) {
        // Update existing user
        updateUser(userData, function(data) {
            if (data.success) {
                // Success message is handled in the API client
                loadUsers(currentPage);
            }
        });
    } else {
        // Create new user
        createUser(userData, function(data) {
            if (data.success) {
                // Success message is handled in the API client
                loadUsers(currentPage);
            }
        });
    }
}

/**
 * Show alert message
 * @param {string} message - Message to display
 * @param {string} type - Alert type (success, info, warning, danger)
 */
function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add alert to the page
    const userSection = document.querySelector('.users-section');
    if (userSection) {
        userSection.insertBefore(alertDiv, userSection.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
}

/**
 * Setup users table
 */
function setupUsersTable(callback) {
    // Show loading state in button
    const setupBtn = document.getElementById('setupUsersBtn');
    if (setupBtn) {
        const originalContent = setupBtn.innerHTML;
        setupBtn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Setting up...';
        setupBtn.disabled = true;
        
        // Call API to setup users table
        fetch('setup_users_table.php')
        .then(response => response.json())
        .then(data => {
            // Reset button
            setupBtn.innerHTML = originalContent;
            setupBtn.disabled = false;
            
            if (data.success) {
                // Show success message
                showAlert(data.message, 'success');
                
                // Update button style
                setupBtn.classList.remove('btn-warning');
                setupBtn.classList.add('btn-outline-secondary');
                
                // Reload users
                loadUsers(1);
                
                if (callback) {
                    callback(data);
                }
            } else {
                // Show error message
                showAlert(data.message || 'Failed to setup users table', 'danger');
            }
        })
        .catch(error => {
            // Reset button
            setupBtn.innerHTML = originalContent;
            setupBtn.disabled = false;
            
            console.error('Error setting up users table:', error);
            showAlert('Error setting up users table', 'danger');
        });
    }
}

/**
 * Delete a user
 * @param {number} userId - User ID to delete
 * @param {string} username - Username for confirmation
 */
function deleteUser(userId, username, callback) {
    // Show SweetAlert2 confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: `You won't be able to revert this!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No',
        customClass: {
            confirmButton: 'btn btn-danger px-4',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request to server
            fetch('user_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    user_id: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Deleted!',
                        text: data.message || 'User deleted successfully.',
                        icon: 'success',
                        confirmButtonColor: '#198754',
                        customClass: {
                            confirmButton: 'btn btn-success px-4'
                        },
                        buttonsStyling: false
                    });
                    
                    // Reload users
                    loadUsers(currentPage);
                    
                    if (callback) {
                        callback(data);
                    }
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to delete user.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        customClass: {
                            confirmButton: 'btn btn-danger px-4'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while deleting the user.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    customClass: {
                        confirmButton: 'btn btn-danger px-4'
                    },
                    buttonsStyling: false
                });
            });
        }
    });
} 