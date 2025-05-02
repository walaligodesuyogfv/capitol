/**
 * User API Client
 * Client-side JavaScript to interact with the RESTful User API
 * Provides functions for user management operations with improved UIs
 */

// Base URL for API
const API_URL = 'api_user.php';

/**
 * Get all users with pagination
 * 
 * @param {number} page - Page number to retrieve
 * @param {number} limit - Number of items per page
 * @param {function} callback - Callback function with results
 */
function getUsers(page = 1, limit = 7, callback) {
    // Show loading spinner
    const tableBody = document.getElementById('userTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading users...</p>
                </td>
            </tr>
        `;
    }

    // Fetch users from API
    fetch(`${API_URL}?page=${page}&limit=${limit}`)
        .then(response => response.json())
        .then(data => {
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-3 text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Failed to load users. Please try again.
                        </td>
                    </tr>
                `;
            }
        });
}

/**
 * Get a single user by ID
 * 
 * @param {number} userId - ID of the user to retrieve
 * @param {function} callback - Callback function with user data
 */
function getUser(userId, callback) {
    fetch(`${API_URL}?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        })
        .catch(error => {
            console.error('Error fetching user:', error);
            if (callback && typeof callback === 'function') {
                callback({ 
                    success: false, 
                    message: 'Failed to load user data. Please try again.' 
                });
            }
        });
}

/**
 * Create a new user
 * 
 * @param {object} userData - User data to create
 * @param {function} callback - Callback function with result
 */
function createUser(userData, callback) {
    // Show loading button state
    const saveBtn = document.getElementById('saveUserBtn');
    if (saveBtn) {
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Saving...';
        saveBtn.disabled = true;
    }

    // Make API request
    fetch(API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        // Reset button state
        if (saveBtn) {
            saveBtn.innerHTML = 'Save User';
            saveBtn.disabled = false;
        }

        if (data.success) {
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: 'User created successfully',
                icon: 'success',
                confirmButtonColor: '#198754',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-success px-4'
                },
                buttonsStyling: false
            }).then(() => {
                // Close modal if it exists
                const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                if (userModal) {
                    userModal.hide();
                }

                // Execute callback
                if (callback && typeof callback === 'function') {
                    callback(data);
                }
            });
        } else {
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to create user',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger px-4'
                },
                buttonsStyling: false
            });
        }
    })
    .catch(error => {
        // Reset button state
        if (saveBtn) {
            saveBtn.innerHTML = 'Save User';
            saveBtn.disabled = false;
        }

        console.error('Error creating user:', error);
        
        // Show error message
        Swal.fire({
            title: 'Error!',
            text: 'Failed to create user. Please try again.',
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-danger px-4'
            },
            buttonsStyling: false
        });
    });
}

/**
 * Update an existing user
 * 
 * @param {object} userData - User data to update
 * @param {function} callback - Callback function with result
 */
function updateUser(userData, callback) {
    // Show loading button state
    const saveBtn = document.getElementById('saveUserBtn');
    if (saveBtn) {
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Updating...';
        saveBtn.disabled = true;
    }

    // Make API request
    fetch(API_URL, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        // Reset button state
        if (saveBtn) {
            saveBtn.innerHTML = 'Save Changes';
            saveBtn.disabled = false;
        }

        if (data.success) {
            // Show success message
            Swal.fire({
                title: 'Success!',
                text: 'User updated successfully',
                icon: 'success',
                confirmButtonColor: '#198754',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-success px-4'
                },
                buttonsStyling: false
            }).then(() => {
                // Close modal if it exists
                const userModal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
                if (userModal) {
                    userModal.hide();
                }

                // Execute callback
                if (callback && typeof callback === 'function') {
                    callback(data);
                }
            });
        } else {
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to update user',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger px-4'
                },
                buttonsStyling: false
            });
        }
    })
    .catch(error => {
        // Reset button state
        if (saveBtn) {
            saveBtn.innerHTML = 'Save Changes';
            saveBtn.disabled = false;
        }

        console.error('Error updating user:', error);
        
        // Show error message
        Swal.fire({
            title: 'Error!',
            text: 'Failed to update user. Please try again.',
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-danger px-4'
            },
            buttonsStyling: false
        });
    });
}

/**
 * Delete a user
 * 
 * @param {number} userId - ID of the user to delete
 * @param {string} username - Username for confirmation
 * @param {function} callback - Callback function with result
 */
function deleteUser(userId, username, callback) {
    // Show confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
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
            // Make API request
            fetch(API_URL, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'User has been deleted.',
                        icon: 'success',
                        confirmButtonColor: '#198754',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-success px-4'
                        },
                        buttonsStyling: false
                    });

                    // Execute callback
                    if (callback && typeof callback === 'function') {
                        callback(data);
                    }
                } else {
                    // Show error message
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to delete user',
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-danger px-4'
                        },
                        buttonsStyling: false
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                
                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete user. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger px-4'
                    },
                    buttonsStyling: false
                });
            });
        }
    });
}

/**
 * Setup the users table initialization
 * 
 * @param {function} callback - Callback function after setup
 */
function setupUsersTable(callback) {
    // Show loading state in button
    const setupBtn = document.getElementById('setupUsersBtn');
    if (setupBtn) {
        const originalText = setupBtn.innerHTML;
        setupBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Setting up...';
        setupBtn.disabled = true;
    }

    // Make API request
    fetch('setup_users_table.php')
        .then(response => response.json())
        .then(data => {
            // Reset button state
            if (setupBtn) {
                setupBtn.innerHTML = '<i class="bi bi-database-gear"></i> Setup Users Table';
                setupBtn.disabled = false;
            }

            if (data.success) {
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Users table setup successfully',
                    icon: 'success',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-success px-4'
                    },
                    buttonsStyling: false
                });

                // Update button style
                if (setupBtn) {
                    setupBtn.classList.remove('btn-warning');
                    setupBtn.classList.add('btn-outline-secondary');
                }

                // Execute callback
                if (callback && typeof callback === 'function') {
                    callback(data);
                }
            } else {
                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to setup users table',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger px-4'
                    },
                    buttonsStyling: false
                });
            }
        })
        .catch(error => {
            // Reset button state
            if (setupBtn) {
                setupBtn.innerHTML = '<i class="bi bi-database-gear"></i> Setup Users Table';
                setupBtn.disabled = false;
            }

            console.error('Error setting up users table:', error);
            
            // Show error message
            Swal.fire({
                title: 'Error!',
                text: 'Error setting up users table. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-danger px-4'
                },
                buttonsStyling: false
            });
        });
} 