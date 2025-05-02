<?php
/**
 * Check if the users table exists
 */

// Include database connection
require_once('config/db.php');

// Get database connection
$conn = getConnection();

// Set default response
$response = [
    'success' => true,
    'table_exists' => false,
    'message' => 'Users table does not exist'
];

if (!$conn) {
    $response['success'] = false;
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

// Check if the users table exists
$query = "SHOW TABLES LIKE 'users'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $response['table_exists'] = true;
    $response['message'] = 'Users table exists';
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 