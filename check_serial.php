<?php
// Include database connection
require_once 'config/db.php';

// Set response headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Get parameters from request
$serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// Validate input
if (empty($serial)) {
    echo json_encode([
        'success' => false,
        'message' => 'Serial number is required',
        'exists' => false
    ]);
    exit;
}

try {
    // Get database connection
    $conn = getConnection();

    // Check if serial number exists, excluding the current item (for edit mode)
    $sql = "SELECT COUNT(*) as count FROM inventory_items WHERE serial_number = ?";
    $params = [$serial];
    $types = "s";
    
    // If item_id is provided (edit mode), exclude it from the check
    if ($item_id > 0) {
        $sql .= " AND item_id != ?";
        $params[] = $item_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    // Bind parameters dynamically based on the number of parameters
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Return success response with exists flag
    echo json_encode([
        'success' => true,
        'exists' => ($row['count'] > 0)
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'exists' => false
    ]);
} finally {
    // Close database connection
    if (isset($conn) && !$conn->connect_error) {
        $conn->close();
    }
}
?> 