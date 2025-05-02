/**
 * Global navigation function for the admin dashboard
 * This function handles the URL updating and section visibility
 */

function updateURL(section) {
    console.log(`Updating to section: ${section}`);
    
    // Update URL hash
    window.location.hash = `#${section}`;
    
    // Hide all sections
    const dashboardSection = document.querySelector('.dashboard-section');
    const inventorySection = document.querySelector('.inventory-section');
    const poSection = document.querySelector('.po-section');
    const parSection = document.querySelector('.par-section');
    const receivedSection = document.querySelector('.received-section');
    const reportsSection = document.querySelector('.reports-section');
    const usersSection = document.querySelector('.users-section');
    const settingsSection = document.querySelector('.settings-section');
    
    // Hide all sections
    dashboardSection.classList.add('d-none');
    inventorySection.classList.add('d-none');
    poSection.classList.add('d-none');
    parSection.classList.add('d-none');
    if (receivedSection) receivedSection.classList.add('d-none');
    if (reportsSection) reportsSection.classList.add('d-none');
    if (usersSection) usersSection.classList.add('d-none');
    if (settingsSection) settingsSection.classList.add('d-none');
    
    // Remove active class from all links
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Show the selected section
    switch (section) {
        case 'dashboard':
            dashboardSection.classList.remove('d-none');
            document.getElementById('dashboard-link').classList.add('active');
            break;
        case 'inventory':
            inventorySection.classList.remove('d-none');
            document.getElementById('inventory-link').classList.add('active');
            break;
        case 'po':
            poSection.classList.remove('d-none');
            document.getElementById('po-link').classList.add('active');
            break;
        case 'par':
            parSection.classList.remove('d-none');
            document.getElementById('par-link').classList.add('active');
            break;
        case 'received':
            if (receivedSection) {
                receivedSection.classList.remove('d-none');
                document.getElementById('received-link').classList.add('active');
            }
            break;
        case 'reports':
            if (reportsSection) {
                reportsSection.classList.remove('d-none');
                document.getElementById('Reports-link').classList.add('active');
                
                // Initialize reports when the section is shown
                if (typeof initializeReportsSection === 'function') {
                    initializeReportsSection();
                } else if (window.initializeReportsSection) {
                    window.initializeReportsSection();
                }
            }
            break;
        case 'users':
            if (usersSection) {
                usersSection.classList.remove('d-none');
                document.getElementById('users-link').classList.add('active');
            }
            break;
        case 'settings':
            if (settingsSection) {
                settingsSection.classList.remove('d-none');
                // Select the settings link
                const settingsLink = document.querySelector('a[href="#settings"]');
                if (settingsLink) {
                    settingsLink.classList.add('active');
                }
                
                // Initialize settings if the function exists
                if (typeof initializeSettings === 'function') {
                    initializeSettings();
                }
            }
            break;
        default:
            // Default to dashboard
            dashboardSection.classList.remove('d-none');
            document.getElementById('dashboard-link').classList.add('active');
    }
    
    // Special handling for sections
    switch (section) {
        case 'users':
            console.log('Loading users section');
            // Ensure user management is initialized
            if (typeof initUserManagement === 'function') {
                initUserManagement();
            } else {
                console.error('User management function not found!');
            }
            break;
        case 'dashboard':
            console.log('Loading dashboard section');
            if (typeof initDashboardAnalytics === 'function') {
                initDashboardAnalytics();
            }
            break;
        case 'inventory':
            console.log('Loading inventory section');
            // Any special inventory section initialization
            break;
        case 'po':
            console.log('Loading PO section');
            // Any special PO section initialization
            break;
        case 'par':
            console.log('Loading PAR section');
            // Any special PAR section initialization
            break;
        case 'settings':
            console.log('Loading settings section');
            // Any special settings section initialization
            break;
    }
}

// Initialize navigation on document load
document.addEventListener('DOMContentLoaded', function() {
    // Get URL hash for navigation
    function getHash() {
        return window.location.hash.substring(1);
    }

    // Update navigation based on hash
    function updateNavigationFromHash() {
        const hash = getHash();
        if (hash) {
            navigateToSection(hash);
        } else {
            // Default to dashboard if no hash
            navigateToSection('dashboard');
        }
    }

    // Handle navigation to a specific section
    function navigateToSection(section) {
        // Hide all sections
        document.querySelectorAll('.dashboard-section, .inventory-section, .po-section, .par-section, .received-section, .reports-section, .users-section, .settings-section').forEach(function(el) {
            el.classList.add('d-none');
        });
        
        // Remove active class from all nav links
        document.querySelectorAll('.sidebar .nav-link').forEach(function(el) {
            el.classList.remove('active');
        });
        
        // Add active class to current nav link
        const navLink = document.getElementById(section + '-link');
        if (navLink) {
            navLink.classList.add('active');
        } else if (section === 'reports') {
            // Special case for Reports section due to capitalization
            const reportsLink = document.getElementById('Reports-link');
            if (reportsLink) {
                reportsLink.classList.add('active');
            }
        } else if (section === 'settings') {
            // Settings link might not have an ID
            const settingsLink = document.querySelector('a[href="#settings"]');
            if (settingsLink) {
                settingsLink.classList.add('active');
            }
        }
        
        // Show current section
        let sectionElement;
        
        switch(section) {
            case 'dashboard':
                sectionElement = document.querySelector('.dashboard-section');
                break;
            case 'inventory':
                sectionElement = document.querySelector('.inventory-section');
                loadInventoryItems();
                break;
            case 'po':
                sectionElement = document.querySelector('.po-section');
                loadPOs();
                break;
            case 'par':
                sectionElement = document.querySelector('.par-section');
                loadPARs();
                break;
            case 'received':
                sectionElement = document.querySelector('.received-section');
                break;
            case 'reports':
                sectionElement = document.querySelector('.reports-section');
                // Initialize reports section if the function exists
                if (typeof initializeReportsSection === 'function') {
                    initializeReportsSection();
                } else if (window.initializeReportsSection) {
                    window.initializeReportsSection();
                }
                break;
            case 'users':
                sectionElement = document.querySelector('.users-section');
                loadUsers();
                break;
            case 'settings':
                sectionElement = document.querySelector('.settings-section');
                // Initialize settings if the function exists
                if (typeof initializeSettings === 'function') {
                    initializeSettings();
                }
                break;
            default:
                sectionElement = document.querySelector('.dashboard-section');
        }
        
        if (sectionElement) {
            sectionElement.classList.remove('d-none');
        }
    }

    // Update URL when navigating
    window.updateURL = function(section) {
        window.location.hash = section;
        navigateToSection(section);
    };

    // Listen for hash changes
    window.addEventListener('hashchange', updateNavigationFromHash);

    // Initialize navigation based on current URL
    updateNavigationFromHash();
});

// Listen for hashchange event
window.addEventListener('hashchange', function() {
    const hash = window.location.hash.substring(1);
    if (hash) {
        console.log(`Hash change detected, navigating to: ${hash}`);
        updateURL(hash);
    }
}); 