<?php
include 'config/db.php';

$conn = getConnection();

// Check if connection successful
if (!$conn) {
    die("Connection failed");
}

// Get tables
$result = $conn->query("SHOW TABLES FROM ictd_inventory");

if ($result) {
    echo "Tables in ictd_inventory database:\n";
    while ($row = $result->fetch_row()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "Error getting tables: " . $conn->error;
}

// Check specifically for PAR table
$parResult = $conn->query("SHOW TABLES LIKE 'par'");
echo "\nPAR table exists: " . ($parResult->num_rows > 0 ? "Yes" : "No") . "\n";

// Close connection
$conn->close();
?> 