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
    
    // Get raw POST data and decode JSON
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception("Invalid or empty JSON data");
    }
    
    // Check if we need to create the inventory_suggestions table
    $tableExists = false;
    $checkTableSql = "SHOW TABLES LIKE 'inventory_suggestions'";
    $stmt = $conn->prepare($checkTableSql);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
    }
    
    // Create the table if it doesn't exist
    if (!$tableExists) {
        $createTableSql = "CREATE TABLE inventory_suggestions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(100),
            current_count INT,
            suggested_count INT,
            confidence FLOAT,
            reason VARCHAR(255),
            priority VARCHAR(50),
            suggestion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $stmt = $conn->prepare($createTableSql);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create inventory_suggestions table");
        }
    }
    
    // Clear previous suggestions
    $clearSql = "TRUNCATE TABLE inventory_suggestions";
    $stmt = $conn->prepare($clearSql);
    if (!$stmt->execute()) {
        throw new Exception("Failed to clear previous suggestions");
    }
    
    // Insert new suggestions
    if (isset($data['suggestions']) && is_array($data['suggestions'])) {
        $insertSql = "INSERT INTO inventory_suggestions 
                      (category, current_count, suggested_count, confidence, reason, priority) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertSql);
        
        $insertCount = 0;
        foreach ($data['suggestions'] as $suggestion) {
            $stmt->execute([
                $suggestion['category'] ?? 'Unknown',
                $suggestion['current_count'] ?? 0,
                $suggestion['suggested_count'] ?? 0,
                $suggestion['confidence'] ?? 0,
                $suggestion['reason'] ?? '',
                $suggestion['priority'] ?? 'Low'
            ]);
            $insertCount++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully saved $insertCount inventory suggestions",
            'count' => $insertCount
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No suggestions to save',
            'count' => 0
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 