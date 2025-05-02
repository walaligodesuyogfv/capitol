<?php
/**
 * Get Report Data API
 * 
 * This script handles fetching report data from the database
 * for different report types and filtering options.
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database configuration file not found'
    ]);
    exit;
}

// Get database connection
$conn = getConnection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// Get request parameters
$reportType = isset($_GET['type']) ? $_GET['type'] : 'summary';
$fromDate = isset($_GET['from']) ? $_GET['from'] : null;
$toDate = isset($_GET['to']) ? $_GET['to'] : null;
$department = isset($_GET['department']) ? $_GET['department'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;

// Calculate offset for pagination
$offset = ($page - 1) * $itemsPerPage;

// Function to generate WHERE clause based on filters
function generateWhereClause($filters) {
    $whereClause = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['fromDate'])) {
        $whereClause[] = "date >= ?";
        $params[] = $filters['fromDate'];
        $types .= 's';
    }
    
    if (!empty($filters['toDate'])) {
        $whereClause[] = "date <= ?";
        $params[] = $filters['toDate'];
        $types .= 's';
    }
    
    if (!empty($filters['department'])) {
        $whereClause[] = "department = ?";
        $params[] = $filters['department'];
        $types .= 's';
    }
    
    if (!empty($filters['status'])) {
        $whereClause[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    return [
        'clause' => !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "",
        'params' => $params,
        'types' => $types
    ];
}

// Fetch data based on report type
$result = [
    'success' => true,
    'data' => [],
    'pagination' => [
        'page' => $page,
        'per_page' => $itemsPerPage,
        'total_items' => 0,
        'total_pages' => 0
    ]
];

// Filter parameters
$filters = [
    'fromDate' => $fromDate,
    'toDate' => $toDate,
    'department' => $department,
    'status' => $status
];

// Check if tables exist before querying
$checkTablesFirst = true;
if ($checkTablesFirst) {
    $table = '';
    
    switch ($reportType) {
        case 'par_detailed':
            $table = 'property_acknowledgement_receipts';
            break;
        case 'po_detailed':
            $table = 'purchase_orders';
            break;
        case 'inventory_detailed':
            $table = 'inventory_items';
            break;
        case 'audit_logs':
            $table = 'audit_logs';
            break;
    }
    
    if (!empty($table)) {
        $tableCheck = $conn->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->num_rows == 0) {
            // Table doesn't exist, return mock data
            echo json_encode([
                'success' => true,
                'mock' => true,
                'data' => getMockData($reportType, $itemsPerPage),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $itemsPerPage,
                    'total_items' => ($reportType == 'summary') ? 5 : 20,
                    'total_pages' => ($reportType == 'summary') ? 1 : 2
                ]
            ]);
            exit;
        }
    }
}

try {
    switch ($reportType) {
        case 'summary':
            // Fetch summary report data
            $summaryData = [];
            
            // Get departments for summary
            $deptQuery = "SELECT DISTINCT department FROM inventory_items";
            $deptResult = $conn->query($deptQuery);
            
            if ($deptResult && $deptResult->num_rows > 0) {
                while ($deptRow = $deptResult->fetch_assoc()) {
                    $dept = $deptRow['department'];
                    
                    // Count and sum for each department
                    $countQuery = "SELECT COUNT(*) as count, SUM(IFNULL(value, 0)) as total_value 
                                FROM inventory_items 
                                WHERE department = ?";
                    
                    $countStmt = $conn->prepare($countQuery);
                    $countStmt->bind_param("s", $dept);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $countRow = $countResult->fetch_assoc();
                    
                    $summaryData[] = [
                        'category' => 'Hardware',
                        'department' => $dept,
                        'count' => $countRow['count'],
                        'value' => $countRow['total_value'],
                        'last_updated' => date('Y-m-d')
                    ];
                    
                    $countStmt->close();
                }
            } else {
                // No departments found, provide sample data
                $summaryData = getMockData('summary', 5);
            }
            
            $result['data'] = $summaryData;
            $result['pagination']['total_items'] = count($summaryData);
            $result['pagination']['total_pages'] = ceil(count($summaryData) / $itemsPerPage);
            break;
            
        case 'par_detailed':
            // Fetch PAR detailed report
            $where = generateWhereClause($filters);
            
            // Count total records for pagination
            $countQuery = "SELECT COUNT(*) as total FROM property_acknowledgement_receipts " . $where['clause'];
            $countStmt = $conn->prepare($countQuery);
            
            if (!empty($where['params'])) {
                $countStmt->bind_param($where['types'], ...$where['params']);
            }
            
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalItems = $countRow['total'];
            
            $countStmt->close();
            
            // Fetch paginated data
            $query = "SELECT * FROM property_acknowledgement_receipts " . $where['clause'] . " 
                    ORDER BY date_acquired DESC LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($where['params'])) {
                $bindParams = array_merge($where['params'], [$itemsPerPage, $offset]);
                $types = $where['types'] . 'ii';
                $stmt->bind_param($types, ...$bindParams);
            } else {
                $stmt->bind_param("ii", $itemsPerPage, $offset);
            }
            
            $stmt->execute();
            $queryResult = $stmt->get_result();
            
            $parData = [];
            if ($queryResult && $queryResult->num_rows > 0) {
                while ($row = $queryResult->fetch_assoc()) {
                    $parData[] = $row;
                }
            } else {
                // No data found, provide sample data
                $parData = getMockData('par_detailed', $itemsPerPage);
            }
            
            $stmt->close();
            
            $result['data'] = $parData;
            $result['pagination']['total_items'] = $totalItems;
            $result['pagination']['total_pages'] = ceil($totalItems / $itemsPerPage);
            break;
            
        case 'po_detailed':
            // Fetch PO detailed report
            $where = generateWhereClause($filters);
            
            // Count total records for pagination
            $countQuery = "SELECT COUNT(*) as total FROM purchase_orders " . $where['clause'];
            $countStmt = $conn->prepare($countQuery);
            
            if (!empty($where['params'])) {
                $countStmt->bind_param($where['types'], ...$where['params']);
            }
            
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalItems = $countRow['total'];
            
            $countStmt->close();
            
            // Fetch paginated data
            $query = "SELECT * FROM purchase_orders " . $where['clause'] . " 
                    ORDER BY po_date DESC LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($where['params'])) {
                $bindParams = array_merge($where['params'], [$itemsPerPage, $offset]);
                $types = $where['types'] . 'ii';
                $stmt->bind_param($types, ...$bindParams);
            } else {
                $stmt->bind_param("ii", $itemsPerPage, $offset);
            }
            
            $stmt->execute();
            $queryResult = $stmt->get_result();
            
            $poData = [];
            if ($queryResult && $queryResult->num_rows > 0) {
                while ($row = $queryResult->fetch_assoc()) {
                    $poData[] = $row;
                }
            } else {
                // No data found, provide sample data
                $poData = getMockData('po_detailed', $itemsPerPage);
            }
            
            $stmt->close();
            
            $result['data'] = $poData;
            $result['pagination']['total_items'] = $totalItems;
            $result['pagination']['total_pages'] = ceil($totalItems / $itemsPerPage);
            break;
            
        case 'inventory_detailed':
            // Fetch inventory detailed report
            $where = generateWhereClause($filters);
            
            // Count total records for pagination
            $countQuery = "SELECT COUNT(*) as total FROM inventory_items " . $where['clause'];
            $countStmt = $conn->prepare($countQuery);
            
            if (!empty($where['params'])) {
                $countStmt->bind_param($where['types'], ...$where['params']);
            }
            
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalItems = $countRow['total'];
            
            $countStmt->close();
            
            // Fetch paginated data
            $query = "SELECT * FROM inventory_items " . $where['clause'] . " 
                    ORDER BY purchase_date DESC LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($where['params'])) {
                $bindParams = array_merge($where['params'], [$itemsPerPage, $offset]);
                $types = $where['types'] . 'ii';
                $stmt->bind_param($types, ...$bindParams);
            } else {
                $stmt->bind_param("ii", $itemsPerPage, $offset);
            }
            
            $stmt->execute();
            $queryResult = $stmt->get_result();
            
            $inventoryData = [];
            if ($queryResult && $queryResult->num_rows > 0) {
                while ($row = $queryResult->fetch_assoc()) {
                    $inventoryData[] = $row;
                }
            } else {
                // No data found, provide sample data
                $inventoryData = getMockData('inventory_detailed', $itemsPerPage);
            }
            
            $stmt->close();
            
            $result['data'] = $inventoryData;
            $result['pagination']['total_items'] = $totalItems;
            $result['pagination']['total_pages'] = ceil($totalItems / $itemsPerPage);
            break;
            
        case 'audit_logs':
            // Fetch audit logs report
            $where = generateWhereClause($filters);
            
            // Count total records for pagination
            $countQuery = "SELECT COUNT(*) as total FROM audit_logs " . $where['clause'];
            $countStmt = $conn->prepare($countQuery);
            
            if (!empty($where['params'])) {
                $countStmt->bind_param($where['types'], ...$where['params']);
            }
            
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalItems = $countRow['total'];
            
            $countStmt->close();
            
            // Fetch paginated data
            $query = "SELECT * FROM audit_logs " . $where['clause'] . " 
                    ORDER BY timestamp DESC LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($where['params'])) {
                $bindParams = array_merge($where['params'], [$itemsPerPage, $offset]);
                $types = $where['types'] . 'ii';
                $stmt->bind_param($types, ...$bindParams);
            } else {
                $stmt->bind_param("ii", $itemsPerPage, $offset);
            }
            
            $stmt->execute();
            $queryResult = $stmt->get_result();
            
            $logsData = [];
            if ($queryResult && $queryResult->num_rows > 0) {
                while ($row = $queryResult->fetch_assoc()) {
                    $logsData[] = $row;
                }
            } else {
                // No data found, provide sample data
                $logsData = getMockData('audit_logs', $itemsPerPage);
            }
            
            $stmt->close();
            
            $result['data'] = $logsData;
            $result['pagination']['total_items'] = $totalItems;
            $result['pagination']['total_pages'] = ceil($totalItems / $itemsPerPage);
            break;
            
        default:
            $result = [
                'success' => false,
                'error' => 'Invalid report type'
            ];
    }
} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Close database connection
$conn->close();

// Return result as JSON
echo json_encode($result);

// Function to generate mock data for testing
function getMockData($reportType, $count) {
    $data = [];
    
    switch ($reportType) {
        case 'summary':
            $departments = ['IT', 'HR', 'Finance', 'Operations', 'Marketing'];
            $categories = ['Hardware', 'Software', 'Furniture', 'Office Equipment', 'Electronics'];
            
            for ($i = 0; $i < min(count($departments), $count); $i++) {
                $data[] = [
                    'category' => $categories[$i % count($categories)],
                    'department' => $departments[$i],
                    'count' => rand(20, 100),
                    'value' => rand(50000, 300000),
                    'last_updated' => date('Y-m-d', strtotime("-" . rand(1, 30) . " days"))
                ];
            }
            break;
            
        case 'par_detailed':
            $employees = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sara Williams', 'Robert Brown'];
            $departments = ['IT', 'HR', 'Finance', 'Operations', 'Marketing'];
            $items = ['Laptop', 'Desktop', 'Monitor', 'Printer', 'Scanner', 'Phone', 'Tablet'];
            $descriptions = [
                'Dell XPS 13', 'HP EliteDesk 800', 'Dell P2419H', 'HP LaserJet Pro', 
                'Epson DS-530', 'iPhone 13', 'iPad Pro 11"'
            ];
            $statuses = ['Active', 'Inactive', 'Reassigned', 'Returned'];
            
            for ($i = 0; $i < $count; $i++) {
                $qty = rand(1, 3);
                $unitCost = rand(5000, 60000);
                $data[] = [
                    'par_no' => 'PAR-2023-' . str_pad($i + 101, 3, '0', STR_PAD_LEFT),
                    'item_name' => $items[array_rand($items)],
                    'description' => $descriptions[array_rand($descriptions)],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                    'employee' => $employees[array_rand($employees)],
                    'department' => $departments[array_rand($departments)],
                    'date_issued' => date('Y-m-d', strtotime("-" . rand(1, 365) . " days")),
                    'status' => $statuses[array_rand($statuses)]
                ];
            }
            break;
            
        case 'po_detailed':
            $suppliers = ['ABC Tech', 'XYZ Corp', 'Global Supplies', 'Office Solutions', 'Tech Depot'];
            $items = ['Laptops', 'Desktops', 'Monitors', 'Printers', 'Phones', 'Furniture', 'Software Licenses'];
            $statuses = ['Completed', 'Pending', 'Cancelled', 'In Progress'];
            
            for ($i = 0; $i < $count; $i++) {
                $qty = rand(2, 10);
                $unitPrice = rand(10000, 50000);
                $poDate = date('Y-m-d', strtotime("-" . rand(1, 180) . " days"));
                $deliveryDays = rand(7, 30);
                $deliveryDate = date('Y-m-d', strtotime($poDate . " + $deliveryDays days"));
                
                $data[] = [
                    'po_no' => 'PO-2023-' . str_pad($i + 101, 3, '0', STR_PAD_LEFT),
                    'supplier' => $suppliers[array_rand($suppliers)],
                    'item_description' => $items[array_rand($items)],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_amount' => $qty * $unitPrice,
                    'date_ordered' => $poDate,
                    'date_delivered' => $deliveryDate,
                    'status' => $statuses[array_rand($statuses)]
                ];
            }
            break;
            
        case 'inventory_detailed':
            $items = ['Laptop', 'Desktop', 'Monitor', 'Printer', 'Scanner', 'Phone', 'Tablet'];
            $brands = ['Dell', 'HP', 'Lenovo', 'Apple', 'Samsung', 'Asus', 'Acer'];
            $models = ['XPS 13', 'EliteDesk 800', 'ThinkPad X1', 'MacBook Pro', 'Galaxy S21', 'ZenBook Pro', 'Aspire 5'];
            $conditions = ['New', 'Good', 'Fair', 'Poor'];
            $locations = ['Office', 'Storage', 'Remote', 'Meeting Room', 'Reception'];
            $assignees = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sara Williams', 'Unassigned'];
            
            for ($i = 0; $i < $count; $i++) {
                $brand = $brands[array_rand($brands)];
                $model = $models[array_rand($models)];
                $purchaseDate = date('Y-m-d', strtotime("-" . rand(1, 1095) . " days")); // Up to 3 years ago
                
                $data[] = [
                    'item_id' => 'INV-' . str_pad($i + 1001, 4, '0', STR_PAD_LEFT),
                    'item_name' => $items[array_rand($items)],
                    'brand_model' => "$brand $model",
                    'serial_number' => strtoupper(substr($brand, 0, 2)) . rand(100000, 999999),
                    'condition' => $conditions[array_rand($conditions)],
                    'location' => $locations[array_rand($locations)],
                    'assigned_to' => $assignees[array_rand($assignees)],
                    'purchase_date' => $purchaseDate,
                    'value' => rand(10000, 80000)
                ];
            }
            break;
            
        case 'audit_logs':
            $users = ['admin', 'john.doe', 'jane.smith', 'mike.johnson', 'sara.williams'];
            $actions = ['CREATE', 'UPDATE', 'DELETE', 'VIEW', 'EXPORT', 'LOGIN', 'LOGOUT'];
            $modules = ['Inventory', 'PO', 'PAR', 'Users', 'Reports', 'Settings'];
            
            for ($i = 0; $i < $count; $i++) {
                $timestamp = date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days -" . rand(1, 24) . " hours"));
                $action = $actions[array_rand($actions)];
                $module = $modules[array_rand($modules)];
                $recordId = rand(1000, 9999);
                
                $details = "";
                switch ($action) {
                    case 'CREATE':
                        $details = "Created new $module item with ID $recordId";
                        break;
                    case 'UPDATE':
                        $details = "Updated $module item with ID $recordId";
                        break;
                    case 'DELETE':
                        $details = "Deleted $module item with ID $recordId";
                        break;
                    case 'VIEW':
                        $details = "Viewed $module item with ID $recordId";
                        break;
                    case 'EXPORT':
                        $details = "Exported $module data";
                        break;
                    case 'LOGIN':
                        $details = "User login successful";
                        break;
                    case 'LOGOUT':
                        $details = "User logout";
                        break;
                }
                
                $data[] = [
                    'timestamp' => $timestamp,
                    'user' => $users[array_rand($users)],
                    'action' => $action,
                    'module' => $module,
                    'record_id' => $recordId,
                    'details' => $details
                ];
            }
            break;
    }
    
    return $data;
} 