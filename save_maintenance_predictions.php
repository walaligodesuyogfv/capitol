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
    
    // Check if we need to create the maintenance_predictions table
    $tableExists = false;
    $checkTableSql = "SHOW TABLES LIKE 'maintenance_predictions'";
    $stmt = $conn->prepare($checkTableSql);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
    }
    
    // Create the table if it doesn't exist
    if (!$tableExists) {
        $createTableSql = "CREATE TABLE maintenance_predictions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id VARCHAR(50),
            item_name VARCHAR(255),
            serial_number VARCHAR(100),
            current_condition VARCHAR(50),
            maintenance_needed BOOLEAN,
            confidence FLOAT,
            estimated_days INT,
            priority VARCHAR(50),
            prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $stmt = $conn->prepare($createTableSql);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create maintenance_predictions table");
        }
    }
    
    // Clear previous predictions
    $clearSql = "TRUNCATE TABLE maintenance_predictions";
    $stmt = $conn->prepare($clearSql);
    if (!$stmt->execute()) {
        throw new Exception("Failed to clear previous predictions");
    }
    
    // Insert new predictions
    if (isset($data['predictions']) && is_array($data['predictions'])) {
        $insertSql = "INSERT INTO maintenance_predictions 
                      (item_id, item_name, serial_number, current_condition, maintenance_needed, 
                       confidence, estimated_days, priority) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertSql);
        
        $insertCount = 0;
        foreach ($data['predictions'] as $prediction) {
            $stmt->execute([
                $prediction['item_id'] ?? '',
                $prediction['item_name'] ?? 'Unknown',
                $prediction['serial_number'] ?? '',
                $prediction['current_condition'] ?? 'Unknown',
                $prediction['maintenance_needed'] ?? false,
                $prediction['confidence'] ?? 0,
                $prediction['estimated_days'] ?? 0,
                $prediction['priority'] ?? 'Low'
            ]);
            $insertCount++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully saved $insertCount maintenance predictions",
            'count' => $insertCount
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No predictions to save',
            'count' => 0
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 