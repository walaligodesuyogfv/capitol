<?php
/**
 * ML Predictions Handler
 * This script calls the Python ML prediction script and returns the results as JSON
 */

// Set headers to allow AJAX calls and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Define the path to the Python script
$pythonScript = "DataAnalytics/get_analytics_data.py";
$prediction_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Validate the prediction_type parameter
$valid_types = ['all', 'maintenance', 'inventory', 'new_items'];
if (!in_array($prediction_type, $valid_types)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid prediction type']));
}

// Execute the Python script with appropriate parameters
if (file_exists($pythonScript)) {
    // Define the command to run
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg("monthly");
    
    // Execute the command
    $output = shell_exec($command);
    
    // If shell_exec doesn't work, try exec
    if (!$output) {
        $outputLines = [];
        exec($command, $outputLines);
        $output = implode("\n", $outputLines);
    }
    
    // Parse the JSON output
    if ($output) {
        $data = json_decode($output, true);
        
        // Check if we have valid data
        if (!$data || json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            die(json_encode(['error' => 'Error parsing prediction data', 'raw_output' => $output]));
        }
        
        // Filter the data based on the prediction_type
        $response = [];
        if ($prediction_type === 'all') {
            // Return all ML prediction data
            $response = isset($data['ml_predictions']) ? $data['ml_predictions'] : [];
        } else if ($prediction_type === 'maintenance') {
            // Return only maintenance predictions
            $response = isset($data['ml_predictions']['maintenance_predictions']) ? 
                ['maintenance_predictions' => $data['ml_predictions']['maintenance_predictions']] : [];
        } else if ($prediction_type === 'inventory') {
            // Return only inventory suggestions
            $response = isset($data['ml_predictions']['inventory_suggestions']) ? 
                ['inventory_suggestions' => $data['ml_predictions']['inventory_suggestions']] : [];
        } else if ($prediction_type === 'new_items') {
            // Return only new inventory items
            $response = isset($data['ml_predictions']['new_inventory_items']) ? 
                ['new_inventory_items' => $data['ml_predictions']['new_inventory_items']] : [];
        }
        
        // Add timestamp for tracking
        $response['timestamp'] = time();
        
        // Return the filtered data
        echo json_encode($response);
    } else {
        http_response_code(500);
        die(json_encode(['error' => 'Error executing prediction script']));
    }
} else {
    http_response_code(404);
    die(json_encode(['error' => 'Python script not found']));
} 