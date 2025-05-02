<?php
// setup_database.php - Script to set up database tables

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    die("Database configuration file not found!");
}

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed!");
}

// Function to execute SQL from file
function executeSQLFile($conn, $filename) {
    if (!file_exists($filename)) {
        echo "File not found: $filename<br>";
        return false;
    }
    
    $sql = file_get_contents($filename);
    
    // Split SQL file into individual queries
    $queries = explode(';', $sql);
    
    // Execute each query
    $success = true;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if ($conn->query($query)) {
            echo "Query executed successfully: " . substr($query, 0, 50) . "...<br>";
        } else {
            echo "Error executing query: " . $conn->error . "<br>";
            echo "Query: " . $query . "<br>";
            $success = false;
        }
    }
    
    return $success;
}

// Setup users table
echo "<h2>Setting up users table...</h2>";
$result = executeSQLFile($conn, 'setup_users_table.sql');
if ($result) {
    echo "<p>Users table setup successfully!</p>";
} else {
    echo "<p>Failed to setup users table.</p>";
}

// Create a default admin user with password hash
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user already exists
$checkQuery = "SELECT COUNT(*) as count FROM users WHERE username = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$count = $checkResult->fetch_assoc()['count'];

if ($count == 0) {
    // Insert admin user
    $query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $passwordHash);
    
    if ($stmt->execute()) {
        echo "<p>Default admin user created successfully!<br>";
        echo "Username: $username<br>";
        echo "Password: $password</p>";
    } else {
        echo "<p>Failed to create default admin user: " . $stmt->error . "</p>";
    }
} else {
    echo "<p>Admin user already exists.</p>";
}

// Close connection
$conn->close();

echo "<p><a href='login.php'>Go to login page</a></p>";
?> 