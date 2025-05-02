<?php
/**
 * Setup Reports Tables
 * 
 * This script creates the necessary database tables for the reports functionality.
 * It should be called from the admin dashboard when setting up the system.
 */

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    die("Database configuration file not found.");
}

// Get database connection
$conn = getConnection();

if (!$conn) {
    die("Database connection failed.");
}

// Define tables to create
$tables = [
    // Report Configurations Table
    "report_configurations" => "
        CREATE TABLE IF NOT EXISTS report_configurations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            report_type VARCHAR(50) NOT NULL,
            filters TEXT,
            chart_type VARCHAR(50) DEFAULT 'bar',
            time_interval VARCHAR(20) DEFAULT 'monthly',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    
    // Report Schedules Table
    "report_schedules" => "
        CREATE TABLE IF NOT EXISTS report_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_config_id INT NOT NULL,
            frequency VARCHAR(20) NOT NULL, -- daily, weekly, monthly
            recipients TEXT,
            active TINYINT(1) DEFAULT 1,
            last_sent TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (report_config_id) REFERENCES report_configurations(id) ON DELETE CASCADE
        )
    ",
    
    // Report Execution History Table
    "report_history" => "
        CREATE TABLE IF NOT EXISTS report_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_config_id INT,
            execution_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            execution_status VARCHAR(20) NOT NULL, -- success, failure
            generated_file VARCHAR(255),
            error_message TEXT,
            executed_by INT,
            recipients TEXT,
            FOREIGN KEY (report_config_id) REFERENCES report_configurations(id) ON DELETE SET NULL
        )
    ",
    
    // Report Analytics Predictions Table
    "report_predictions" => "
        CREATE TABLE IF NOT EXISTS report_predictions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            prediction_type VARCHAR(50) NOT NULL,
            date_generated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            time_horizon INT NOT NULL,
            confidence_level FLOAT DEFAULT 0.95,
            prediction_data TEXT,
            metrics TEXT,
            generated_by INT
        )
    ",
    
    // Report Metadata Table for storing report templates and layouts
    "report_metadata" => "
        CREATE TABLE IF NOT EXISTS report_metadata (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type VARCHAR(50) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    
    // Audit Log Table for report-related activities
    "report_audit_logs" => "
        CREATE TABLE IF NOT EXISTS report_audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    "
];

// Create the tables
$success = true;
$messages = [];

foreach ($tables as $tableName => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $messages[] = "Table '$tableName' created or already exists.";
        } else {
            $messages[] = "Error creating table '$tableName': " . $conn->error;
            $success = false;
        }
    } catch (Exception $e) {
        $messages[] = "Exception creating table '$tableName': " . $e->getMessage();
        $success = false;
    }
}

// Insert sample report configurations
if ($success) {
    $sampleConfigs = [
        [
            'name' => 'Monthly PAR Summary',
            'description' => 'Monthly summary report of all PARs',
            'report_type' => 'par_detailed',
            'filters' => json_encode(['time_interval' => 'monthly']),
            'chart_type' => 'bar'
        ],
        [
            'name' => 'Quarterly PO Analysis',
            'description' => 'Quarterly analysis of purchase orders',
            'report_type' => 'po_detailed',
            'filters' => json_encode(['time_interval' => 'quarterly']),
            'chart_type' => 'line'
        ],
        [
            'name' => 'Department Asset Distribution',
            'description' => 'Distribution of assets across departments',
            'report_type' => 'summary',
            'filters' => json_encode([]),
            'chart_type' => 'pie'
        ]
    ];
    
    $insertStmt = $conn->prepare("INSERT INTO report_configurations (name, description, report_type, filters, chart_type) 
                               VALUES (?, ?, ?, ?, ?)");
    
    if ($insertStmt) {
        foreach ($sampleConfigs as $config) {
            $insertStmt->bind_param("sssss", 
                $config['name'], 
                $config['description'], 
                $config['report_type'], 
                $config['filters'], 
                $config['chart_type']
            );
            
            if (!$insertStmt->execute()) {
                $messages[] = "Error inserting sample config '" . $config['name'] . "': " . $insertStmt->error;
                $success = false;
            } else {
                $messages[] = "Sample config '" . $config['name'] . "' inserted.";
            }
        }
        
        $insertStmt->close();
    } else {
        $messages[] = "Error preparing statement for sample configs: " . $conn->error;
        $success = false;
    }
}

// Return the result as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'messages' => $messages
]);

// Close the database connection
$conn->close(); 