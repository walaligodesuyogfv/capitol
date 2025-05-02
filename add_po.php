<?php
// Make sure no output has been sent before this point
if (headers_sent()) {
    die(json_encode(['success' => false, 'message' => 'Headers already sent, cannot set JSON headers']));
}

// Set the proper content type for JSON responses
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Initialize response array
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    require_once 'config/db.php';
    
    // Verify database structure - check for total_cost column instead of amount
    $columnCheck = $conn->query("SHOW COLUMNS FROM po_items LIKE 'total_cost'");
    if ($columnCheck->num_rows === 0) {
        // If total_cost column doesn't exist, update the schema
        $schemaUpdated = false;
        if (file_exists('update_po_schema.php')) {
            // Run the schema update if the file exists
            require_once 'update_po_schema.php';
            if (isset($schemaChanges) && $schemaChanges > 0) {
                $schemaUpdated = true;
            }
        }
        
        if (!$schemaUpdated) {
            throw new Exception("Database schema is incompatible. Please run the schema update script.");
        }
    }
    
    // Get POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Invalid data received');
    }

    // Ensure necessary fields are present
    $requiredFields = ['po_no', 'supplier_name', 'po_date', 'items'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Calculate total_amount if not provided
    if (!isset($data['total_amount']) || empty($data['total_amount'])) {
        $data['total_amount'] = 0;
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 
                           (isset($item['qty']) ? floatval($item['qty']) : 1);
                           
                $unit_cost = isset($item['unit_cost']) ? floatval($item['unit_cost']) : 
                            (isset($item['unit_price']) ? floatval($item['unit_price']) : 0);
                            
                $item_total = $quantity * $unit_cost;
                $data['total_amount'] += $item_total;
            }
        }
    }

    // Extract data from the JSON object
    $po_no = $data['po_no'];
    $supplier_name = $data['supplier_name']; 
    $po_date = $data['po_date'];
    $total_amount = $data['total_amount'];
    
    // Extract optional fields with defaults
    $ref_no = isset($data['ref_no']) ? $data['ref_no'] : '';
    $mode_of_procurement = isset($data['mode_of_procurement']) ? $data['mode_of_procurement'] : '';
    $pr_no = isset($data['pr_no']) ? $data['pr_no'] : '';
    $pr_date = isset($data['pr_date']) ? $data['pr_date'] : '';
    $place_of_delivery = isset($data['place_of_delivery']) ? $data['place_of_delivery'] : '';
    $delivery_date = isset($data['delivery_date']) ? $data['delivery_date'] : '';
    $payment_term = isset($data['payment_term']) ? $data['payment_term'] : '';
    $delivery_term = isset($data['delivery_term']) ? $data['delivery_term'] : '';
    $obligation_request_no = isset($data['obligation_request_no']) ? $data['obligation_request_no'] : '';
    $obligation_amount = isset($data['obligation_amount']) ? floatval($data['obligation_amount']) : 0;
    
    // Extract supplier contact details
    $supplier_address = isset($data['supplier_address']) ? $data['supplier_address'] : 
                      (isset($data['address']) ? $data['address'] : '');
    
    $supplier_email = isset($data['supplier_email']) ? $data['supplier_email'] : 
                     (isset($data['email']) ? $data['email'] : '');
    
    $supplier_tel = isset($data['supplier_tel']) ? $data['supplier_tel'] : 
                   (isset($data['tel']) ? $data['tel'] : 
                   (isset($data['telephone']) ? $data['telephone'] : ''));
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if PO already exists
    $checkStmt = $conn->prepare("SELECT po_id FROM purchase_orders WHERE po_no = ?");
    $checkStmt->bind_param("s", $po_no);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    $isUpdate = false;
    $poId = 0;
    
    if ($checkResult->num_rows > 0) {
        // PO exists - update it
        $row = $checkResult->fetch_assoc();
        $poId = $row['po_id'];
        $isUpdate = true;
        
        $updateStmt = $conn->prepare("UPDATE purchase_orders SET 
            ref_no = ?, 
            supplier_name = ?, 
            supplier_address = ?,
            supplier_email = ?,
            supplier_tel = ?,
            po_date = ?, 
            mode_of_procurement = ?,
            pr_no = ?, 
            pr_date = ?, 
            place_of_delivery = ?, 
            delivery_date = ?,
            payment_term = ?, 
            delivery_term = ?, 
            obligation_request_no = ?,
            obligation_amount = ?, 
            total_amount = ?
            WHERE po_id = ?");
            
        $updateStmt->bind_param(
            "ssssssssssssssddi",
            $ref_no,
            $supplier_name,
            $supplier_address,
            $supplier_email,
            $supplier_tel,
            $po_date,
            $mode_of_procurement,
            $pr_no,
            $pr_date,
            $place_of_delivery,
            $delivery_date,
            $payment_term,
            $delivery_term,
            $obligation_request_no,
            $obligation_amount,
            $total_amount,
            $poId
        );
        $updateStmt->execute();
        
        // Delete existing items to replace with new ones
        $deleteItemsStmt = $conn->prepare("DELETE FROM po_items WHERE po_id = ?");
        $deleteItemsStmt->bind_param("i", $poId);
        $deleteItemsStmt->execute();
    } else {
        // Insert new PO
        try {
            $insertStmt = $conn->prepare("INSERT INTO purchase_orders (
                po_no, ref_no, supplier_name, supplier_address, supplier_email, supplier_tel,
                po_date, mode_of_procurement, pr_no, pr_date, place_of_delivery, delivery_date,
                payment_term, delivery_term, obligation_request_no, obligation_amount, total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insertStmt->bind_param(
                "sssssssssssssssdd",
                $po_no,
                $ref_no,
                $supplier_name,
                $supplier_address,
                $supplier_email,
                $supplier_tel,
                $po_date,
                $mode_of_procurement,
                $pr_no,
                $pr_date,
                $place_of_delivery,
                $delivery_date,
                $payment_term,
                $delivery_term,
                $obligation_request_no,
                $obligation_amount,
                $total_amount
            );
            
            // If insert fails due to duplicate key, try to regenerate a unique PO number
            if (!$insertStmt->execute()) {
                if ($conn->errno === 1062) { // Duplicate entry error code
                    // Generate a unique PO number by adding a suffix
                    $originalPoNo = $po_no;
                    $suffix = 1;
                    $maxAttempts = 5; // Limit attempts to prevent infinite loop
                    $attemptCount = 0;
                    
                    do {
                        $attemptCount++;
                        $newPoNo = $originalPoNo . '-' . $suffix;
                        $checkDupStmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_orders WHERE po_no = ?");
                        $checkDupStmt->bind_param("s", $newPoNo);
                        $checkDupStmt->execute();
                        $dupResult = $checkDupStmt->get_result();
                        $dupRow = $dupResult->fetch_assoc();
                        $exists = ($dupRow['count'] > 0);
                        $checkDupStmt->close();
                        
                        if (!$exists) {
                            $po_no = $newPoNo;
                            error_log("Generated unique PO number: $newPoNo on attempt $attemptCount");
                            break;
                        }
                        
                        $suffix++;
                    } while ($attemptCount < $maxAttempts); // Limited attempts
                    
                    if ($attemptCount >= $maxAttempts) {
                        // If we've reached max attempts, generate a truly unique timestamp-based number
                        $timestamp = time();
                        $random = mt_rand(1000, 9999);
                        $po_no = $originalPoNo . '-' . $timestamp . $random;
                        error_log("Generated timestamp-based PO number: $po_no after max attempts");
                    }
                    
                    // Try the insert again with the new PO number
                    $insertStmt->close();
                    $insertStmt = $conn->prepare("INSERT INTO purchase_orders (
                        po_no, ref_no, supplier_name, supplier_address, supplier_email, supplier_tel,
                        po_date, mode_of_procurement, pr_no, pr_date, place_of_delivery, delivery_date,
                        payment_term, delivery_term, obligation_request_no, obligation_amount, total_amount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $insertStmt->bind_param(
                        "sssssssssssssssdd",
                        $po_no,
                        $ref_no,
                        $supplier_name,
                        $supplier_address,
                        $supplier_email,
                        $supplier_tel,
                        $po_date,
                        $mode_of_procurement,
                        $pr_no,
                        $pr_date,
                        $place_of_delivery,
                        $delivery_date,
                        $payment_term,
                        $delivery_term,
                        $obligation_request_no,
                        $obligation_amount,
                        $total_amount
                    );
                    
                    // Try the insert again, if it fails again, we'll catch it in the outer catch blocks
                    if (!$insertStmt->execute()) {
                        throw new Exception("Failed to insert PO with the regenerated number: " . $insertStmt->error);
                    }
                } else {
                    // Not a duplicate error, re-throw
                    throw new Exception("Failed to insert PO: " . $insertStmt->error);
                }
            }
            
            $poId = $insertStmt->insert_id;
            $insertStmt->close();
        } catch (Exception $e) {
            // Throw up to outer catch block
            throw $e;
        }
    }

    // Now insert the items - use a transaction to ensure all items are added or none
    // Prepare the item insert statement with all required fields
    $itemStmt = $conn->prepare("INSERT INTO po_items (
        po_id, item_name, description, unit, quantity, unit_cost
    ) VALUES (?, ?, ?, ?, ?, ?)");

    // Normalize field names and handle items properly
    foreach ($data['items'] as $item) {
        $itemName = isset($item['item_name']) ? $item['item_name'] : 
                  (isset($item['name']) ? $item['name'] : 'Unnamed Item');
        
        $description = isset($item['description']) ? $item['description'] : 
                      (isset($item['item_description']) ? $item['item_description'] : '');
        
        $unit = isset($item['unit']) ? $item['unit'] : 'SET';
        
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 
                   (isset($item['qty']) ? intval($item['qty']) : 1);
                   
        $unit_cost = isset($item['unit_cost']) ? floatval($item['unit_cost']) : 
                    (isset($item['unit_price']) ? floatval($item['unit_price']) : 0);
        
        // We don't need to insert amount/total_cost as it's auto-calculated in the database
        // Log before inserting
        error_log("Adding PO item: $itemName, Unit: $unit, Qty: $quantity, Cost: $unit_cost");
        
        $itemStmt->bind_param(
            "isssid",
            $poId,
            $itemName,
            $description,
            $unit,
            $quantity,
            $unit_cost
        );
        
        if (!$itemStmt->execute()) {
            error_log("Error adding PO item: " . $itemStmt->error);
            throw new Exception("Failed to add item: " . $itemStmt->error);
        }
    }

    $itemStmt->close();

    // Commit transaction
    $conn->commit();

    $response = [
        'success' => true,
        'message' => $isUpdate ? 'Purchase Order updated successfully' : 'Purchase Order saved successfully',
        'po_id' => $poId,
        'is_update' => $isUpdate
    ];

} catch (mysqli_sql_exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
    error_log("Database Error saving PO: " . $e->getMessage());

} catch (Exception $e) {
    // Handle other exceptions
    if (isset($conn)) {
        $conn->rollback();
    }
    
    $response = [
        'success' => false,
        'message' => 'Error saving Purchase Order: ' . $e->getMessage()
    ];
    error_log("Error saving PO: " . $e->getMessage());
}

// Close connection
if (isset($conn)) {
    $conn->close();
}

// Ensure we only output JSON
echo json_encode($response);
exit;