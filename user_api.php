<?php
/**
 * User Management API
 * Handles user-related operations (CRUD)
 */

// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Include database connection
require_once('config/db.php');

// Get database connection
$conn = getConnection();

// Check if connection failed
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Handle GET requests (read operations)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get single user
    if (isset($_GET['user_id'])) {
        $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID'
            ]);
            exit;
        }
        
        // Prepare SQL statement
        $sql = "SELECT user_id, username, email, role, status, created_at FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
        exit;
    }
    
    // Get all users with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users";
    $countResult = $conn->query($countSql);
    $totalUsers = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $limit);
    
    // Get users for current page
    $sql = "SELECT user_id, username, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalUsers,
            'pages' => $totalPages
        ]
    ]);
    exit;
}

// Handle POST requests (create and update operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if this is a delete operation
    if (isset($input['action']) && $input['action'] === 'delete') {
        // Validate user ID
        if (!isset($input['user_id']) || !filter_var($input['user_id'], FILTER_VALIDATE_INT)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID'
            ]);
            exit;
        }
        
        $userId = $input['user_id'];
        
        // Don't allow deleting the current user
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ]);
            exit;
        }
        
        // Delete user
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete user: ' . $conn->error
            ]);
        }
        exit;
    }
    
    // Create or update user
    // Validate required fields
    if (!isset($input['username']) || empty(trim($input['username'])) || 
        !isset($input['email']) || empty(trim($input['email']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Username and email are required'
        ]);
        exit;
    }
    
    // Check if we're updating an existing user
    $isUpdate = isset($input['user_id']) && !empty($input['user_id']);
    
    // If creating new user, password is required
    if (!$isUpdate && (!isset($input['password']) || empty($input['password']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Password is required for new users'
        ]);
        exit;
    }
    
    // Sanitize inputs
    $username = trim($input['username']);
    $email = trim($input['email']);
    $role = isset($input['role']) ? trim($input['role']) : 'staff';
    $status = isset($input['status']) ? trim($input['status']) : 'active';
    
    // Check if username already exists (for new users or username change)
    if ($isUpdate) {
        $userId = $input['user_id'];
        $checkSql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('si', $username, $userId);
    } else {
        $checkSql = "SELECT user_id FROM users WHERE username = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $username);
    }
    
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists'
        ]);
        exit;
    }
    
    // Hash password if provided
    $hasPassword = isset($input['password']) && !empty($input['password']);
    $passwordHash = $hasPassword ? password_hash($input['password'], PASSWORD_DEFAULT) : null;
    
    if ($isUpdate) {
        // Update existing user
        if ($hasPassword) {
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ?, password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssi', $username, $email, $role, $status, $passwordHash, $userId);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssi', $username, $email, $role, $status, $userId);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update user: ' . $conn->error
            ]);
        }
    } else {
        // Create new user
        $sql = "INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $username, $email, $passwordHash, $role, $status);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User created successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create user: ' . $conn->error
            ]);
        }
    }
    
    exit;
}

// Handle unsupported methods
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'Unsupported request method'
]);
exit; 