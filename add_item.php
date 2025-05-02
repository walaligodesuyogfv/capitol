<?php
header('Content-Type: application/json');

// Include database connection
include 'config/db.php';

// Get JSON input
$json = file_get_contents('php://input');

// Validate JSON
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Log received data for debugging
error_log("Received data: " . json_encode($data));

try {
    // Basic validation
    if (empty($data['item_name'])) {
        throw new Exception("Item name is required");
    }
    
    // Handle serial number
    $serial_number = !empty($data['serial_number']) ? $data['serial_number'] : null;
    
    // Validate condition_status is one of the allowed values
    $allowed_conditions = ['New', 'Good', 'Fair', 'Poor'];
    $condition_status = !empty($data['condition']) ? $data['condition'] : 'New';
    
    if (!in_array($condition_status, $allowed_conditions)) {
        $condition_status = 'New'; // Default to 'New' if not valid
    }
    
    // Format dates correctly for MySQL
    $date_acquired = !empty($data['purchase_date']) ? date('Y-m-d', strtotime($data['purchase_date'])) : null;
    $warranty_expiry = !empty($data['warranty_expiration']) ? date('Y-m-d', strtotime($data['warranty_expiration'])) : null;
    
    // Set parameters with correct names
    $item_name = $data['item_name'];
    $brand_model = !empty($data['brand_model']) ? $data['brand_model'] : null;
    $assigned_to = !empty($data['assigned_to']) ? $data['assigned_to'] : null;
    $location = !empty($data['location']) ? $data['location'] : null;
    $notes = !empty($data['notes']) ? $data['notes'] : null;
    
    // Get custom item_id if provided
    $custom_item_id = !empty($data['item_id']) ? $data['item_id'] : null;

    // Check if a custom item_id was provided and is not empty
    if (!empty($custom_item_id)) {
        // First, check if the custom ID already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM inventory_items WHERE item_id = ?");
        $check_stmt->bind_param("s", $custom_item_id);
        $check_stmt->execute();
        $check_stmt->bind_result($id_count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($id_count > 0) {
            throw new Exception("Item ID {$custom_item_id} already exists. Please use a different ID.");
        }
        
        // Prepare the SQL with explicit item_id
        $stmt = $conn->prepare("INSERT INTO inventory_items(
            item_id,
            item_name,
            brand_model,
            serial_number,
            date_acquired,
            warranty_expiry,
            assigned_to,
            condition_status,
            location,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }

        // Bind parameters with item_id included
        $stmt->bind_param("ssssssssss",
            $custom_item_id,
            $item_name,
            $brand_model,
            $serial_number,
            $date_acquired,
            $warranty_expiry,
            $assigned_to,
            $condition_status,
            $location,
            $notes
        );
        
        // Execute the statement
        $result = $stmt->execute();
        error_log("Execute result: " . ($result ? "success" : "failed") . ", Error: " . $stmt->error);
        
        if (!$result) {
            $error = $stmt->error;
            $errno = $conn->errno;
            $stmt->close();
            throw new Exception("Database execute error ($errno): " . $error);
        }
        
        $new_item_id = $custom_item_id;
        $stmt->close();
    } else {
        // No custom ID provided, use the original auto-increment logic
        // Try to insert the item - only one insert will happen
        $insert_success = false;
        $max_attempts = 3;
        $attempt_count = 0;
        $new_item_id = null;
        
        // If serial number exists and might be a duplicate, try to make it unique
        while (!$insert_success && $attempt_count < $max_attempts) {
            $stmt = $conn->prepare("INSERT INTO inventory_items(
                item_name,
                brand_model,
                serial_number,
                date_acquired,
                warranty_expiry,
                assigned_to,
                condition_status,
                location,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            // Bind parameters (s = string)
            $stmt->bind_param("sssssssss",
                $item_name,
                $brand_model,
                $serial_number,
                $date_acquired,
                $warranty_expiry,
                $assigned_to,
                $condition_status,
                $location,
                $notes
            );

            // Try to execute the statement
            $result = $stmt->execute();
            error_log("Execute result: " . ($result ? "success" : "failed") . ", Error: " . $stmt->error);
            
            if ($result) {
                $insert_success = true;
                $new_item_id = $stmt->insert_id;
                $stmt->close();
                break; // Exit the loop - successful insert
            } else {
                // If duplicate serial number error (1062)
                if ($conn->errno == 1062 && $serial_number !== null) {
                    $stmt->close();
                    $attempt_count++;
                    // Modify serial number to make it unique
                    $serial_number = $serial_number . "-" . $attempt_count;
                    error_log("Retrying with modified serial number: " . $serial_number);
                } else {
                    // For any other error
                    $error = $stmt->error;
                    $errno = $conn->errno;
                    $stmt->close();
                    throw new Exception("Database execute error ($errno): " . $error);
                }
            }
        }
        
        // If we couldn't insert after multiple attempts with modified serial numbers,
        // try one last time with a completely random serial number
        if (!$insert_success) {
            $serial_number = 'AUTO-' . uniqid();
            error_log("Final attempt with auto-generated serial number: " . $serial_number);
            
            $stmt = $conn->prepare("INSERT INTO inventory_items(
                item_name,
                brand_model,
                serial_number,
                date_acquired,
                warranty_expiry,
                assigned_to,
                condition_status,
                location,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Database prepare error: " . $conn->error);
            }

            $stmt->bind_param("sssssssss",
                $item_name,
                $brand_model,
                $serial_number,
                $date_acquired,
                $warranty_expiry,
                $assigned_to,
                $condition_status,
                $location,
                $notes
            );

            $result = $stmt->execute();
            error_log("Final execute result: " . ($result ? "success" : "failed") . ", Error: " . $stmt->error);
            
            if (!$result) {
                $error = $stmt->error;
                $errno = $conn->errno;
                $stmt->close();
                throw new Exception("Database execute error in final attempt ($errno): " . $error);
            }
            
            $new_item_id = $stmt->insert_id;
            $stmt->close();
        }
    }
    
    echo json_encode([
        'success' => true, 
        'item_id' => $new_item_id,
        'message' => 'Item added successfully'
    ]);

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log("Error in add_item.php: " . $errorMsg);
    
    // Return error instead of success when there's an error
    echo json_encode([
        'success' => false, 
        'message' => $errorMsg
    ]);
}

$conn->close();
?>