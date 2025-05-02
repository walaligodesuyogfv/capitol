/**
 * User Navigation JavaScript
 * Handles proper navigation to/from user section
 */

// Define global updateURL function
function updateURL(section) {
    // Update URL hash
    window.location.hash = section;
    
    // Hide all sections
    const sections = document.querySelectorAll('.content-wrapper > div[class$="-section"]');
    sections.forEach(s => s.classList.add('d-none'));
    
    // Show the selected section
    let sectionClass = `${section}-section`;
    
    // Special case for user/users section
    if (section === 'user') {
        sectionClass = 'users-section';
    }
    
    const sectionToShow = document.querySelector(`.${sectionClass}`);
    if (sectionToShow) {
        sectionToShow.classList.remove('d-none');
    } else if (section === 'settings') {
        // Handle settings section if it exists
        const settingsSection = document.querySelector('.settings-section');
        if (settingsSection) {
            settingsSection.classList.remove('d-none');
        }
    }
    
    // Update active link
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => link.classList.remove('active'));
    
    const activeLink = document.querySelector(`.sidebar .nav-link[href="#${section}"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

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
        // Update active link
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            link.classList.remove('active');
        });

        const activeLink = document.getElementById(section + '-link');
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Hide all sections
        document.querySelectorAll('.content-wrapper > div[class$="-section"]').forEach(function(div) {
            div.classList.add('d-none');
        });

        // Show the requested section
        const sectionDiv = document.querySelector('.' + section + '-section');
        if (sectionDiv) {
            sectionDiv.classList.remove('d-none');
            
            // Trigger section-specific initialization if needed
            if (section === 'reports') {
                // Initialize reports if that script exists
                if (typeof initializeReportsSection === 'function') {
                    initializeReportsSection();
                }
            } else if (section === 'dashboard') {
                // Initialize dashboard charts if those functions exist
                if (typeof initializeDashboardCharts === 'function') {
                    initializeDashboardCharts();
                }
            }
        } else {
            console.error('Invalid section: ' + section);
            // Show an error message
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid section: ' + section,
                timer: 2000,
                showConfirmButton: false
            });
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

    // Setup sidebar toggle for mobile
    const sidebarToggleBtn = document.querySelector('.sidebar-toggle');
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.content-wrapper').classList.toggle('expanded');
        });
    }
}); 