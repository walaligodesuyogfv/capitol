<?php
// Test script to verify that PHP can call the Python reports script

header('Content-Type: application/json');

// Path to the Python script
$pythonScript = "DataAnalytics/get_report_analytics.py";

// Check if the Python script exists
if (!file_exists($pythonScript)) {
    echo json_encode([
        'success' => false,
        'error' => 'Python script not found at: ' . $pythonScript
    ]);
    exit;
}

// Test calling the Python script with simple parameters
$command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg("summary");
$output = shell_exec($command);

// If shell_exec fails, try exec
if (!$output) {
    $outputLines = [];
    exec($command, $outputLines);
    $output = implode("\n", $outputLines);
}

// Try to parse the output as JSON
$result = json_decode($output, true);

// If JSON parsing fails, return raw output
if (!$result || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to parse Python output: ' . json_last_error_msg(),
        'raw_output' => $output
    ]);
    exit;
}

// Add metadata to the result
$result['success'] = true;
$result['test_time'] = date('Y-m-d H:i:s');
$result['php_version'] = PHP_VERSION;
$result['os'] = PHP_OS;

// Return the result as JSON
echo json_encode($result, JSON_PRETTY_PRINT);
?> 