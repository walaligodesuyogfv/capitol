<?php
/**
 * PHP script to fetch and display analytics data from Python script
 */

// Function to run Python script and get JSON data
function getAnalyticsData($period = 'monthly') {
    $period = escapeshellarg($period);
    $pythonScript = 'python_scripts/get_analytics_data.py';
    
    // Check if script exists
    if (!file_exists($pythonScript)) {
        return json_encode([
            'error' => true,
            'message' => 'Python script not found'
        ]);
    }
    
    // Try multiple Python command variations to handle different environments
    $commands = [
        "python $pythonScript $period",
        "python3 $pythonScript $period",
        "py $pythonScript $period"
    ];
    
    $output = null;
    
    // Try each command until one works
    foreach ($commands as $command) {
        $output = shell_exec($command . " 2>&1"); // Capture stderr too
        
        if ($output !== null) {
            // Check if the output is valid JSON
            $decoded = json_decode($output, true);
            if ($decoded !== null) {
                break; // Found working command with valid JSON
            }
        }
    }
    
    if ($output === null) {
        return json_encode([
            'error' => true,
            'message' => 'Failed to execute Python script'
        ]);
    }
    
    // Verify JSON is valid
    $decoded = json_decode($output, true);
    if ($decoded === null) {
        return json_encode([
            'error' => true,
            'message' => 'Invalid JSON returned from Python script',
            'raw_output' => $output,
            'json_error' => json_last_error_msg()
        ]);
    }
    
    // Just return the data directly as it's already correctly structured
    // Add a title field for the chart
    $decoded['title'] = ucfirst($period) . ' Analytics Report';
    
    return json_encode($decoded);
}

// Handle request
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';

// Validate period
if (!in_array($period, ['weekly', 'monthly', 'quarterly'])) {
    $period = 'monthly'; // Default to monthly if invalid
}

$jsonData = getAnalyticsData($period);

// Set content type to JSON
header('Content-Type: application/json');
echo $jsonData;
?> 