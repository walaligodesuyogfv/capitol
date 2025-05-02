<?php
/**
 * Inventory ML Tracking Endpoint
 * 
 * This file handles tracking inventory items for ML predictions
 */

// Allow cross-origin requests from same domain
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Function to execute Python script
function trackInventoryItem($itemData) {
    // Create a temporary JSON file with the item data
    $tempFile = tempnam(sys_get_temp_dir(), 'inventory_');
    file_put_contents($tempFile, json_encode($itemData));
    
    // Execute the Python script to track the item
    $pythonScript = "DataAnalytics/track_inventory.py";
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($tempFile);
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Clean up the temporary file
    unlink($tempFile);
    
    // Process the result
    $result = implode("\n", $output);
    $jsonResult = json_decode($result, true);
    
    if ($returnCode !== 0 || !$jsonResult) {
        return [
            'success' => false,
            'message' => 'Failed to track inventory item',
            'error' => $result
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Inventory item tracked successfully',
        'data' => $jsonResult
    ];
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Check if data is valid JSON
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    // Track the inventory item
    $result = trackInventoryItem($input);
    
    // Return the result
    echo json_encode($result);
} else {
    // Method not allowed
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
} 