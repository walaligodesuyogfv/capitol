<?php
/**
 * Inventory Prediction PHP Connector
 * This script connects the dashboard to the Python ML prediction script
 */

// Set the content type to JSON
header('Content-Type: application/json');

// Path to Python script
$pythonScript = "DataAnalytics/inventory_prediction.py";

// Check if the Python script exists
if (!file_exists($pythonScript)) {
    echo json_encode([
        'success' => false,
        'message' => 'Python script not found',
        'top_items' => [],
        'needs_attention' => [],
        'recommendations' => []
    ]);
    exit;
}

// Execute the Python script
$command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg("standard");
$output = shell_exec($command);

// If shell_exec fails, try exec
if (!$output) {
    $outputLines = [];
    exec($command, $outputLines);
    $output = implode("\n", $outputLines);
}

// Check if we got valid output
if (!$output) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to execute ML analysis script',
        'top_items' => [],
        'needs_attention' => [],
        'recommendations' => []
    ]);
    exit;
}

// Try to decode the JSON output
$data = json_decode($output, true);

// Check if the JSON is valid
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid output from ML analysis script: ' . json_last_error_msg(),
        'raw_output' => substr($output, 0, 1000), // Include part of the raw output for debugging
        'top_items' => [],
        'needs_attention' => [],
        'recommendations' => []
    ]);
    exit;
}

// Pass through the Python script's output directly
echo $output; 