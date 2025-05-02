<?php
/**
 * Fetch ML Prediction Data
 * This script retrieves data for the Maintenance Predictions and Inventory Suggestions sections
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration not found',
        'needs_attention' => [],
        'recommendations' => [],
        'top_items' => []
    ]);
    exit;
}

$conn = getConnection();

// Check if connection successful
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'needs_attention' => [],
        'recommendations' => [],
        'top_items' => []
    ]);
    exit;
}

// Get the data
try {
    $result = [
        'success' => true,
        'needs_attention' => getMaintenancePredictions($conn),
        'recommendations' => getGeneralRecommendations($conn),
        'top_items' => getTopItems($conn)
    ];
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'needs_attention' => [],
        'recommendations' => [],
        'top_items' => []
    ]);
}

$conn->close();

/**
 * Get maintenance predictions from the database
 */
function getMaintenancePredictions($conn) {
    $predictions = [];
    
    // Check if the maintenance_predictions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'maintenance_predictions'");
    if ($tableCheck->num_rows == 0) {
        return $predictions;
    }
    
    // Get data from maintenance_predictions table
    $query = "SELECT * FROM maintenance_predictions ORDER BY priority DESC, created_at DESC LIMIT 10";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $predictions[] = [
                'id' => $row['item_id'],
                'item_name' => $row['item_name'],
                'reason' => $row['reason'],
                'priority' => $row['priority']
            ];
        }
    }
    
    return $predictions;
}

/**
 * Get inventory suggestions from the database
 */
function getTopItems($conn) {
    $items = [];
    
    // Check if the inventory_suggestions table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'inventory_suggestions'");
    if ($tableCheck->num_rows == 0) {
        return $items;
    }
    
    // Get data from inventory_suggestions table
    $query = "SELECT * FROM inventory_suggestions ORDER BY score DESC, created_at DESC LIMIT 5";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => $row['item_id'],
                'item_name' => $row['item_name'],
                'reason' => $row['reason'],
                'score' => $row['score']
            ];
        }
    }
    
    return $items;
}

/**
 * Get general recommendations
 */
function getGeneralRecommendations($conn) {
    $recommendations = [];
    
    // Get data from inventory table for general recommendations
    $query = "SELECT COUNT(*) as total, SUM(CASE WHEN item_condition = 'Poor' THEN 1 ELSE 0 END) as poor_condition FROM inventory";
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        $total = $row['total'];
        $poorCondition = $row['poor_condition'];
        
        // Add recommendations based on inventory state
        if ($total > 0) {
            if ($poorCondition > 0) {
                $recommendations[] = "You have $poorCondition items in Poor condition that may need maintenance or replacement.";
            }
            
            // Get items with expired warranties
            $warrantyQuery = "SELECT COUNT(*) as expired FROM inventory WHERE warranty_expiration < CURDATE() AND warranty_expiration != '0000-00-00'";
            $warrantyResult = $conn->query($warrantyQuery);
            
            if ($warrantyResult && $warrantyRow = $warrantyResult->fetch_assoc()) {
                $expiredCount = $warrantyRow['expired'];
                
                if ($expiredCount > 0) {
                    $recommendations[] = "You have $expiredCount items with expired warranties that may need renewal or replacement.";
                }
            }
        }
        
        // Default recommendations if nothing specific
        if (empty($recommendations)) {
            $recommendations[] = "Your inventory is in good condition. Continue regular maintenance.";
            $recommendations[] = "Consider documenting usage patterns to help predict future maintenance needs.";
        }
    }
    
    // Add some general recommendations
    $recommendations[] = "Regularly check items in Fair or Poor condition to prevent further deterioration.";
    $recommendations[] = "Keep record of maintenance activities for improved prediction accuracy.";
    
    return $recommendations;
} 