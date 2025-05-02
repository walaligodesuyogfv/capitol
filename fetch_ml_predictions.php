<?php
/**
 * ML Predictions API
 * This script calls the Python ML prediction script and returns the results
 */

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Define path to Python script
$pythonScript = __DIR__ . '/DataAnalytics/inventory_predictions.py';

// Check if we need to refresh the cache
$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';

// Function to execute the Python script
function executePythonScript($scriptPath) {
    // Attempt different Python commands that might work in various environments
    $commands = [
        "python " . escapeshellarg($scriptPath),
        "python3 " . escapeshellarg($scriptPath),
        "py " . escapeshellarg($scriptPath)
    ];
    
    $output = null;
    
    // Try each command until one works
    foreach ($commands as $command) {
        // Execute the command and capture output (including errors)
        $result = shell_exec($command . " 2>&1");
        
        if ($result !== null) {
            // Check if the output is valid JSON (will be after the Content-Type line)
            $jsonStart = strpos($result, '{');
            if ($jsonStart !== false) {
                $jsonContent = substr($result, $jsonStart);
                $jsonData = json_decode($jsonContent, true);
                
                if ($jsonData !== null) {
                    return $jsonContent; // Return just the JSON part
                }
            }
        }
    }
    
    // If all commands failed, return an error
    return json_encode([
        'success' => false,
        'error' => 'Failed to execute Python script or get valid JSON response',
        'raw_output' => $result ?? 'No output'
    ]);
}

// Check if file exists
if (!file_exists($pythonScript)) {
    http_response_code(404);
    die(json_encode([
        'success' => false,
        'error' => 'Python script not found',
        'path' => $pythonScript
    ]));
}

// Get predictions from Python script
$result = executePythonScript($pythonScript);

// Check if we received valid JSON
$data = json_decode($result, true);
if ($data === null) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Invalid JSON response from Python script',
        'json_error' => json_last_error_msg(),
        'raw_output' => $result
    ]));
}

// Return the predictions
echo $result; 