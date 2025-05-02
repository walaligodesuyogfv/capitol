<?php
/**
 * Track ML Data
 * This script tracks inventory, PO, and PAR data for machine learning prediction
 */

// Function to track inventory data for ML predictions
function trackInventoryForML($inventoryData) {
    $pythonScript = "DataAnalytics/track_inventory.py";
    
    if (!file_exists($pythonScript)) {
        return [
            'success' => false,
            'message' => 'Python script not found',
            'path' => $pythonScript
        ];
    }
    
    // Create temporary file with JSON data
    $tempFile = tempnam(sys_get_temp_dir(), 'inventory_data');
    file_put_contents($tempFile, json_encode($inventoryData));
    
    // Call Python script with the data file
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($tempFile);
    $output = shell_exec($command);
    
    // Clean up temporary file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return [
        'success' => true,
        'message' => 'Inventory data tracked for ML',
        'output' => $output
    ];
}

// Function to track PO data for ML predictions
function trackPOForML($poData) {
    $pythonScript = "DataAnalytics/po_forecast.py";
    
    if (!file_exists($pythonScript)) {
        return [
            'success' => false,
            'message' => 'Python script not found',
            'path' => $pythonScript
        ];
    }
    
    // Create temporary file with JSON data
    $tempFile = tempnam(sys_get_temp_dir(), 'po_data');
    file_put_contents($tempFile, json_encode($poData));
    
    // Call Python script with the data file
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($tempFile);
    $output = shell_exec($command);
    
    // Clean up temporary file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return [
        'success' => true,
        'message' => 'PO data tracked for ML',
        'output' => $output
    ];
}

// Function to track PAR data for ML predictions
function trackPARForML($parData) {
    $pythonScript = "DataAnalytics/par_forecast.py";
    
    if (!file_exists($pythonScript)) {
        return [
            'success' => false,
            'message' => 'Python script not found',
            'path' => $pythonScript
        ];
    }
    
    // Create temporary file with JSON data
    $tempFile = tempnam(sys_get_temp_dir(), 'par_data');
    file_put_contents($tempFile, json_encode($parData));
    
    // Call Python script with the data file
    $command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($tempFile);
    $output = shell_exec($command);
    
    // Clean up temporary file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    return [
        'success' => true,
        'message' => 'PAR data tracked for ML',
        'output' => $output
    ];
}

// Handle AJAX requests if this file is directly accessed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if (isset($data['type']) && isset($data['data'])) {
        switch ($data['type']) {
            case 'inventory':
                $response = trackInventoryForML($data['data']);
                break;
            case 'po':
                $response = trackPOForML($data['data']);
                break;
            case 'par':
                $response = trackPARForML($data['data']);
                break;
            default:
                $response = ['success' => false, 'message' => 'Invalid type'];
        }
    }
    
    echo json_encode($response);
    exit;
}
?> 