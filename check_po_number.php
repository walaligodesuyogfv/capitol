<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Initialize response
$response = ['success' => true, 'exists' => false, 'message' => ''];

try {
    // Include database configuration
    require_once 'config/db.php';
    
    // Check if PO number is provided
    if (!isset($_GET['po_no']) || empty($_GET['po_no'])) {
        throw new Exception('No PO number provided');
    }
    
    $poNo = trim($_GET['po_no']);
    
    // Get optional item_id for edit mode (to exclude the current PO)
    $poId = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
    
    // Check if database connection is available
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Prepare query - if po_id is provided, exclude that PO from the check
    if ($poId > 0) {
        $query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_no = ? AND po_id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $poNo, $poId);
    } else {
        $query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_no = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $poNo);
    }
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Check if PO exists
    $exists = ($row['count'] > 0);
    
    // Return the result
    $response['exists'] = $exists;
    if ($exists) {
        $response['message'] = "PO number '$poNo' already exists in the database";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Return error response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log the error
    error_log("Error checking PO number: " . $e->getMessage());
}

// Close the database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
exit;
?> 