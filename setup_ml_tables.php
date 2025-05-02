<?php
/**
 * Setup ML Tables Script
 * Creates the necessary database tables for ML prediction functionality
 */

if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    die("Database configuration file not found");
}

// Get database connection
$conn = getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Success and error messages
$messages = [];

// Create item_views table
$createItemViewsSQL = "
CREATE TABLE IF NOT EXISTS `item_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `view_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create item_usage table
$createItemUsageSQL = "
CREATE TABLE IF NOT EXISTS `item_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `usage_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action_type` varchar(50) NOT NULL,
  `details` text,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create ml_prediction_log table
$createMlPredictionLogSQL = "
CREATE TABLE IF NOT EXISTS `ml_prediction_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prediction_type` varchar(50) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `prediction_data` text NOT NULL,
  `prediction_score` float DEFAULT NULL,
  `prediction_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `prediction_type` (`prediction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create item_condition_history table
$createItemConditionHistorySQL = "
CREATE TABLE IF NOT EXISTS `item_condition_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `previous_condition` varchar(50) DEFAULT NULL,
  `new_condition` varchar(50) NOT NULL,
  `change_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute queries
try {
    $conn->query($createItemViewsSQL);
    $messages[] = "Item views table created successfully";
    
    $conn->query($createItemUsageSQL);
    $messages[] = "Item usage table created successfully";
    
    $conn->query($createMlPredictionLogSQL);
    $messages[] = "ML prediction log table created successfully";
    
    $conn->query($createItemConditionHistorySQL);
    $messages[] = "Item condition history table created successfully";
    
    // Create tracking function
    $createTrackViewFunction = "
    CREATE FUNCTION IF NOT EXISTS track_item_view(item_id INT, user_id INT, source VARCHAR(50))
    RETURNS INT
    BEGIN
        INSERT INTO item_views (item_id, user_id, source, view_date)
        VALUES (item_id, user_id, source, NOW());
        RETURN LAST_INSERT_ID();
    END;
    ";
    
    // Create procedure to update item conditions
    $createUpdateConditionProcedure = "
    CREATE PROCEDURE IF NOT EXISTS update_item_condition(
        IN p_item_id INT,
        IN p_new_condition VARCHAR(50),
        IN p_user_id INT,
        IN p_notes TEXT
    )
    BEGIN
        DECLARE v_previous_condition VARCHAR(50);
        
        -- Get current condition
        SELECT `condition` INTO v_previous_condition FROM inventory_items 
        WHERE id = p_item_id LIMIT 1;
        
        -- Update the item
        UPDATE inventory_items SET 
            `condition` = p_new_condition,
            last_updated = NOW()
        WHERE id = p_item_id;
        
        -- Record the change
        INSERT INTO item_condition_history 
            (item_id, previous_condition, new_condition, changed_by, notes)
        VALUES 
            (p_item_id, v_previous_condition, p_new_condition, p_user_id, p_notes);
    END;
    ";
    
    // Try to create function and procedure if supported
    try {
        $conn->query("DELIMITER //");
        $conn->query($createTrackViewFunction);
        $conn->query("DELIMITER ;");
        $messages[] = "Item view tracking function created successfully";
    } catch (Exception $e) {
        $messages[] = "Note: Could not create tracking function: " . $e->getMessage();
    }
    
    try {
        $conn->query("DELIMITER //");
        $conn->query($createUpdateConditionProcedure);
        $conn->query("DELIMITER ;");
        $messages[] = "Item condition update procedure created successfully";
    } catch (Exception $e) {
        $messages[] = "Note: Could not create condition update procedure: " . $e->getMessage();
    }
    
    // Create view for item statistics
    $createItemStatsViewSQL = "
    CREATE OR REPLACE VIEW `item_statistics` AS
    SELECT 
        i.id,
        i.item_name,
        i.serial_number,
        i.condition,
        COUNT(DISTINCT v.id) AS view_count,
        COUNT(DISTINCT u.id) AS usage_count,
        MAX(v.view_date) AS last_viewed,
        MAX(u.usage_date) AS last_used,
        DATEDIFF(CURRENT_DATE, i.purchase_date) AS age_days,
        CASE 
            WHEN i.warranty_expiration IS NOT NULL THEN 
                DATEDIFF(i.warranty_expiration, CURRENT_DATE) 
            ELSE NULL 
        END AS days_to_warranty_expiration
    FROM 
        inventory_items i
    LEFT JOIN 
        item_views v ON i.id = v.item_id
    LEFT JOIN 
        item_usage u ON i.id = u.item_id
    GROUP BY 
        i.id;
    ";
    
    try {
        $conn->query($createItemStatsViewSQL);
        $messages[] = "Item statistics view created successfully";
    } catch (Exception $e) {
        $messages[] = "Note: Could not create item statistics view: " . $e->getMessage();
    }
    
    // Success!
    $result = [
        'success' => true,
        'messages' => $messages
    ];
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage(),
        'messages' => $messages
    ];
}

// Return result as JSON
header('Content-Type: application/json');
echo json_encode($result); 