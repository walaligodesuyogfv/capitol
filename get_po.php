<?php
// Database connection
require_once 'config/db.php';

// Enable error logging but disable HTML output of errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
error_log("Get PO endpoint called: " . date('Y-m-d H:i:s'));

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Validate database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : 'Connection not established'));
    }
    
    // Get pagination parameters or use defaults
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    
    // Ensure valid pagination parameters
    if ($page < 1) $page = 1;
    if ($perPage < 1 || $perPage > 100) $perPage = 10;
    
    // Calculate offset for pagination
    $offset = ($page - 1) * $perPage;
    
    // Check if purchase_orders table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'purchase_orders'");
    if ($tableCheck->num_rows === 0) {
        // Return empty result if table doesn't exist
        echo json_encode([
            'success' => true,
            'data' => [],
            'total' => 0,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0,
            'message' => 'No purchase orders table found'
        ]);
        exit;
    }
    
    // Get total count of POs
    $countQuery = "SELECT COUNT(*) as total FROM purchase_orders";
    $countResult = $conn->query($countQuery);
    
    if (!$countResult) {
        throw new Exception("Error counting POs: " . $conn->error);
    }
    
    $countRow = $countResult->fetch_assoc();
    $total = $countRow['total'];
    $totalPages = ceil($total / $perPage);
    
    // If no results, return empty array
    if ($total === 0) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'total' => 0,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0,
            'message' => 'No purchase orders found'
        ]);
        exit;
    }
    
    // Query to get POs with pagination
    $query = "SELECT po.*, 
                (SELECT COUNT(*) FROM po_items WHERE po_id = po.po_id) as item_count
              FROM purchase_orders po
              ORDER BY po.po_date DESC, po.po_id DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing PO query: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $perPage, $offset);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing PO query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $pos = [];
    
    // Fetch all POs
    while ($row = $result->fetch_assoc()) {
        // Format dates for better client-side use
        if (!empty($row['po_date'])) {
            $row['po_date_formatted'] = date('m/d/Y', strtotime($row['po_date']));
        }
        if (!empty($row['pr_date'])) {
            $row['pr_date_formatted'] = date('m/d/Y', strtotime($row['pr_date']));
        }
        if (!empty($row['delivery_date'])) {
            $row['delivery_date_formatted'] = date('m/d/Y', strtotime($row['delivery_date']));
        }
        
        $pos[] = $row;
    }
    
    $stmt->close();
    
    // Return results
    echo json_encode([
        'success' => true,
        'data' => $pos,
        'total' => $total,
        'current_page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages
    ]);
    
} catch (Exception $e) {
    error_log("Error getting PO data: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error getting PO data: ' . $e->getMessage()
    ]);
}

// Close connection
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}

?>