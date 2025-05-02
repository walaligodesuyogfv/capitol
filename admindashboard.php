<?php
// Get analytics data for the dashboard
$pythonScript = "DataAnalytics/get_analytics_data.py";
$analyticsData = null;

// Try to get analytics data
if (file_exists($pythonScript)) {
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg("monthly");
    $output = shell_exec($command);
    if (!$output) {
        $outputLines = [];
        exec($command, $outputLines);
        $output = implode("\n", $outputLines);
    }
        if ($output) {
        $analyticsData = json_decode($output, true);
        if (!$analyticsData || json_last_error() !== JSON_ERROR_NONE) {
            // Log the error for debugging
            error_log("Failed to parse analytics data: " . json_last_error_msg());
            error_log("Raw output: " . $output);
            $analyticsData = null;
        }
    }
}
if (file_exists('track_ml_data.php')) {
    include 'track_ml_data.php';
}
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    // Define a fallback function if db.php doesn't exist
    function getConnection()
    {
        return null;
    }
}

// Load metrics functions
if (file_exists('metrics.php')) {
    include 'metrics.php';
} else {
    // Define a fallback function if metrics.php doesn't exist
    function getSystemMetrics()
    {
        return [
            'total_items' => 0,
            'inventory_count' => 0,
            'po_count' => 0,
            'par_count' => 0
        ];
    }
}

// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Get username from session
$username = $_SESSION['username'];

// Get system metrics for the dashboard
$metrics = getSystemMetrics();

// Handle form submission for tracking data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if we're saving inventory, PO, or PAR data
    if (isset($_POST['track_for_prediction']) && $_POST['track_for_prediction'] === 'true') {
        // Track data for ML predictions
        $trackingData = isset($_POST['tracking_data']) ? json_decode($_POST['tracking_data'], true) : null;
        
        if ($trackingData) {
            if (isset($_POST['item_name'])) {
                // Track inventory item
                if (function_exists('trackInventoryForML')) {
                    $result = trackInventoryForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            } else if (isset($_POST['po_no'])) {
                // Track PO
                if (function_exists('trackPOForML')) {
                    $result = trackPOForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            } else if (isset($_POST['par_no'])) {
                // Track PAR
                if (function_exists('trackPARForML')) {
                    $result = trackPARForML($trackingData);
                    if ($result['success']) {
                        // Tracking successful
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICTD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <link rel="stylesheet" href="script.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ml-regression@4.4.1/dist/ml-regression.min.js"></script>
    <link rel="icon" type="image/x-icon" href="image.jpg">
</head>
<body>  
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="crypto.jpg" alt="Logo">
            <div class="logo-text">
                <i class="bi bi-laptop"></i> STI SYSTEM
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" id="dashboard-link" onclick="updateURL('dashboard')">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#inventory" id="inventory-link" onclick="updateURL('inventory')">
                    <i class="bi bi-box-seam"></i> <span>Inventory</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#po" id="po-link" onclick="updateURL('po')">
                    <i class="bi bi-cart3"></i> <span>PO</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#par" id="par-link" onclick="updateURL('par')">
                    <i class="bi bi-receipt"></i> <span>PAR</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#received" id="received-link" onclick="updateURL('received')">
                    <i class="bi bi-box-seam"></i> <span>Received</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports" id="Reports-link" onclick="updateURL('reports')">
                    <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#users" id="users-link" onclick="updateURL('users')">
                    <i class="bi bi-people"></i> <span>User</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings" onclick="updateURL('settings')">
                    <i class="bi bi-gear"></i> <span>Settings</span>
                </a>
            </li>
            <li class="nav-item mt-5">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-left"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
        <!-- Inventory Section -->
        <div class="inventory-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-box-seam"></i> Inventory Items</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                            <i class="bi bi-plus-circle"></i> Add New Item
                        </button>
                        <button class="btn btn-success btn-sm" id="exportBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="printBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="print-section">
                        <div class="print-header d-none">
                            <h2>ICTD Inventory Management System</h2>
                            <p>Inventory Report</p>
                            <p>Date: <span id="printDate"></span></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="search-box d-flex align-items-center gap-3">
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2"></i>
                                    <input type="text" id="inventorySearchInput" class="form-control ps-4" placeholder="Search inventory..." style="width: 250px;">
                                </div>
                                <div class="filter-group d-flex gap-2">
                                    <select class="form-select" id="conditionFilter" style="width: 150px;">
                                        <option value="">All Conditions</option>
                                        <option value="New">New</option>
                                        <option value="Good">Good</option>
                                        <option value="Fair">Fair</option>
                                        <option value="Poor">Poor</option>
                                    </select>
                                    <select class="form-select" id="locationFilter" style="width: 150px;">
                                        <option value="">All Locations</option>
                                        <option value="Office">Office</option>
                                        <option value="Storage">Storage</option>
                                    </select>
                                </div>
                            </div>
                            <div class="admin-actions">
                                <button class="btn btn-sm btn-outline-secondary" id="setupConditionTables" title="Install condition monitoring tables">
                                    <i class="bi bi-wrench"></i> Setup Condition Monitoring
                                </button>
                                <button class="btn btn-sm btn-outline-primary" id="setupRequiredTables" title="Install required database tables">
                                    <i class="bi bi-database-gear"></i> Setup Required Tables
                                </button>
                            </div>
                        </div>

                        <!-- Serial Number Scanner Button - Moved to its own row -->
                        <div class="scan-button-container mb-3">
                            <!-- Scan result notification area -->
                            <div id="scanResultNotification" class="scan-result ms-auto d-none">
                                <span class="scan-result-status"></span>
                                <span class="scan-result-text"></span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                        <th>Item ID</th>
                                        <th>Item Name</th>
                                        <th>Brand/Model</th>
                                        <th>Serial Number</th>
                                        <th>Purchase Date</th>
                                        <th>Warranty</th>
                                        <th>Assigned To</th>
                                        <th>Location</th>
                                        <th>Condition</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="inventoryTableBody">
                                    <!-- Data will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Add pagination controls for inventory -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="inventoryPageInfo">Showing 1-7 of 0 items</span>
                            </div>
                            <div class="pagination-controls">
                                <button id="inventoryPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                                <button id="inventoryNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Order Section -->
        <div class="po-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-cart3"></i> Purchase Orders</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPOModal">
                            <i class="bi bi-plus-circle"></i> Create New PO
                        </button>
                        <button class="btn btn-success btn-sm" id="exportPOBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="printPOListBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="print-section">
                        <div class="print-header d-none">
                            <h2>ICTD Inventory Management System</h2>
                            <p>Purchase Orders Report</p>
                            <p>Date: <span id="printPODate"></span></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="search-box d-flex align-items-center gap-3">
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2"></i>
                                    <input type="text" id="poSearchInput" class="form-control ps-4" placeholder="Search PO..." style="width: 250px;">
                                </div>
                                <div class="filter-group d-flex gap-2">
                                    <select class="form-select" id="poSupplierFilter" style="width: 150px;">
                                        <option value="">All Suppliers</option>
                                        <!-- Will be populated dynamically -->
                                    </select>
                                    <select class="form-select" id="poDateFilter" style="width: 150px;">
                                        <option value="">All Dates</option>
                                        <option value="30">Last 30 Days</option>
                                        <option value="90">Last 90 Days</option>
                                        <option value="180">Last 6 Months</option>
                                        <option value="365">Last Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm" id="poTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="po-number-col">PO NO.</th>
                                        <th class="supplier-col">SUPPLIER</th>
                                        <th class="date-col">DATE</th>
                                        <th class="amount-col">TOTAL AMOUNT</th>
                                        <th class="actions-col">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="poTableBody">
                                    <!-- Data will be loaded dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Add pagination controls for PO -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="poPageInfo">Showing 1-7 of 0 purchase orders</span>
                            </div>
                            <div class="pagination-controls">
                                <button id="poPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                                <button id="poNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div class="dashboard-section d-none">
            <div class="dashboard-header mb-4 d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-speedometer2"></i> Dashboard Overview</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Updated Notification Dropdown -->
                    <div class="dropdown">
                        <button class="btn position-relative shadow-sm" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notification-menu p-0 border-0 shadow" aria-labelledby="notificationDropdown">
                            <div class="notification-header border-bottom p-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h6>
                                <button id="refreshActivitiesBtn" class="btn btn-sm btn-link text-decoration-none p-0">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="notification-body">
                                <div class="expiring-soon p-3 border-bottom">
                                    <h6 class="text-warning mb-3">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Expiring Soon
                                    </h6>
                                    <div id="expiringItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div class="text-muted small py-2">No items expiring soon</div>
                                    </div>
                                </div>
                                <div class="expired p-3 border-bottom">
                                    <h6 class="text-danger mb-3">
                                        <i class="bi bi-x-circle me-1"></i> Expired
                                    </h6>
                                    <div id="expiredItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div class="text-muted small py-2">No expired items</div>
                                    </div>
                                </div>
                                <div class="recently-added p-3">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-plus-circle me-1"></i> Recently Added Inventory
                                    </h6>
                                    <div id="recentInventoryItems" class="notification-list">
                                        <!-- Dynamically populated -->
                                        <div id="noRecentInventoryNotif" class="text-muted small py-2">No recently added items</div>
                                        <div id="recentInventoryListNotif"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="notification-footer border-top p-2 text-center">
                                <small class="text-muted">Click on any notification to view item details</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warranty Bills container hidden by default -->
            <div id="warrantyBills" class="d-none">
                <div class="warranty-bills-container">
                    <h6 class="mb-3 text-primary"><i class="bi bi-receipt-cutoff me-2"></i>Bills & Warranty Notifications</h6>
                    <div class="warranty-bills-list">
                        <!-- This will be dynamically populated -->
                        <div class="text-muted small py-2">No pending bills or warranty notifications</div>
                    </div>
                </div>
            </div>

            <!-- Stats grid -->
            <div class="stats-grid">
                <!-- Total Items -->
                <div class="dashboard-stats total-items">
                    <div class="stats-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['total_items']) ? htmlspecialchars($metrics['total_items']) : '0'; ?></div>
                    <div class="stats-label">Total Items</div>
                </div>
                <!-- Inventory -->
                <div class="dashboard-stats inventory">
                    <div class="stats-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['inventory_count']) ? htmlspecialchars($metrics['inventory_count']) : '0'; ?></div>
                    <div class="stats-label">Inventory</div>
                </div>
                <!-- PO -->
                <div class="dashboard-stats po">
                    <div class="stats-icon">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['po_count']) ? htmlspecialchars($metrics['po_count']) : '0'; ?></div>
                    <div class="stats-label">PO</div>
                </div>
                <!-- PAR -->
                <div class="dashboard-stats par">
                    <div class="stats-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="stats-number"><?php echo isset($metrics['par_count']) ? htmlspecialchars($metrics['par_count']) : '0'; ?></div>
                    <div class="stats-label">PAR</div>
                </div>
            </div>
            
            <!-- Dashboard Charts and ML Predictions -->
            <div class="row g-3 mt-2">
                <!-- Performance Analytics Section - Full Width -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Performance Analytics</h6>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" id="weeklyChartBtn">Weekly</button>
                                <button class="btn btn-sm btn-outline-primary active" id="monthlyChartBtn">Monthly</button>
                                <button class="btn btn-sm btn-outline-primary" id="quarterlyChartBtn">Quarterly</button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div id="analyticsChartContainer" class="chart-container" style="position: relative; height:250px; width:100%">
                                <canvas id="analyticsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Forecast Row - PO and PAR side by side -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-graph-up me-2"></i>PO Forecast</h6>
                            <button id="refreshPoPredictionBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="poChartContainer" class="chart-container" style="position: relative; height:180px; width:100%">
                                <canvas id="poForecastChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Next Month</div>
                                            <div id="poNextMonthPrediction" class="fs-5 fw-bold text-primary">₱3,400,549</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Accuracy</div>
                                            <div id="poAccuracyScore" class="fs-5 fw-bold text-success">89%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-graph-up me-2"></i>PAR Forecast</h6>
                            <button id="refreshParPredictionBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">             
                            <div id="parChartContainer" class="chart-container" style="position: relative; height:180px; width:100%">
                                <canvas id="parForecastChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Next Month</div>
                                            <div id="parNextMonthPrediction" class="fs-5 fw-bold text-primary">₱1,800,000</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 text-center shadow-sm">
                                            <div class="text-muted small fw-medium">Accuracy</div>
                                            <div id="parAccuracyScore" class="fs-5 fw-bold text-success">92%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ML Prediction Dashboard Row -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary fw-semibold"><i class="bi bi-robot me-2"></i>ML Prediction Dashboard</h6>
                            <button id="refreshPredictions" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <!-- Left side: Maintenance Predictions -->
                                <div class="col-md-6 border-end">
                                    <div class="p-3">
                                        <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-tools me-2"></i>Maintenance Predictions</h6>
                                        <div id="maintenancePredictions" class="maintenance-predictions">
                                            <div class="text-center text-muted py-3 d-none" id="maintenanceLoading">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading predictions...</p>
                                            </div>
                                            <div id="maintenanceItems">
                                                <div class="list-group list-group-flush maintenance-list">
                                                    <!-- Enhanced design for maintenance predictions -->
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-warning border-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Medium</span>
                                                            </div>
                                                          
                                                            <div class="flex-shrink-0 ms-3">
                                                                <button class="btn btn-sm btn-outline-warning">
                                                                    <i class="bi bi-wrench"></i> Schedule
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-danger border-4">
                                            
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="maintenance-item p-3 mb-2 rounded-3 bg-light border-start border-info border-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="badge bg-info text-dark rounded-pill px-3 py-2">Low</span>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h6 class="mb-1 fw-semibold">Monitor Dell P2419H</h6>
                                                                <div class="d-flex align-items-center mb-1">
                                                                    <i class="bi bi-upc-scan text-muted me-2"></i>
                                                                    <span class="text-muted small">Serial: MN74650</span>
                                                                </div>
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bi bi-calendar-check text-muted me-2"></i>
                                                                    <span class="text-muted small">Predicted maintenance: 47 days</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex-shrink-0 ms-3">
                                                                <button class="btn btn-sm btn-outline-info">
                                                                    <i class="bi bi-wrench"></i> Schedule
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="progress mt-2" style="height: 6px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="noMaintenanceItems" class="d-none">
                                                <div class="text-center py-3">
                                                    <i class="bi bi-check-circle-fill text-success mb-2" style="font-size: 1.5rem;"></i>
                                                    <p>No maintenance required at this time</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Right side: Inventory Suggestions -->
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <h6 class="fw-semibold mb-3 text-primary"><i class="bi bi-box-seam me-2"></i>Inventory Suggestions</h6>
                                        <div id="inventorySuggestions" class="inventory-suggestions">
                                            <div class="text-center text-muted py-3 d-none" id="suggestionsLoading">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2">Loading suggestions...</p>
                                            </div>
                                            <div id="inventoryItems">
                                                <!-- Enhanced design for inventory suggestions -->
                                                <div class="card border-0 shadow-sm mb-3">
                                                    <div class="card-body p-0">
                                                        <div class="recommendation-item p-3">
                    
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="noInventoryItems" class="d-none">
                                                <div class="text-center py-3">
                                                    <i class="bi bi-search text-primary mb-2" style="font-size: 1.5rem;"></i>
                                                    <p>No inventory suggestions available</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Acknowledgement Receipt Section -->
        <div class="par-section d-none">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Property Acknowledgement Receipts</h5>
                    <button class="btn btn-primary" id="newParBtn" data-bs-toggle="modal" data-bs-target="#addPARModal">
                        <i class="bi bi-plus-circle"></i> Create New PAR
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="parTable" class="table par-table table-striped table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>PAR No.</th>
                                    <th>Date Acquired</th>
                                    <th>Property Number</th>
                                    <th>Received By</th>
                                    <th>Amount</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="parTableBody">
                                <!-- PARs will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div class="users-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-people"></i> User Management</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" id="addNewUserBtn" data-bs-toggle="modal" data-bs-target="#userModal">
                            <i class="bi bi-plus-circle"></i> Add New User
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="setupUsersBtn" title="Setup users database tables">
                            <i class="bi bi-database-gear"></i> Setup Users Table
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- User form container - this was missing -->
                    <div id="userFormContainer" class="mb-4 d-none">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-primary" id="userFormTitle">Add New User</h6>
                                <button type="button" class="btn-close" id="cancelUserBtn" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Add pagination controls for users -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <span id="userPageInfo">Showing 1-7 of 0 users</span>
                        </div>
                        <div class="pagination-controls">
                            <button id="userPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                            <button id="userNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="reports-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-file-earmark-bar-graph"></i> Reports</h5>
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" id="generateReportBtn">
                            <i class="bi bi-gear-fill"></i> Generate Report
                        </button>
                        <button class="btn btn-success btn-sm" id="exportReportBtn">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </button>
                        <button class="btn btn-info btn-sm text-white" id="printReportBtn">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button class="btn btn-danger btn-sm" id="exportPdfBtn">
                            <i class="bi bi-file-earmark-pdf"></i> Export to PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters and Options Row -->
                    <div class="row g-3 mb-4">
                        <!-- Report Type Selection -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 text-primary">Report Type</h6>
                                </div>
                                <div class="card-body">
                                    <select class="form-select" id="reportType">
                                        <option value="summary">Summary Reports</option>
                                        <option value="par_detailed">PAR Detailed Report</option>
                                        <option value="po_detailed">PO Detailed Report</option>
                                        <option value="inventory_detailed">Inventory Detailed Report</option>
                                        <option value="audit_logs">Audit Trail/History Logs</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 text-primary">Date Range</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">From</label>
                                            <input type="date" class="form-control form-control-sm" id="dateFrom">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">To</label>
                                            <input type="date" class="form-control form-control-sm" id="dateTo">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Filters -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 text-primary">Filters</h6>
                                </div>
                                <div class="card-body">
                                    <select class="form-select form-select-sm mb-2" id="departmentFilter">
                                        <option value="">All Departments</option>
                                        <option value="IT">IT Department</option>
                                        <option value="HR">HR Department</option>
                                        <option value="Finance">Finance Department</option>
                                        <option value="Operations">Operations</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Type Selection -->
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 text-primary">Chart Options</h6>
                                </div>
                                <div class="card-body">
                                    <select class="form-select form-select-sm mb-2" id="chartType">
                                        <option value="bar">Bar Chart</option>
                                        <option value="line">Line Chart</option>
                                        <option value="pie">Pie Chart</option>
                                        <option value="doughnut">Doughnut Chart</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="timeInterval">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visualizations Row -->
                    <div class="row g-3 mb-4">
                        <!-- Chart 1: Total Assets per Department -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-primary">Total Assets per Department</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm refresh-chart" data-chart="assetsByDepartmentChart">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleChartType('assetsByDepartmentChart')">
                                            <i class="bi bi-bar-chart"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:250px; width:100%">
                                        <canvas id="assetsByDepartmentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart 2: PAR vs PO Total Value -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-primary">PAR vs PO Total Value</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm refresh-chart" data-chart="parVsPoChart">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleChartType('parVsPoChart')">
                                            <i class="bi bi-pie-chart"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:250px; width:100%">
                                        <canvas id="parVsPoChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Row of Charts -->
                    <div class="row g-3 mb-4">
                        <!-- Chart 3: Monthly Trend -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-primary">Monthly/Quarterly/Yearly Trend</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm refresh-chart" data-chart="monthlyTrendChart">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleChartType('monthlyTrendChart')">
                                            <i class="bi bi-graph-up"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:250px; width:100%">
                                        <canvas id="monthlyTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                                                
                        <!-- Chart 4: Asset Count vs Total Value -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-primary">Asset Count vs Total Value</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm refresh-chart" data-chart="assetValueChart">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleChartType('assetValueChart')">
                                            <i class="bi bi-bar-chart"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:250px; width:100%">
                                        <canvas id="assetValueChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Reports Section -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary"><i class="bi bi-table"></i> Detailed Report</h6>
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control form-control-sm" id="reportSearchInput" placeholder="Search report...">
                                <button class="btn btn-outline-secondary btn-sm" type="button">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- PAR Detailed Report Table -->
                            <div id="parDetailedReport" class="table-responsive report-table d-none">
                                <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PAR No.</th>
                                            <th>Item Name</th>
                                            <th>Description</th>
                                            <th>Quantity</th>
                                            <th>Unit Cost</th>
                                            <th>Total Cost</th>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Date Issued</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="parDetailedTableBody">
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- PO Detailed Report Table -->
                            <div id="poDetailedReport" class="table-responsive report-table d-none">
                                <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PO No.</th>
                                            <th>Supplier</th>
                                            <th>Item Description</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total Amount</th>
                                            <th>Date Ordered</th>
                                            <th>Date Delivered</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="poDetailedTableBody">
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Summary Report Table -->
                            <div id="summaryReport" class="table-responsive report-table">
                                <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Category</th>
                                            <th>Department</th>
                                            <th>Total Count</th>
                                            <th>Total Value</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody id="summaryTableBody">
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Inventory Detailed Report Table -->
                            <div id="inventoryDetailedReport" class="table-responsive report-table d-none">
                                <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Item Name</th>
                                            <th>Brand/Model</th>
                                            <th>Serial Number</th>
                                            <th>Condition</th>
                                            <th>Location</th>
                                            <th>Assigned To</th>
                                            <th>Purchase Date</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryDetailedTableBody">
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Audit Logs Report Table -->
                            <div id="auditLogsReport" class="table-responsive report-table d-none">
                                <table class="table table-striped table-bordered table-hover align-middle shadow-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Module</th>
                                            <th>Record ID</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="auditLogsTableBody">
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span id="reportPageInfo">Showing 1-10 of 0 records</span>
                                </div>
                                <div class="pagination-controls">
                                    <button id="reportPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </button>
                                    <button id="reportNextBtn" class="btn btn-sm btn-outline-primary ms-2">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Python Data Integration Section -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-primary"><i class="bi bi-cpu"></i> ML Predictions & Analytics</h6>
                            <button class="btn btn-primary btn-sm" id="runPredictionBtn">
                                <i class="bi bi-gear-fill"></i> Run Python Analysis
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Prediction Parameters</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Prediction Type</label>
                                                <select class="form-select" id="predictionType">
                                                    <option value="future_par">Future PAR Values</option>
                                                    <option value="future_po">Future PO Values</option>
                                                    <option value="asset_depreciation">Asset Depreciation</option>
                                                    <option value="maintenance_prediction">Maintenance Prediction</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Time Horizon</label>
                                                <select class="form-select" id="timeHorizon">
                                                    <option value="3">Next 3 Months</option>
                                                    <option value="6">Next 6 Months</option>
                                                    <option value="12">Next 12 Months</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confidence Level</label>
                                                <input type="range" class="form-range" min="0.7" max="0.99" step="0.01" value="0.95" id="confidenceLevel">
                                                <div class="d-flex justify-content-between">
                                                    <small>70%</small>
                                                    <small id="confidenceLevelDisplay">95%</small>
                                                    <small>99%</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Prediction Results</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="predictionLoading" class="text-center py-5 d-none">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-3">Running predictive analytics...</p>
                                                <p class="text-muted small">This may take a few moments</p>
                                            </div>
                                            <div id="predictionResults">
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle me-2"></i> Select parameters and click "Run Python Analysis" to generate predictions.
                                                </div>
                                                <div class="chart-container" style="position: relative; height:250px; width:100%">
                                                    <canvas id="predictionChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="settings-section d-none">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="bi bi-gear"></i> Settings</h5>
                    <button class="btn btn-success btn-sm" id="saveSettingsBtn">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
                <div class="card-body">
                    <!-- Settings Tabs -->
                    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="user-roles-tab" data-bs-toggle="tab" data-bs-target="#user-roles" type="button" role="tab" aria-controls="user-roles" aria-selected="true">User Roles & Permissions</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inventory-thresholds-tab" data-bs-toggle="tab" data-bs-target="#inventory-thresholds" type="button" role="tab" aria-controls="inventory-thresholds" aria-selected="false">Inventory Thresholds</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notification-settings-tab" data-bs-toggle="tab" data-bs-target="#notification-settings" type="button" role="tab" aria-controls="notification-settings" aria-selected="false">Notification Settings</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-settings-tab" data-bs-toggle="tab" data-bs-target="#system-settings" type="button" role="tab" aria-controls="system-settings" aria-selected="false">System Settings</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- User Roles & Permissions Tab -->
                        <div class="tab-pane fade show active" id="user-roles" role="tabpanel" aria-labelledby="user-roles-tab">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Role Management</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="roleSelector" class="form-label">Select Role</label>
                                                <select class="form-select" id="roleSelector">
                                                    <option value="admin">Administrator</option>
                                                    <option value="manager">Manager</option>
                                                    <option value="staff">Staff</option>
                                                    <option value="viewer">Viewer</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="roleDescription" class="form-label">Role Description</label>
                                                <textarea class="form-control" id="roleDescription" rows="3"></textarea>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-primary btn-sm" id="addNewRoleBtn">
                                                    <i class="bi bi-plus-circle"></i> Add New Role
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" id="deleteRoleBtn">
                                                    <i class="bi bi-trash"></i> Delete Role
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Module Permissions</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-bordered permissions-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Module</th>
                                                            <th class="text-center">View</th>
                                                            <th class="text-center">Create</th>
                                                            <th class="text-center">Edit</th>
                                                            <th class="text-center">Delete</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Dashboard</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="dashboard" data-action="view" checked></td>
                                                            <td class="text-center">-</td>
                                                            <td class="text-center">-</td>
                                                            <td class="text-center">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Inventory</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="inventory" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="inventory" data-action="create" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="inventory" data-action="edit" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="inventory" data-action="delete" checked></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Purchase Orders</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="po" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="po" data-action="create" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="po" data-action="edit" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="po" data-action="delete" checked></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Property Acknowledgement Receipts</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="par" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="par" data-action="create" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="par" data-action="edit" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="par" data-action="delete" checked></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Reports</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="reports" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="reports" data-action="create" checked></td>
                                                            <td class="text-center">-</td>
                                                            <td class="text-center">-</td>
                                                        </tr>
                                                        <tr>
                                                            <td>User Management</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="users" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="users" data-action="create" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="users" data-action="edit" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="users" data-action="delete" checked></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Settings</td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="settings" data-action="view" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="settings" data-action="create" checked></td>
                                                            <td class="text-center"><input type="checkbox" class="form-check-input permission-check" data-module="settings" data-action="edit" checked></td>
                                                            <td class="text-center">-</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Inventory Thresholds Tab -->
                        <div class="tab-pane fade" id="inventory-thresholds" role="tabpanel" aria-labelledby="inventory-thresholds-tab">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Threshold Management</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i> 
                                                <small>Define stock thresholds for inventory items. When stock falls below the threshold, the system will generate alerts.</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="defaultThreshold" class="form-label">Default Threshold (units)</label>
                                                <input type="number" class="form-control" id="defaultThreshold" value="5" min="1">
                                                <div class="form-text">Default minimum quantity before alerts are triggered</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enableAutoOrder" checked>
                                                    <label class="form-check-label" for="enableAutoOrder">Enable Auto-Order</label>
                                                </div>
                                                <div class="form-text">Automatically generate purchase orders when stock falls below threshold</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="thresholdPercentage" class="form-label">Critical Threshold Percentage</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="thresholdPercentage" value="20" min="1" max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <div class="form-text">Percentage of threshold to mark as critical (red alert)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-8">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 text-primary">Item-Specific Thresholds</h6>
                                            <div class="input-group" style="width: 300px;">
                                                <input type="text" id="thresholdSearchInput" class="form-control form-control-sm" placeholder="Search items...">
                                                <button class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-striped mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Item Name</th>
                                                            <th>Current Stock</th>
                                                            <th>Threshold</th>
                                                            <th>Status</th>
                                                            <th>Auto-Order</th>
                                                        </tr>
                                                    </thead>
                                                   
                                                </table>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span id="thresholdPageInfo">Showing 1-4 of 4 items</span>
                                                </div>
                                                <div class="pagination-controls">
                                                    <button id="thresholdPrevBtn" class="btn btn-sm btn-outline-primary" disabled>
                                                        <i class="bi bi-chevron-left"></i> Previous
                                                    </button>
                                                    <button id="thresholdNextBtn" class="btn btn-sm btn-outline-primary ms-2" disabled>
                                                        Next <i class="bi bi-chevron-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notification Settings Tab -->
                        <div class="tab-pane fade" id="notification-settings" role="tabpanel" aria-labelledby="notification-settings-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Alert Preferences</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Enable Alerts For:</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="lowStockAlert" checked>
                                                    <label class="form-check-label" for="lowStockAlert">
                                                        Low Stock Items
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="expiringWarrantyAlert" checked>
                                                    <label class="form-check-label" for="expiringWarrantyAlert">
                                                        Expiring Warranties
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="maintenanceReminderAlert" checked>
                                                    <label class="form-check-label" for="maintenanceReminderAlert">
                                                        Maintenance Reminders
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="newItemAlert" checked>
                                                    <label class="form-check-label" for="newItemAlert">
                                                        New Item Added
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="poStatusAlert" checked>
                                                    <label class="form-check-label" for="poStatusAlert">
                                                        PO Status Changes
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="alertFrequency" class="form-label">Alert Frequency</label>
                                                <select class="form-select" id="alertFrequency">
                                                    <option value="immediate">Immediate</option>
                                                    <option value="daily">Daily Digest</option>
                                                    <option value="weekly">Weekly Summary</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">Notification Delivery</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Notification Methods:</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="inAppNotification" checked>
                                                    <label class="form-check-label" for="inAppNotification">
                                                        In-App Notifications
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailNotification" checked>
                                                    <label class="form-check-label" for="emailNotification">
                                                        Email Notifications
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="emailRecipients" class="form-label">Email Recipients</label>
                                                <textarea class="form-control" id="emailRecipients" rows="3" placeholder="Enter email addresses (one per line)"></textarea>
                                                <div class="form-text">Enter additional email addresses to receive notifications</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="retentionPeriod" class="form-label">Notification Retention Period (days)</label>
                                                <input type="number" class="form-control" id="retentionPeriod" value="30" min="1">
                                                <div class="form-text">Number of days to keep notifications before auto-delete</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Settings Tab -->
                        <div class="tab-pane fade" id="system-settings" role="tabpanel" aria-labelledby="system-settings-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">General Settings</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="systemName" class="form-label">System Name</label>
                                                <input type="text" class="form-control" id="systemName" value="ICTD Inventory Management System">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="organizationName" class="form-label">Organization Name</label>
                                                <input type="text" class="form-control" id="organizationName" value="STI College">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="dateFormat" class="form-label">Date Format</label>
                                                <select class="form-select" id="dateFormat">
                                                    <option value="mm/dd/yyyy">MM/DD/YYYY</option>
                                                    <option value="dd/mm/yyyy">DD/MM/YYYY</option>
                                                    <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="timeFormat" class="form-label">Time Format</label>
                                                <select class="form-select" id="timeFormat">
                                                    <option value="12">12-hour (AM/PM)</option>
                                                    <option value="24">24-hour</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="currency" class="form-label">Currency</label>
                                                <select class="form-select" id="currency">
                                                    <option value="PHP">Philippine Peso (₱)</option>
                                                    <option value="USD">US Dollar ($)</option>
                                                    <option value="EUR">Euro (€)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-primary">System Maintenance</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="logRetention" class="form-label">Log Retention Period (days)</label>
                                                <input type="number" class="form-control" id="logRetention" value="90" min="1">
                                                <div class="form-text">Number of days to keep system logs before auto-delete</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="backupFrequency" class="form-label">Database Backup Frequency</label>
                                                <select class="form-select" id="backupFrequency">
                                                    <option value="daily">Daily</option>
                                                    <option value="weekly">Weekly</option>
                                                    <option value="monthly">Monthly</option>
                                                </select>
                                            </div>
                                            
                                            <div class="d-grid gap-2 mt-4">
                                                <button class="btn btn-primary" id="backupDatabaseBtn">
                                                    <i class="bi bi-download"></i> Backup Database Now
                                                </button>
                                                <button class="btn btn-warning" id="clearCacheBtn">
                                                    <i class="bi bi-trash"></i> Clear System Cache
                                                </button>
                                                <button class="btn btn-danger" id="resetSystemBtn">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Reset System
                                                </button>
                                                <button class="btn btn-info text-white" id="setupSettingsTablesBtn">
                                                    <i class="bi bi-database-gear"></i> Setup Settings Tables
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Inventory Item Modal -->
    <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addInventoryModalLabel">Add New Inventory Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                <div class="modal-body py-3">
                    <form id="addInventoryForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="inventoryTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="inventoryTrackingData" name="tracking_data">
                        
                        <div class="row g-3">
                            <!-- Two column layout to reduce vertical space -->
                            <div class="col-md-6">
                                <!-- Item Basic Info Section -->
                                <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Basic Information</h6>
                        </div>
                        <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="itemID" class="form-label">Item ID</label>
                                                <input type="text" class="form-control" id="itemID" name="itemID" placeholder="Enter item ID">
                                    </div>
                                            <div class="col-md-6">
                                                <label for="itemName" class="form-label">Item Name</label>
                                                <input type="text" class="form-control" id="itemName" name="item_name" placeholder="Enter item name" required>
                                    </div>

                                            <div class="col-md-6">
                                                <label for="Brand/model" class="form-label">Brand/Model</label>
                                                <input type="text" class="form-control" id="Brand/model" name="brand_model" placeholder="Enter Brand/Model">
                                    </div>
                                            <div class="col-md-6">
                                                <label for="serialNumber" class="form-label">Serial Number</label>
                                                <input type="text" class="form-control serial-number-field" id="serialNumber" name="serial_number" placeholder="Enter serial number">
                                                <div class="form-text text-muted small">Serial numbers must be unique.</div>
                                    </div>
                                    </div>
                                    </div>
                                    </div>
                                    </div>

                            <div class="col-md-6">
                                <!-- Purchase & Warranty Info Section -->
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Purchase & Warranty</h6>
                                </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="purchaseDate" class="form-label">Purchase Date</label>
                                                <input type="date" class="form-control" id="purchaseDate" name="purchase_date">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="warrantyDate" class="form-label">Warranty Expiration</label>
                                                <input type="date" class="form-control" id="warrantyDate" name="warranty_expiration">
                                            </div>
                                        </div>
                                    </div>
                        </div>
                    </div>

                            <!-- Second row for assignment & notes -->
                            <div class="col-md-6">
                                <!-- Assignment & Status Section -->
                                            <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Assignment & Status</h6>
                                                        </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="assignedTo" class="form-label">Assigned To</label>
                                                <input type="text" class="form-control" id="assignedTo" name="assigned_to" placeholder="Enter person name">
                                                        </div>
                                            <div class="col-md-6">
                                                <label for="location" class="form-label">Location</label>
                                                <input type="text" class="form-control" id="location" name="location" placeholder="Enter location">
                                                    </div>
                                            <div class="col-md-6">
                                                <label for="condition" class="form-label">Condition</label>
                                                <select class="form-select" id="condition" name="condition">
                                                    <option selected disabled value="">Select condition</option>
                                                    <option value="New">New</option>
                                                    <option value="Good">Good</option>
                                                    <option value="Fair">Fair</option>
                                                    <option value="Poor">Poor</option>
                                                </select>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Notes Section -->
                                            <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Additional Information</h6>
                                                        </div>
                                    <div class="card-body">
                                        <div class="mb-0">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Enter additional notes"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                    </form>
                                                        </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveItemBtn" onclick="saveInventoryItem()">
                        Save Item
                    </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

    <!-- Add Purchase Order Modal -->
    <div class="modal fade" id="addPOModal" tabindex="-1" aria-labelledby="addPOModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addPOModalLabel">Create New Purchase Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                <div class="modal-body py-3">
                    <form id="poForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="poTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="poTrackingData" name="tracking_data">

                        <div class="row g-3">
                            <!-- Left column - Main information -->
                            <div class="col-md-6">
                                <!-- PO Details Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PO Information</h6>
                                                        </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="poNo" class="form-label">PO NO.</label>
                                                <input type="text" class="form-control" id="poNo" name="po_no" placeholder="Enter PO number" required>
                                                        </div>
                                            <div class="col-md-6">
                                                <label for="supplier" class="form-label">SUPPLIER</label>
                                                <input type="text" class="form-control" id="supplier" name="supplier_name" placeholder="Enter supplier name">
                                                    </div>
                                            <div class="col-md-6">
                                                <label for="poDate" class="form-label">DATE</label>
                                                <input type="date" class="form-control" id="poDate" name="po_date">
                                                </div>
                                            <div class="col-md-6">
                                                <label for="refNo" class="form-label">Ref. No.</label>
                                                <input type="text" class="form-control" id="refNo" name="ref_no" placeholder="Enter reference number">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Supplier Information Section -->
                                <div class="card border-0 shadow-sm-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Supplier Information</h6>
                                        </div>
                                        <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="modeOfProcurement" class="form-label">Mode of Procurement</label>
                                                <input type="text" class="form-control" id="modeOfProcurement" name="mode_of_procurement" placeholder="Enter mode of procurement">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="emailAddress" class="form-label">E-Mail Address</label>
                                                <input type="email" class="form-control" id="emailAddress" name="email" placeholder="Enter email address">
                                        </div>
                                            <div class="col-12">
                                                <label for="supplierAddress" class="form-label">Supplier Address</label>
                                                <input type="text" class="form-control" id="supplierAddress" name="supplier_address" placeholder="Enter supplier address">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="telephoneNo" class="form-label">Tel.</label>
                                                <input type="text" class="form-control" id="telephoneNo" name="tel" placeholder="Enter telephone number">
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>

                            <!-- Right column - Additional information -->
                                <div class="col-md-6">
                                <!-- PR Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PR Information</h6>
                                        </div>
                                        <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="prNo" class="form-label">PR No.</label>
                                                <input type="text" class="form-control" id="prNo" name="pr_no" placeholder="Enter PR number">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="prDate" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="prDate" name="pr_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delivery Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Delivery Information</h6>
                                        </div>
                                        <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="placeOfDelivery" class="form-label">Place of Delivery</label>
                                                <input type="text" class="form-control" id="placeOfDelivery" name="place_of_delivery" placeholder="Enter delivery place">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="deliveryDate" class="form-label">Date of Delivery</label>
                                                <input type="date" class="form-control" id="deliveryDate" name="delivery_date">
                                        </div>
                                            <div class="col-md-6">
                                                <label for="paymentTerm" class="form-label">Payment Term</label>
                                                <input type="text" class="form-control" id="paymentTerm" name="payment_term" placeholder="Enter payment term">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="deliveryTerm" class="form-label">Delivery Term</label>
                                                <input type="text" class="form-control" id="deliveryTerm" name="delivery_term" placeholder="Enter delivery term">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Obligation Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Obligation Information</h6>
                                        </div>
                                        <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="obligationRequestNo" class="form-label">Obligation Request No.</label>
                                                <input type="text" class="form-control" id="obligationRequestNo" name="obligation_request_no" placeholder="Enter obligation request number">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="obligationAmount" class="form-label">Obligation Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="text" class="form-control" id="obligationAmount" name="obligation_amount" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                            <!-- Full width - Gentlemen's note and item details -->
                            <div class="col-12">
                                <div class="alert alert-light rounded-3 border mb-3">
                                    <p class="mb-1 fw-medium">Gentlemen:</p>
                                    <p class="mb-0 small">Please furnish this office the following articles subject to the terms and conditions contained herein:</p>
                                    </div>
                                </div>
                                
                            <!-- Item Details Section -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary">Item Details</h6>
                                    </div>
                                    <div class="card-body">
                                    <div class="table-responsive">
                                            <table class="table table-bordered enhanced-po-table" id="poItemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                        <th>Item</th>
                                                        <th>Unit</th>
                                                        <th style="width: 30%;">Description</th>
                                                        <th>QTY</th>
                                                    <th>Unit Cost</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                </tr>
                                            </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm item-name" name="item_name[]" placeholder="Item">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm item-unit" name="unit[]" placeholder="Unit">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control form-control-sm item-description" name="item_description[]" placeholder="Description" rows="2" style="min-height: 60px;"></textarea>
                                                            <span class="truncated-indicator" style="display: none;">more</span>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm qty" name="quantity[]" placeholder="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm unit-cost" name="unit_cost[]" placeholder="0.00" min="0">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm amount" name="amount[]" placeholder="0.00" readonly>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7">
                                                            <button type="button" class="btn btn-sm btn-success" id="addRow">
                                                                <i class="bi bi-plus-circle"></i> Add Item
                                                            </button>
                                                        </td>
                                                </tr>
                                                <tr>
                                                        <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" id="totalAmount" value="₱0.00" readonly>
                                                    </td>
                                                        <td></td>
                                                </tr>
                                                </tfoot>
                                        </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="savePoBtn">
                        Save PO
                    </button>
                </div>
            </div>
                                    </div>
                                </div>
                                
    <!-- Add Property Acknowledgement Receipt Modal -->
    <div class="modal fade" id="addPARModal" tabindex="-1" aria-labelledby="addPARModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-body py-3">
                    <form id="parForm">
                        <!-- Hidden fields for ML tracking -->
                        <input type="hidden" id="parTrackPrediction" name="track_for_prediction" value="true">
                        <input type="hidden" id="parTrackingData" name="tracking_data">
                        <input type="hidden" id="par_id" name="par_id" value="">
                        <input type="hidden" id="received_by_id" name="received_by_id" value="1">
                        <input type="hidden" id="default_user_id" name="default_user_id" value="1">

                        <!-- Rest of the form content -->
                        <div class="row g-3">
                            <!-- Left column -->
                            <div class="col-md-6">
                                <!-- PAR Basic Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">PAR Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="par_no" class="form-label">PAR No.</label>
                                                <input type="text" class="form-control" id="par_no" name="par_no">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="entity_name" class="form-label">Entity Name</label>
                                                <input type="text" class="form-control" id="entity_name" name="entity_name">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="date_acquired" class="form-label">Date Acquired</label>
                                                <input type="date" class="form-control" id="date_acquired" name="date_acquired">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div>
                                            <label for="remarks" class="form-label">Remarks</label>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter remarks (optional)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column -->
                            <div class="col-md-6">
                                <!-- Recipient Information Section -->
                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Recipient Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="received_by" class="form-label">Received by</label>
                                                <input type="text" class="form-control" id="received_by" name="received_by" placeholder="Enter employee name" onchange="document.getElementById('received_by_id').value = '';">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="position" class="form-label">Position</label>
                                                <input type="text" class="form-control" id="position" name="position" placeholder="Enter position">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="department" class="form-label">Department</label>
                                                <input type="text" class="form-control" id="department" name="department" placeholder="Enter department">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Details Section - Full width -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-primary">Item Details</h6>
                                    </div>
                                    <div class="card-body">
                                    <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover align-middle shadow-sm" id="parItemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                        <th>QTY</th>
                                                        <th>Unit</th>
                                                        <th>Description</th>
                                                        <th>Property Number</th>
                                                        <th>Date Acquired</th>
                                                        <th>Amount</th>
                                                        <th>Action</th>
                                                </tr>
                                            </thead>
                                                <tbody>
                                                    <!-- Table rows will be dynamically added by JavaScript -->
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="7">
                                                            <button type="button" class="btn btn-sm btn-success" id="addParRowBtn">
                                                                <i class="bi bi-plus-circle"></i> Add Item
                                                            </button>
                                                    </td>
                                                </tr>
                                                    <tr>
                                                        <td colspan="5" class="text-end fw-bold">Total Amount:</td>
                                                        <td class="fw-bold">
                                                            <span id="parTotal">0.00</span>
                                                            <input type="hidden" id="parTotalAmount" name="total_amount" value="0.00">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                        </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveParBtn">
                        Save PAR
                    </button>
                </div>
            </div>
                                    </div>
                                </div>
                                
    <!-- View Purchase Order Modal -->
    <div class="modal fade" id="viewPOModal" tabindex="-1" aria-labelledby="viewPOModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPOModalLabel"><i class="bi bi-eye"></i> View Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="poLoading" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading purchase order details...</p>
                    </div>
                    <div id="poContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPO()">Print</button>
                </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
        <script src="updateURL.js"></script>
        <script src="admindashboard.js"></script>
        <script src="par.js"></script>
        <script src="amindinventory.js"></script>
        <script src="dashboard_analytics.js"></script>
        <script src="dashboard_ml_prediction.js"></script>
        <script src="inventory_ml_tracking.js"></script>
        <script src="user_dashboard_client.js"></script>
        <script src="user_management.js"></script>
        <script src="user_navigation.js"></script>
        <script src="reports.js"></script>
        <script src="settings.js"></script>

     


                           

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="user_id" value="">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="userRole" class="form-label">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield"></i></span>
                                    <select class="form-select" id="userRole" name="role">
                                        <option value="admin">Administrator</option>
                                        <option value="manager">Manager</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-toggle-on"></i></span>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">
                        Save User
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>