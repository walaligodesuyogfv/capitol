<?php
// Check if PO number already exists in database
require_once 'config/db.php';

// Set response headers
header('Content-Type: application/json');

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    // Validate required parameters
    if (!isset($_GET['po_no']) || empty($_GET['po_no'])) {
        throw new Exception("PO number is required");
    }
    
    // Get the PO number from the request
    $poNo = trim($_GET['po_no']);
    
    // Get optional item_id for edit mode (to exclude the current PO)
    $poId = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
    
    // Check if database connection is available
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : 'Connection not established'));
    }
    
    // Log the check operation
    error_log("Checking if PO number '{$poNo}' exists" . ($poId > 0 ? " (excluding PO ID: {$poId})" : ""));
    
    // Prepare query - if po_id is provided, exclude that PO from the check
    if ($poId > 0) {
        $query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_no = ? AND po_id != ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("si", $poNo, $poId);
    } else {
        $query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_no = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("s", $poNo);
    }
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Check if PO exists
    $exists = ($row['count'] > 0);
    
    // Log the result
    error_log("PO number '{$poNo}' " . ($exists ? "exists" : "does not exist"));
    
    // Return the result
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'po_no' => $poNo
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'exists' => false // Default to false on error
    ]);
    
    // Log the error
    error_log("Error checking PO number: " . $e->getMessage());
}

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?> 