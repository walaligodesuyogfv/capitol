<?php
// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    die("Database configuration not found");
}

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Failed to connect to database");
}

echo "<h2>Database Tables</h2>";

// Check tables
$result = $conn->query("SHOW TABLES");
echo "<ul>";
$usersExists = false;
while ($row = $result->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
    if ($row[0] == 'users') {
        $usersExists = true;
    }
}
echo "</ul>";

// Check users table structure if it exists
if ($usersExists) {
    echo "<h2>Users Table Structure</h2>";
    $result = $conn->query("DESCRIBE users");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Users table does not exist. Please use the 'Setup Users Table' button to create it.</p>";
}
?> 