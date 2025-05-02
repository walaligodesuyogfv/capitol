<?php
/**
 * Update PO Tables
 * This script updates the purchase_orders and po_items tables to match the required schema
 */

// Include database connection
include 'config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function for logging
function logMessage($message) {
    echo $message . "<br>";
    error_log($message);
    return $message;
}

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }

    $messages = [];
    $messages[] = logMessage("Starting database update...");

    // Update purchase_orders table
    $messages[] = logMessage("Checking purchase_orders table...");

    $tableCheck = $conn->query("SHOW TABLES LIKE 'purchase_orders'");
    if ($tableCheck->num_rows == 0) {
        // Create purchase_orders table
        $messages[] = logMessage("Creating purchase_orders table...");
        
        $createTable = "CREATE TABLE purchase_orders (
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
        
        if ($conn->query($createTable)) {
            $messages[] = logMessage("purchase_orders table created successfully");
        } else {
            throw new Exception("Failed to create purchase_orders table: " . $conn->error);
        }
    } else {
        $messages[] = logMessage("purchase_orders table already exists, checking columns...");
        
        // Check and add any missing columns
        $requiredColumns = [
            'po_id' => "INT AUTO_INCREMENT PRIMARY KEY",
            'po_no' => "VARCHAR(50) NOT NULL",
            'supplier_name' => "VARCHAR(100)",
            'po_date' => "DATE",
            'mode_of_procurement' => "VARCHAR(100)",
            'supplier_email' => "VARCHAR(100)",
            'supplier_address' => "TEXT",
            'supplier_tel' => "VARCHAR(50)",
            'total_amount' => "DECIMAL(12,2) DEFAULT 0.00",
            'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        $columnsAdded = 0;
        foreach ($requiredColumns as $column => $definition) {
            $check = $conn->query("SHOW COLUMNS FROM purchase_orders LIKE '$column'");
            if ($check->num_rows == 0) {
                $messages[] = logMessage("Adding missing column: $column");
                $addColumn = $conn->query("ALTER TABLE purchase_orders ADD COLUMN $column $definition");
                if ($addColumn) {
                    $columnsAdded++;
                } else {
                    $messages[] = logMessage("Warning: Failed to add column $column: " . $conn->error);
                }
            }
        }
        
        if ($columnsAdded > 0) {
            $messages[] = logMessage("Added $columnsAdded missing columns to purchase_orders table");
        } else {
            $messages[] = logMessage("All required columns already exist in purchase_orders table");
        }
    }

    // Update po_items table
    $messages[] = logMessage("Checking po_items table...");

    $tableCheck = $conn->query("SHOW TABLES LIKE 'po_items'");
    if ($tableCheck->num_rows == 0) {
        // Create po_items table
        $messages[] = logMessage("Creating po_items table...");
        
        $createTable = "CREATE TABLE po_items (
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
        
        if ($conn->query($createTable)) {
            $messages[] = logMessage("po_items table created successfully");
        } else {
            throw new Exception("Failed to create po_items table: " . $conn->error);
        }
    } else {
        $messages[] = logMessage("po_items table already exists, checking columns...");
        
        // Check and add any missing columns
        $requiredColumns = [
            'po_item_id' => "INT AUTO_INCREMENT PRIMARY KEY",
            'po_id' => "INT NOT NULL",
            'item_name' => "VARCHAR(255) NOT NULL",
            'unit' => "VARCHAR(50)",
            'description' => "TEXT",
            'quantity' => "INT NOT NULL DEFAULT 1",
            'unit_cost' => "DECIMAL(12,2) NOT NULL DEFAULT 0.00"
        ];
        
        $columnsAdded = 0;
        foreach ($requiredColumns as $column => $definition) {
            $check = $conn->query("SHOW COLUMNS FROM po_items LIKE '$column'");
            if ($check->num_rows == 0) {
                $messages[] = logMessage("Adding missing column: $column");
                $addColumn = $conn->query("ALTER TABLE po_items ADD COLUMN $column $definition");
                if ($addColumn) {
                    $columnsAdded++;
                } else {
                    $messages[] = logMessage("Warning: Failed to add column $column: " . $conn->error);
                }
            }
        }
        
        // Check for total_cost column (computed column)
        $check = $conn->query("SHOW COLUMNS FROM po_items LIKE 'total_cost'");
        if ($check->num_rows == 0) {
            $messages[] = logMessage("Adding total_cost computed column");
            // Drop the column if it exists as a regular column
            $conn->query("ALTER TABLE po_items DROP COLUMN IF EXISTS total_cost");
            // Add it as a computed column
            $addColumn = $conn->query("ALTER TABLE po_items ADD COLUMN total_cost DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED");
            if ($addColumn) {
                $columnsAdded++;
            } else {
                $messages[] = logMessage("Warning: Failed to add computed column total_cost: " . $conn->error);
            }
        }
        
        // Check if old column names need to be migrated
        $columnMappings = [
            'item_description' => 'description',
            'amount' => 'total_cost'
        ];
        
        foreach ($columnMappings as $oldColumn => $newColumn) {
            $oldCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE '$oldColumn'");
            $newCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE '$newColumn'");
            
            if ($oldCheck->num_rows > 0 && $newCheck->num_rows === 0) {
                $messages[] = logMessage("Migrating data from $oldColumn to $newColumn");
                
                // For non-computed columns, we can use CHANGE COLUMN
                if ($newColumn !== 'total_cost') {
                    $updateQuery = "ALTER TABLE po_items CHANGE COLUMN `$oldColumn` `$newColumn` TEXT";
                    if ($conn->query($updateQuery)) {
                        $messages[] = logMessage("Successfully migrated $oldColumn to $newColumn");
                    } else {
                        $messages[] = logMessage("Warning: Failed to migrate $oldColumn to $newColumn: " . $conn->error);
                    }
                } else {
                    // For total_cost, we need to create it as a computed column and drop the old one
                    $conn->query("ALTER TABLE po_items ADD COLUMN total_cost DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED");
                    $conn->query("ALTER TABLE po_items DROP COLUMN $oldColumn");
                    $messages[] = logMessage("Created computed column $newColumn and dropped $oldColumn");
                }
            }
        }
        
        if ($columnsAdded > 0) {
            $messages[] = logMessage("Added $columnsAdded missing columns to po_items table");
        } else {
            $messages[] = logMessage("All required columns already exist in po_items table");
        }
    }

    // Success message
    $messages[] = logMessage("Database update completed successfully");
    echo "<h2>Update Completed</h2>";
    echo "<p>The purchase_orders and po_items tables have been updated to match the required schema.</p>";
    echo "<a href='index.php'>Return to Dashboard</a>";

} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
    echo "<a href='index.php'>Return to Dashboard</a>";
    error_log("Error updating database: " . $e->getMessage());
}
?> 