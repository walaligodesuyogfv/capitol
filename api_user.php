<?php
/**
 * User Management RESTful API
 * Provides endpoints for user operations (CRUD)
 */

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access',
        'code' => 401
    ]);
    exit;
}

// Include database connection
require_once('config/db.php');

// Get database connection
$conn = getConnection();

// Check connection
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'code' => 500
    ]);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        handleGetRequest($conn);
        break;
    case 'POST':
        handlePostRequest($conn);
        break;
    case 'PUT':
        handlePutRequest($conn);
        break;
    case 'DELETE':
        handleDeleteRequest($conn);
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed',
            'code' => 405
        ]);
        break;
}

/**
 * Handle GET requests for user data
 *
 * @param mysqli $conn Database connection
 */
function handleGetRequest($conn) {
    // Check if user ID is provided
    if (isset($_GET['id'])) {
        // Get a single user
        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$userId) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user ID',
                'code' => 400
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
                'message' => 'User not found',
                'code' => 404
            ]);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'data' => $user,
            'code' => 200
        ]);
        exit;
    } else {
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
            ],
            'code' => 200
        ]);
        exit;
    }
}

/**
 * Handle POST requests for creating users
 *
 * @param mysqli $conn Database connection
 */
function handlePostRequest($conn) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['username']) || empty(trim($input['username'])) || 
        !isset($input['email']) || empty(trim($input['email'])) ||
        !isset($input['password']) || empty($input['password'])) {
        
        echo json_encode([
            'success' => false,
            'message' => 'Username, email, and password are required',
            'code' => 400
        ]);
        exit;
    }
    
    // Sanitize inputs
    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = $input['password'];
    $confirmPassword = isset($input['confirm_password']) ? $input['confirm_password'] : '';
    $role = isset($input['role']) ? trim($input['role']) : 'staff';
    $status = isset($input['status']) ? trim($input['status']) : 'active';
    
    // Check password match
    if ($password !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => 'Passwords do not match',
            'code' => 400
        ]);
        exit;
    }
    
    // Check if username already exists
    $checkSql = "SELECT user_id FROM users WHERE username = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('s', $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists',
            'code' => 409
        ]);
        exit;
    }
    
    // Check if email already exists
    $checkEmailSql = "SELECT user_id FROM users WHERE email = ?";
    $checkEmailStmt = $conn->prepare($checkEmailSql);
    $checkEmailStmt->bind_param('s', $email);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists',
            'code' => 409
        ]);
        exit;
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Create new user
    $sql = "INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssss', $username, $email, $passwordHash, $role, $status);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $conn->insert_id,
            'code' => 201
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create user: ' . $conn->error,
            'code' => 500
        ]);
    }
    exit;
}

/**
 * Handle PUT requests for updating users
 *
 * @param mysqli $conn Database connection
 */
function handlePutRequest($conn) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if user ID is provided
    if (!isset($input['user_id']) || !filter_var($input['user_id'], FILTER_VALIDATE_INT)) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid user ID is required',
            'code' => 400
        ]);
        exit;
    }
    
    // Validate required fields
    if (!isset($input['username']) || empty(trim($input['username'])) || 
        !isset($input['email']) || empty(trim($input['email']))) {
        
        echo json_encode([
            'success' => false,
            'message' => 'Username and email are required',
            'code' => 400
        ]);
        exit;
    }
    
    // Sanitize inputs
    $userId = $input['user_id'];
    $username = trim($input['username']);
    $email = trim($input['email']);
    $role = isset($input['role']) ? trim($input['role']) : 'staff';
    $status = isset($input['status']) ? trim($input['status']) : 'active';
    
    // Check if username already exists (excluding current user)
    $checkSql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('si', $username, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Username already exists',
            'code' => 409
        ]);
        exit;
    }
    
    // Check if email already exists (excluding current user)
    $checkEmailSql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $checkEmailStmt = $conn->prepare($checkEmailSql);
    $checkEmailStmt->bind_param('si', $email, $userId);
    $checkEmailStmt->execute();
    $checkEmailResult = $checkEmailStmt->get_result();
    
    if ($checkEmailResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists',
            'code' => 409
        ]);
        exit;
    }
    
    // Check if password should be updated
    $hasPassword = isset($input['password']) && !empty($input['password']);
    
    if ($hasPassword) {
        // Check password match
        if (!isset($input['confirm_password']) || $input['password'] !== $input['confirm_password']) {
            echo json_encode([
                'success' => false,
                'message' => 'Passwords do not match',
                'code' => 400
            ]);
            exit;
        }
        
        // Hash password
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Update user with password
        $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $username, $email, $role, $status, $passwordHash, $userId);
    } else {
        // Update user without password
        $sql = "UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', $username, $email, $role, $status, $userId);
    }
    
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'code' => 200
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update user: ' . $conn->error,
            'code' => 500
        ]);
    }
    exit;
}

/**
 * Handle DELETE requests for removing users
 *
 * @param mysqli $conn Database connection
 */
function handleDeleteRequest($conn) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if user ID is provided
    if (!isset($input['user_id']) || !filter_var($input['user_id'], FILTER_VALIDATE_INT)) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid user ID is required',
            'code' => 400
        ]);
        exit;
    }
    
    $userId = $input['user_id'];
    
    // Don't allow deleting the current user
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete your own account',
            'code' => 403
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
            'message' => 'User deleted successfully',
            'code' => 200
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user: ' . $conn->error,
            'code' => 500
        ]);
    }
    exit;
} 