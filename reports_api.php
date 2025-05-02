<?php
/**
 * Reports API Handler
 * 
 * This script serves as a bridge between the JavaScript frontend and Python analytics backend.
 * It receives requests from the frontend, calls the appropriate Python script,
 * and returns the results as JSON.
 */

// Allow cross-origin requests (for local development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database connection if needed
if (file_exists('config/db.php')) {
    include 'config/db.php';
}

// Function to run Python script and get results
function runPythonScript($scriptPath, $args = []) {
    $command = "python " . escapeshellarg($scriptPath);
    
    // Add arguments to the command
    foreach ($args as $arg) {
        $command .= " " . escapeshellarg($arg);
    }
    
    // Execute the command
    $output = shell_exec($command);
    
    // If shell_exec fails, try exec
    if (!$output) {
        $outputLines = [];
        exec($command, $outputLines);
        $output = implode("\n", $outputLines);
    }
    
    // Try to parse the output as JSON
    $result = json_decode($output, true);
    
    // If JSON parsing fails, return an error
    if (!$result || json_last_error() !== JSON_ERROR_NONE) {
        // Log the error for debugging
        error_log("Failed to parse Python output: " . json_last_error_msg());
        error_log("Raw output: " . $output);
        
        return [
            'success' => false,
            'error' => 'Failed to parse Python output',
            'raw_output' => $output
        ];
    }
    
    return $result;
}

// Check request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    // Handle preflight requests
    exit(0);
} else if ($method === 'POST') {
    // Get the request body
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);
    
    // Check if JSON decoding was successful
    if (!$requestData) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON in request body'
        ]);
        exit;
    }
    
    // Extract parameters from the request
    $action = isset($requestData['action']) ? $requestData['action'] : 'summary';
    $reportType = isset($requestData['report_type']) ? $requestData['report_type'] : 'summary';
    $timeRange = isset($requestData['time_range']) ? $requestData['time_range'] : 'monthly';
    $predictionType = isset($requestData['prediction_type']) ? $requestData['prediction_type'] : 'future_par';
    $timeHorizon = isset($requestData['time_horizon']) ? $requestData['time_horizon'] : 6;
    $confidenceLevel = isset($requestData['confidence_level']) ? $requestData['confidence_level'] : 0.95;
    $filters = isset($requestData['filters']) ? $requestData['filters'] : [];
    
    // Path to the Python script
    $pythonScript = "DataAnalytics/get_report_analytics.py";
    
    // Prepare the arguments based on the action
    $args = [];
    
    if ($action === 'prediction') {
        $args = [
            'prediction',
            $timeRange,
            $predictionType,
            $timeHorizon,
            $confidenceLevel
        ];
    } else if ($action === 'department_summary') {
        $args = ['department_summary'];
    } else if ($action === 'monthly_trend') {
        $args = ['monthly_trend', $timeRange];
    } else if ($action === 'asset_analysis') {
        $args = ['asset_analysis'];
    } else {
        // Default summary action
        $args = ['summary'];
    }
    
    // Check if the Python script exists
    if (!file_exists($pythonScript)) {
        echo json_encode([
            'success' => false,
            'error' => 'Python script not found at: ' . $pythonScript
        ]);
        exit;
    }
    
    // Run the Python script
    $result = runPythonScript($pythonScript, $args);
    
    // Add success flag to the result
    $result['success'] = true;
    
    // Return the result as JSON
    echo json_encode($result);
    
} else if ($method === 'GET') {
    // Handle GET requests for fetching report data
    
    // Extract parameters from the query string
    $action = isset($_GET['action']) ? $_GET['action'] : 'summary';
    $reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';
    $timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'monthly';
    
    // Path to the Python script
    $pythonScript = "DataAnalytics/get_report_analytics.py";
    
    // Prepare the arguments based on the action
    $args = [];
    
    if ($action === 'department_summary') {
        $args = ['department_summary'];
    } else if ($action === 'monthly_trend') {
        $args = ['monthly_trend', $timeRange];
    } else if ($action === 'asset_analysis') {
        $args = ['asset_analysis'];
    } else {
        // Default summary action
        $args = ['summary'];
    }
    
    // Check if the Python script exists
    if (!file_exists($pythonScript)) {
        // If Python script does not exist, use mock data
        $mockData = getMockData($action, $timeRange);
        $mockData['success'] = true;
        $mockData['mock'] = true;
        
        echo json_encode($mockData);
        exit;
    }
    
    // Run the Python script
    $result = runPythonScript($pythonScript, $args);
    
    // Add success flag to the result
    $result['success'] = true;
    
    // Return the result as JSON
    echo json_encode($result);
    
} else {
    // Method not allowed
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}

// Function to generate mock data when Python script is not available
function getMockData($action, $timeRange) {
    $result = [];
    
    if ($action === 'department_summary') {
        $result = [
            'department_summary' => [
                'labels' => ['IT', 'HR', 'Finance', 'Operations', 'Marketing'],
                'counts' => [75, 35, 45, 60, 25],
                'values' => [250000, 85000, 120000, 180000, 60000]
            ]
        ];
    } else if ($action === 'monthly_trend') {
        if ($timeRange === 'monthly') {
            $labels = ['Jan 2023', 'Feb 2023', 'Mar 2023', 'Apr 2023', 'May 2023', 'Jun 2023', 
                      'Jul 2023', 'Aug 2023', 'Sep 2023', 'Oct 2023', 'Nov 2023', 'Dec 2023'];
        } else if ($timeRange === 'quarterly') {
            $labels = ['Q1 2022', 'Q2 2022', 'Q3 2022', 'Q4 2022', 'Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023'];
        } else {
            $labels = ['2019', '2020', '2021', '2022', '2023'];
        }
        
        $result = [
            'par_trend' => [
                'labels' => $labels,
                'values' => array_map(function() { return rand(15000, 45000); }, $labels)
            ],
            'po_trend' => [
                'labels' => $labels,
                'values' => array_map(function() { return rand(25000, 65000); }, $labels)
            ]
        ];
    } else if ($action === 'asset_analysis') {
        $dates = [];
        $counts = [];
        $values = [];
        
        // Generate last 12 months of data
        for ($i = 0; $i < 12; $i++) {
            $date = date('Y-m-d', strtotime("-$i months"));
            $dates[] = $date;
            $counts[] = 120 + $i * 5 + rand(-3, 3);
            $values[] = 280000 + $i * 15000 + rand(-5000, 5000);
        }
        
        $result = [
            'dates' => array_reverse($dates),
            'asset_counts' => array_reverse($counts),
            'asset_values' => array_reverse($values)
        ];
    } else {
        // Default summary action
        $result = [
            'par_total' => 520000,
            'po_total' => 780000,
            'asset_count_total' => 180,
            'asset_value_total' => 450000,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
    
    return $result;
} 