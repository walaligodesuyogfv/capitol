<?php
// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define application access constant
define('APP_ACCESS', true);

// Database configuration
require_once 'config/db_config.php';

// Database connection
function getDbConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Helper function to sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}

// Helper function to hash passwords securely
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Helper function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Process different actions based on request
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';

switch ($action) {
    case 'getUsers':
        getUsers();
        break;
    
    case 'getUser':
        getUser();
        break;
    
    case 'addUser':
        addUser();
        break;
    
    case 'updateUser':
        updateUser();
        break;
    
    case 'deleteUser':
        deleteUser();
        break;
    
    case 'login':
        loginUser();
        break;
    
    case 'logout':
        logoutUser();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Function to get all users
function getUsers() {
    $conn = getDbConnection();
    
    // Check for search query
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $whereClause = '';
    
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $whereClause = " WHERE username LIKE '%$search%' OR role LIKE '%$search%'";
    }
    
    $sql = "SELECT id, username, role, last_login FROM users" . $whereClause . " ORDER BY id DESC";
    $result = $conn->query($sql);
    
    $users = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'users' => $users]);
}

// Function to get a single user
function getUser() {
    if (!isset($_GET['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    $conn = getDbConnection();
    $id = $conn->real_escape_string(sanitizeInput($_GET['id']));
    
    $sql = "SELECT id, username, role FROM users WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $conn->close();
}

// Function to add a new user
function addUser() {
    // Validate input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Don't sanitize password before hashing
    $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : '';
    
    if (empty($username) || empty($password) || empty($role)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    $conn = getDbConnection();
    
    // Check if username already exists
    $checkSql = "SELECT id FROM users WHERE username = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('s', $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        return;
    }
    
    $checkStmt->close();
    
    // Hash the password
    $hashedPassword = hashPassword($password);
    
    // Insert new user
    $sql = "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $username, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        $stmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . $conn->error]);
    }
}

// Function to update an existing user
function updateUser() {
    // Validate input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $userId = isset($_POST['user_id']) ? sanitizeInput($_POST['user_id']) : '';
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Don't sanitize password before hashing
    $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : '';
    
    if (empty($userId) || empty($username) || empty($role)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID, username, and role are required']);
        return;
    }
    
    $conn = getDbConnection();
    
    // Check if username already exists (excluding current user)
    $checkSql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('si', $username, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        return;
    }
    
    $checkStmt->close();
    
    // Update user (with or without password)
    if (!empty($password)) {
        // Hash the new password
        $hashedPassword = hashPassword($password);
        
        $sql = "UPDATE users SET username = ?, password = ?, role = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $username, $hashedPassword, $role, $userId);
    } else {
        // Update without changing password
        $sql = "UPDATE users SET username = ?, role = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $username, $role, $userId);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        $stmt->close();
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $conn->error]);
    }
}

// Function to delete a user
function deleteUser() {
    // Validate input
    if (!isset($_GET['id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    $conn = getDbConnection();
    $id = $conn->real_escape_string(sanitizeInput($_GET['id']));
    
    $sql = "DELETE FROM users WHERE id = $id";
    
    if ($conn->query($sql)) {
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        $conn->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $conn->error]);
    }
}

// Function to handle user login
function loginUser() {
    // Validate input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Don't sanitize password before verification
    
    if (empty($username) || empty($password)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        return;
    }
    
    $conn = getDbConnection();
    
    // Get user by username
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (verifyPassword($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login timestamp
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('i', $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt->close();
            $conn->close();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            // Password is incorrect
            $stmt->close();
            $conn->close();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } else {
        // User not found
        $stmt->close();
        $conn->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
}

// Function to handle user logout
function logoutUser() {
    // Clear session
    session_unset();
    session_destroy();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logout successful']);
}
?> 