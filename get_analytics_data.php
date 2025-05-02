<?php
/**
 * Get Analytics Data
 * This script fetches analytics data from Python for the dashboard
 */

// Set error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the period from request or use monthly as default
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

// Check if we need to force an update
$forceUpdate = isset($_GET['update']) && $_GET['update'] === 'true';

// Validate period to prevent command injection
$validPeriods = ['weekly', 'monthly', 'quarterly'];
if (!in_array($period, $validPeriods)) {
    $period = 'monthly';
}

// Set content type to JSON
header('Content-Type: application/json');

// Python script path
$pythonScript = __DIR__ . '/DataAnalytics/get_analytics_data.py';

// Check if Python script exists
if (!file_exists($pythonScript)) {
    echo json_encode([
        'success' => false,
        'message' => 'Analytics script not found',
        'path' => $pythonScript
    ]);
    exit;
}

// Build command - add force update flag if needed
$command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($period);
if ($forceUpdate) {
    $command .= " update";
}

// Execute Python script
$output = shell_exec($command);

// If shell_exec fails, try exec
if (!$output) {
    $outputLines = [];
    exec($command, $outputLines);
    $output = implode("\n", $outputLines);
}

// Check if we got valid JSON output
$data = json_decode($output, true);
if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to parse analytics data',
        'error' => json_last_error_msg(),
        'raw_output' => $output
    ]);
    exit;
}

// Add success flag to the data
$data['success'] = true;

// Return the data
echo json_encode($data); 