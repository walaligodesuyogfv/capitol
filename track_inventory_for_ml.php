<?php
/**
 * Track Inventory for ML
 * This script handles tracking of inventory items for ML predictions
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Function to get database connection
function getDBConnection() {
    // Create database directory if it doesn't exist
    $db_dir = __DIR__ . '/config';
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0755, true);
    }
    
    // Connect to SQLite database
    try {
        $db = new PDO('sqlite:' . $db_dir . '/ictd_inventory.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

// Function to ensure tables exist
function ensureTablesExist($db) {
    // Create inventory table if it doesn't exist
    $db->exec('CREATE TABLE IF NOT EXISTS inventory (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_code TEXT,
        item_name TEXT,
        brand_model TEXT,
        serial_number TEXT UNIQUE,
        purchase_date TEXT,
        warranty_expiration TEXT,
        assigned_to TEXT,
        location TEXT,
        condition TEXT,
        notes TEXT,
        date_added DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create inventory ML tracking table
    $db->exec('CREATE TABLE IF NOT EXISTS inventory_ml_tracking (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inventory_id INTEGER,
        item_name TEXT,
        brand_model TEXT,
        serial_number TEXT,
        condition TEXT,
        purchase_date TEXT,
        warranty_expiration TEXT,
        location TEXT,
        tracking_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create prediction history table
    $db->exec('CREATE TABLE IF NOT EXISTS prediction_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        prediction_type TEXT,
        prediction_data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
}

// Function to track inventory item for ML predictions
function trackItemForML($item) {
    // Get database connection
    $db = getDBConnection();
    
    if (!$db) {
        return [
            'success' => false,
            'message' => 'Database connection failed'
        ];
    }
    
    try {
        // Ensure tables exist
        ensureTablesExist($db);
        
        // Find if item already exists (by serial number if provided)
        $inventory_id = null;
        if (!empty($item['serial_number'])) {
            $stmt = $db->prepare('SELECT id FROM inventory WHERE serial_number = ?');
            $stmt->execute([$item['serial_number']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $inventory_id = $result['id'];
            }
        }
        
        // If no existing inventory item found, create one
        if (!$inventory_id) {
            $stmt = $db->prepare('
                INSERT INTO inventory (
                    item_code, item_name, brand_model, serial_number, 
                    purchase_date, warranty_expiration, assigned_to, 
                    location, condition, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $item['item_code'] ?? '',
                $item['item_name'] ?? '',
                $item['brand_model'] ?? '',
                $item['serial_number'] ?? '',
                $item['purchase_date'] ?? '',
                $item['warranty_expiration'] ?? '',
                $item['assigned_to'] ?? '',
                $item['location'] ?? '',
                $item['condition'] ?? 'Good',
                $item['notes'] ?? ''
            ]);
            
            $inventory_id = $db->lastInsertId();
        }
        
        // Now track this item in the ML tracking table
        $stmt = $db->prepare('
            INSERT INTO inventory_ml_tracking (
                inventory_id, item_name, brand_model, serial_number, 
                condition, purchase_date, warranty_expiration, location
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $inventory_id,
            $item['item_name'] ?? '',
            $item['brand_model'] ?? '',
            $item['serial_number'] ?? '',
            $item['condition'] ?? 'Good',
            $item['purchase_date'] ?? '',
            $item['warranty_expiration'] ?? '',
            $item['location'] ?? ''
        ]);
        
        // Generate ML predictions using the Python script
        generatePredictions();
        
        return [
            'success' => true,
            'message' => 'Item tracked successfully for ML predictions',
            'inventory_id' => $inventory_id
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error tracking item: ' . $e->getMessage()
        ];
    }
}

// Function to generate ML predictions using Python
function generatePredictions() {
    // Path to the Python script
    $pythonScript = __DIR__ . '/DataAnalytics/inventory_predictions.py';
    
    // Log the prediction attempt
    error_log("Generating ML predictions using: " . $pythonScript);
    
    // Check if script exists
    if (!file_exists($pythonScript)) {
        error_log("ERROR: Python prediction script not found at: " . $pythonScript);
        return false;
    }
    
    // Create output directory for prediction results if it doesn't exist
    $outputDir = __DIR__ . '/config/prediction_results';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Output file for log
    $timestamp = date('Y-m-d_H-i-s');
    $outputFile = $outputDir . '/prediction_' . $timestamp . '.json';
    
    try {
        // Determine the appropriate Python command based on OS
        $pythonCommand = 'python';
        
        // For Windows environments, you might need to use 'py' or the full path
        if (PHP_OS == 'WINNT') {
            // Try different Python executables (Windows systems might use 'py' or 'python3')
            $possibleCommands = ['python', 'py', 'python3'];
            foreach ($possibleCommands as $cmd) {
                $testCommand = sprintf('%s --version 2>&1', $cmd);
                $output = [];
                $returnVar = -1;
                exec($testCommand, $output, $returnVar);
                
                if ($returnVar === 0) {
                    $pythonCommand = $cmd;
                    break;
                }
            }
            
            // Run in background using start command
            $command = sprintf('start /B %s %s > %s 2>&1', 
                $pythonCommand, 
                escapeshellarg($pythonScript), 
                escapeshellarg($outputFile)
            );
            
            // Execute the command and don't wait for results
            pclose(popen($command, 'r'));
            
            // Log the prediction execution
            error_log("Executed prediction script with command: " . $command);
        } else {
            // Unix/Linux/MacOS
            $command = sprintf('%s %s > %s 2>&1 &', 
                $pythonCommand, 
                escapeshellarg($pythonScript), 
                escapeshellarg($outputFile)
            );
            
            // Execute command in background
            exec($command);
            
            // Log the prediction execution
            error_log("Executed prediction script with command: " . $command);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error generating predictions: " . $e->getMessage());
        return false;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data from request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Track item for ML predictions
    $result = trackItemForML($input);
    echo json_encode($result);
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
} 