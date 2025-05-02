<?php
/**
 * Generate Predictions Script
 * This script runs the Python prediction script and returns the results as JSON
 */

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Function to execute Python script
function executePythonPrediction() {
    // Path to Python script
    $pythonScript = __DIR__ . '/DataAnalytics/inventory_predictions.py';
    
    // Check if script exists
    if (!file_exists($pythonScript)) {
        return [
            'success' => false,
            'message' => 'Python script not found',
            'path' => $pythonScript
        ];
    }
    
    // Try different Python executables
    $pythonCommands = ['python', 'python3', 'py'];
    $result = null;
    $error = null;
    
    foreach ($pythonCommands as $cmd) {
        try {
            // Build command with error capture
            $command = sprintf('%s %s 2>&1', $cmd, escapeshellarg($pythonScript));
            
            // Execute the command
            $output = shell_exec($command);
            
            if ($output) {
                // Extract JSON from output (after Content-Type header)
                $jsonStart = strpos($output, '{');
                if ($jsonStart !== false) {
                    $jsonContent = substr($output, $jsonStart);
                    $jsonData = json_decode($jsonContent, true);
                    
                    if ($jsonData) {
                        return [
                            'success' => true,
                            'data' => $jsonData,
                            'command' => $command
                        ];
                    }
                }
                
                // If we couldn't parse JSON, store output as error
                $error = $output;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // If we got here, all commands failed
    return [
        'success' => false,
        'message' => 'Failed to execute Python script',
        'error' => $error
    ];
}

// Check for force parameter
$forceRun = isset($_GET['force']) && $_GET['force'] === 'true';

// Check if this is a direct browser request or AJAX call
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Execute the prediction
$result = executePythonPrediction();

// Add timestamp
$result['timestamp'] = date('Y-m-d H:i:s');

// Return the result
echo json_encode($result, JSON_PRETTY_PRINT);
?> 