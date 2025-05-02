<?php
// Allow cross-domain requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// If this is an OPTIONS request, just return the headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database configuration not found']);
    exit;
}

try {
    // Get database connection
    $conn = getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
    
    // Prepare the SQL query to get all inventory items
    $sql = "SELECT * FROM inventory";
    $stmt = $conn->prepare($sql);
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query");
    }
    
    // Fetch the results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the data as JSON
    echo json_encode($results);
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 