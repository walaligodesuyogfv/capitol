<?php
// Include database connection
include 'config/db.php';

// Check if the connection was established
if (!$conn) {
    die("Connection failed");
}

// Get table structure
$query = "SHOW CREATE TABLE inventory_items";
$result = $conn->query($query);

if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "Error getting table structure: " . $conn->error;
}

// Close the connection
$conn->close();
?> 