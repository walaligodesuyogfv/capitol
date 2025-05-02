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
    
    // Check if the table exists
    $tableExists = false;
    $checkTableSql = "SHOW TABLES LIKE 'maintenance_predictions'";
    $stmt = $conn->prepare($checkTableSql);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $tableExists = true;
    }
    
    // If table doesn't exist, run Python predictor script if available
    if (!$tableExists && file_exists('DataAnalytics/inventory_predictor.py')) {
        $pythonPath = 'python'; // Change this to the path of your Python executable if needed
        $command = "$pythonPath DataAnalytics/inventory_predictor.py";
        $output = [];
        $return_var = 0;
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception("Failed to run inventory predictor script");
        }
        
        // Check again if the table exists
        $stmt = $conn->prepare($checkTableSql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $tableExists = true;
        }
    }
    
    if ($tableExists) {
        // Get maintenance predictions
        $sql = "SELECT * FROM maintenance_predictions ORDER BY priority DESC, confidence DESC";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to fetch maintenance predictions");
        }
        
        $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return data
        echo json_encode([
            'success' => true,
            'count' => count($predictions),
            'predictions' => $predictions
        ]);
    } else {
        // Generate dummy data if table doesn't exist or no data
        $dummyPredictions = [];
        
        // Get some inventory items to create plausible predictions
        $inventorySql = "SELECT * FROM inventory LIMIT 5";
        $stmt = $conn->prepare($inventorySql);
        
        if ($stmt->execute()) {
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($inventory as $item) {
                $condition = $item['condition'] ?? 'Good';
                if ($condition == 'Poor' || $condition == 'Fair') {
                    $confidence = mt_rand(70, 95) / 100;
                    $days = mt_rand(10, 60);
                    $dummyPredictions[] = [
                        'item_id' => $item['item_id'] ?? '',
                        'item_name' => $item['item_name'] ?? 'Unknown',
                        'serial_number' => $item['serial_number'] ?? '',
                        'current_condition' => $condition,
                        'maintenance_needed' => true,
                        'confidence' => $confidence,
                        'estimated_days' => $days,
                        'priority' => ($condition == 'Poor') ? 'High' : 'Medium'
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($dummyPredictions),
            'predictions' => $dummyPredictions,
            'note' => 'Dummy data generated as no predictions table exists'
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 