<?php
/**
 * Setup users database table
 */

// Include database connection
require_once('config/db.php');

// Get database connection
$conn = getConnection();

// Set default response
$response = [
    'success' => true,
    'message' => 'Users table created successfully'
];

if (!$conn) {
    $response['success'] = false;
    $response['message'] = 'Database connection failed';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if the users table already exists
$query = "SHOW TABLES LIKE 'users'";
$result = $conn->query($query);
$tableExists = ($result && $result->num_rows > 0);

if ($tableExists) {
    // Table already exists, check if it needs modification
    $response['message'] = 'Users table already exists';
    
    // Check if the table has all required columns
    $checkColumns = "DESCRIBE users";
    $columnsResult = $conn->query($checkColumns);
    
    // Extract column names
    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    // List of required columns
    $requiredColumns = ['user_id', 'username', 'email', 'password', 'role', 'status', 'created_at'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        // Add missing columns
        foreach ($missingColumns as $column) {
            $alterQuery = "";
            
            switch ($column) {
                case 'role':
                    $alterQuery = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff'";
                    break;
                case 'status':
                    $alterQuery = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
                    break;
                case 'created_at':
                    $alterQuery = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                    break;
                default:
                    // Skip unknown columns
                    continue;
            }
            
            if (!empty($alterQuery)) {
                $conn->query($alterQuery);
            }
        }
        
        $response['message'] = 'Users table updated with missing columns';
    }
} else {
    // Create users table
    $createQuery = "CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createQuery) === TRUE) {
        // Create default admin user
        $defaultUsername = 'admin';
        $defaultEmail = 'admin@system.com';
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('sss', $defaultUsername, $defaultEmail, $defaultPassword);
        
        if ($stmt->execute()) {
            $response['message'] = 'Users table created with default admin user (username: admin, password: admin123)';
        } else {
            $response['message'] = 'Users table created but failed to create default admin user';
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to create users table: ' . $conn->error;
    }
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 