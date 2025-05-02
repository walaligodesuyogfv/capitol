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
    $checkTableSql = "SHOW TABLES LIKE 'inventory_suggestions'";
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
        // Get inventory suggestions
        $sql = "SELECT * FROM inventory_suggestions ORDER BY priority DESC, confidence DESC";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to fetch inventory suggestions");
        }
        
        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Return data
        echo json_encode([
            'success' => true,
            'count' => count($suggestions),
            'suggestions' => $suggestions
        ]);
    } else {
        // Generate dummy data if table doesn't exist or no data
        $dummySuggestions = [];
        
        // Group inventory items by category to create plausible suggestions
        $sql = "SELECT 
                    SUBSTRING_INDEX(item_name, ' ', 1) AS category,
                    COUNT(*) AS item_count,
                    AVG(CASE 
                        WHEN `condition` = 'New' THEN 4
                        WHEN `condition` = 'Good' THEN 3
                        WHEN `condition` = 'Fair' THEN 2
                        WHEN `condition` = 'Poor' THEN 1
                        ELSE 3
                    END) AS avg_condition
                FROM inventory
                GROUP BY SUBSTRING_INDEX(item_name, ' ', 1)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute()) {
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categories as $category) {
                $itemCount = $category['item_count'];
                $avgCondition = $category['avg_condition'];
                
                if ($itemCount < 3 || $avgCondition < 2.5) {
                    $confidence = mt_rand(70, 90) / 100;
                    $suggestedCount = max(5, $itemCount + 2);
                    $reason = ($itemCount < 3) ? 'Low inventory count' : 'Items need replacement';
                    $priority = ($itemCount < 2 || $avgCondition < 2) ? 'High' : 'Medium';
                    
                    $dummySuggestions[] = [
                        'category' => ucfirst($category['category']),
                        'current_count' => $itemCount,
                        'suggested_count' => $suggestedCount,
                        'confidence' => $confidence,
                        'reason' => $reason,
                        'priority' => $priority
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($dummySuggestions),
            'suggestions' => $dummySuggestions,
            'note' => 'Dummy data generated as no suggestions table exists'
        ]);
    }
    
} catch (Exception $e) {
    // Handle errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 