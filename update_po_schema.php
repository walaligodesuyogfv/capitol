<?php
// Script to update purchase_orders and po_items tables to match user schema
require_once 'config/db.php';

// Enable error reporting for debugging but don't display errors in output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Response headers
header('Content-Type: application/json');

// Function to log messages
function logMessage($message) {
    error_log("[UPDATE_PO_SCHEMA] " . $message);
    return $message;
}

$messages = [];
$errors = [];
$schemaChanges = 0;

try {
    // Check connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $messages[] = logMessage("Starting PO schema update...");
    $conn->autocommit(false); // Start transaction

    // Check purchase_orders table structure
    $messages[] = logMessage("Checking purchase_orders table structure...");
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'purchase_orders'");
    if ($tableCheck->num_rows === 0) {
        // Create purchase_orders table based on user's requirement
        $createPoTable = "CREATE TABLE purchase_orders (
            po_id INT PRIMARY KEY AUTO_INCREMENT,
            po_no VARCHAR(50) NOT NULL UNIQUE,
            supplier_name VARCHAR(100),
            po_date DATE,
            mode_of_procurement VARCHAR(100),
            supplier_email VARCHAR(100),
            supplier_address TEXT,
            supplier_tel VARCHAR(50),
            total_amount DECIMAL(12,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($createPoTable)) {
            $messages[] = logMessage("Created purchase_orders table successfully");
            $schemaChanges++;
        } else {
            throw new Exception("Failed to create purchase_orders table: " . $conn->error);
        }
    } else {
        $messages[] = logMessage("purchase_orders table already exists");
    }
    
    // Check po_items table structure
    $messages[] = logMessage("Checking po_items table structure...");
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'po_items'");
    if ($tableCheck->num_rows === 0) {
        // Create po_items table based on user's requirement
        $createPoItemsTable = "CREATE TABLE po_items (
            po_item_id INT AUTO_INCREMENT PRIMARY KEY,
            po_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            unit VARCHAR(50),
            description TEXT,
            quantity INT NOT NULL DEFAULT 1,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_cost DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED,
            FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id) ON DELETE CASCADE
        )";
        
        if ($conn->query($createPoItemsTable)) {
            $messages[] = logMessage("Created po_items table successfully");
            $schemaChanges++;
        } else {
            throw new Exception("Failed to create po_items table: " . $conn->error);
        }
    } else {
        $messages[] = logMessage("po_items table already exists, checking columns...");
        
        // Check for required columns and add/modify them as needed
        $requiredColumns = [
            'unit' => [
                'type' => 'VARCHAR(50)',
                'position' => 'AFTER item_name'
            ],
            'description' => [
                'type' => 'TEXT',
                'position' => 'AFTER unit'
            ],
            'quantity' => [
                'type' => 'INT NOT NULL DEFAULT 1',
                'position' => 'AFTER description'
            ],
            'unit_cost' => [
                'type' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00',
                'position' => 'AFTER quantity'
            ],
            'total_cost' => [
                'type' => 'DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED',
                'position' => ''
            ]
        ];
        
        // Check for old column names that need to be renamed
        $oldToNewColumnMapping = [
            'item_description' => 'description',
            'amount' => 'total_cost'
        ];
        
        // First, check if old columns exist and need to be renamed
        foreach ($oldToNewColumnMapping as $oldColumn => $newColumn) {
            $oldColumnCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE '$oldColumn'");
            $newColumnCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE '$newColumn'");
            
            if ($oldColumnCheck->num_rows > 0 && $newColumnCheck->num_rows === 0) {
                // Rename old column to new name
                $sql = "ALTER TABLE po_items CHANGE COLUMN `$oldColumn` `$newColumn` TEXT";
                if ($conn->query($sql)) {
                    $messages[] = logMessage("Renamed column '$oldColumn' to '$newColumn'");
                    $schemaChanges++;
                } else {
                    $errors[] = "Failed to rename column '$oldColumn' to '$newColumn': " . $conn->error;
                }
            }
        }
        
        // Now add any missing columns
        foreach ($requiredColumns as $column => $details) {
            $columnCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE '$column'");
            
            if ($columnCheck->num_rows === 0) {
                $position = !empty($details['position']) ? $details['position'] : '';
                $sql = "ALTER TABLE po_items ADD COLUMN `$column` {$details['type']} $position";
                
                if ($conn->query($sql)) {
                    $messages[] = logMessage("Added column '$column' to po_items table");
                    $schemaChanges++;
                } else {
                    $errors[] = "Failed to add column '$column': " . $conn->error;
                }
            }
        }
    }
    
    // Update quantity column from DECIMAL to INT if needed
    $quantityColumnInfo = $conn->query("SHOW COLUMNS FROM po_items LIKE 'quantity'");
    if ($quantityColumnInfo->num_rows > 0) {
        $columnData = $quantityColumnInfo->fetch_assoc();
        if (strpos(strtolower($columnData['Type']), 'decimal') !== false) {
            $messages[] = logMessage("Converting quantity column from DECIMAL to INT...");
            
            // First modify the column to remove the GENERATED column dependency
            if ($conn->query("ALTER TABLE po_items DROP COLUMN total_cost")) {
                // Now convert quantity to INT
                if ($conn->query("ALTER TABLE po_items MODIFY COLUMN quantity INT NOT NULL DEFAULT 1")) {
                    $messages[] = logMessage("Successfully converted quantity column to INT");
                    $schemaChanges++;
                    
                    // Re-add the total_cost column as GENERATED
                    if ($conn->query("ALTER TABLE po_items ADD COLUMN total_cost DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED")) {
                        $messages[] = logMessage("Re-added total_cost as GENERATED column");
                    } else {
                        $errors[] = "Failed to re-add total_cost column: " . $conn->error;
                    }
                } else {
                    $errors[] = "Failed to convert quantity column to INT: " . $conn->error;
                }
            } else {
                $errors[] = "Failed to drop total_cost column: " . $conn->error;
            }
        }
    }
    
    // Commit transaction if no errors
    if (empty($errors)) {
        $conn->commit();
        $messages[] = logMessage("Schema update completed successfully with $schemaChanges changes");
        
        echo json_encode([
            'success' => true,
            'message' => "Schema update completed successfully",
            'changes' => $schemaChanges,
            'details' => $messages
        ]);
    } else {
        $conn->rollback();
        throw new Exception("Schema update failed with errors: " . implode(", ", $errors));
    }
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && !$conn->connect_error) {
        $conn->rollback();
    }
    
    $errorMessage = $e->getMessage();
    error_log("Schema update error: " . $errorMessage);
    
    echo json_encode([
        'success' => false,
        'message' => "Schema update failed: " . $errorMessage,
        'details' => array_merge($messages, $errors)
    ]);
}

// Close connection
if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}
?> 