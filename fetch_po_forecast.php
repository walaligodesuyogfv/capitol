<?php
/**
 * Fetch PO Forecast
 * This script fetches PO forecast data from Python for the dashboard
 */

// Set error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Attempt to find the correct Python executable and script
$pythonScript = "DataAnalytics/po_forecast.py";

if (file_exists($pythonScript)) {
    // Try different Python commands in case the system has multiple versions
    $commands = [
        "python " . escapeshellarg($pythonScript) . " 2>&1",
        "python3 " . escapeshellarg($pythonScript) . " 2>&1",
        "py " . escapeshellarg($pythonScript) . " 2>&1"
    ];
    
    $output = null;
    foreach ($commands as $command) {
        $output = shell_exec($command);
        if ($output) break;
    }
    
    // If shell_exec fails, try exec
    if (!$output) {
        $outputLines = [];
        exec($commands[0], $outputLines);
        $output = implode("\n", $outputLines);
    }
    
    // Check if we got valid JSON output
    $data = json_decode($output, true);
    if ($data !== null && json_last_error() === JSON_ERROR_NONE) {
        // Return the data with success flag
        $data['success'] = true;
        echo json_encode($data);
    } else {
        // Return error with raw output for debugging
        echo json_encode([
            'success' => false,
            'message' => 'Failed to parse PO forecast data',
            'error' => json_last_error_msg(),
            'raw_output' => $output
        ]);
    }
} else {
    // Script not found
    echo json_encode([
        'success' => false,
        'message' => 'PO forecast script not found',
        'path' => $pythonScript
    ]);
}
?> 