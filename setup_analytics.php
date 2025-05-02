<?php
/**
 * Analytics Dashboard Setup
 * This script sets up the required dependencies for the analytics dashboard
 */

// Check if the user has submitted the form
$setupRun = isset($_POST['setup_analytics']);
$setupOutput = "";
$setupSuccess = false;

if ($setupRun) {
    // Path to Python setup script
    $setupScript = __DIR__ . '/DataAnalytics/setup.py';
    
    // Check if the script exists
    if (!file_exists($setupScript)) {
        $setupOutput = "Error: Setup script not found at $setupScript";
    } else {
        // Run the setup script
        $command = "python " . escapeshellarg($setupScript);
        
        // Execute the command and capture the output
        $outputLines = [];
        exec($command, $outputLines, $returnCode);
        $setupOutput = implode("\n", $outputLines);
        
        // Check if the setup was successful
        $setupSuccess = ($returnCode === 0);
    }
}

// Function to check Python installation
function checkPythonInstallation() {
    exec('python --version 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        return false;
    }
    return $output[0] ?? false;
}

// Check Python installation
$pythonVersion = checkPythonInstallation();

// Check if DataAnalytics directory exists
$analyticsDir = __DIR__ . '/DataAnalytics';
$analyticsDirExists = is_dir($analyticsDir);

// Check if assets directory exists
$assetsDir = __DIR__ . '/assets/charts';
$assetsDirExists = is_dir($assetsDir);

// Check if required Python files exist
$analyticsFileExists = file_exists($analyticsDir . '/get_analytics_data.py');
$enhancedAnalyticsFileExists = file_exists($analyticsDir . '/enhanced_analytics.py');
$requirementsFileExists = file_exists($analyticsDir . '/requirements.txt');
$setupFileExists = file_exists($analyticsDir . '/setup.py');

// Get operating system
$os = PHP_OS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .setup-card {
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .header-icon {
            font-size: 3rem;
            color: #5470c6;
        }
        .status-icon {
            font-size: 1.2rem;
        }
        .status-success {
            color: #28a745;
        }
        .status-warning {
            color: #ffc107;
        }
        .status-danger {
            color: #dc3545;
        }
        .output-box {
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            background-color: #212529;
            color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card setup-card">
            <div class="card-header bg-white py-3 text-center">
                <div class="header-icon mb-2">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h4 class="mb-0">Analytics Dashboard Setup</h4>
                <p class="text-muted small mb-0">
                    This utility will set up the required dependencies for the analytics dashboard
                </p>
            </div>
            <div class="card-body p-4">
                <?php if ($setupRun && $setupSuccess): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Setup Completed Successfully!</strong>
                    <p class="mb-0 small">The analytics dashboard is now ready to use.</p>
                </div>
                <?php elseif ($setupRun && !$setupSuccess): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <strong>Setup Failed!</strong>
                    <p class="mb-0 small">Please check the output below for details.</p>
                </div>
                <?php endif; ?>
                
                <h5 class="mb-3">System Requirements</h5>
                <div class="list-group mb-4">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Python Installation</strong>
                            <p class="mb-0 small text-muted">Python is required to run the analytics scripts</p>
                        </div>
                        <div>
                            <?php if ($pythonVersion): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                <?php echo htmlspecialchars($pythonVersion); ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Not Installed
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Operating System</strong>
                            <p class="mb-0 small text-muted">Supported: Windows, Linux, macOS</p>
                        </div>
                        <div>
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($os); ?>
                            </span>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>DataAnalytics Directory</strong>
                            <p class="mb-0 small text-muted">Contains the analytics scripts</p>
                        </div>
                        <div>
                            <?php if ($analyticsDirExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Missing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Assets Directory</strong>
                            <p class="mb-0 small text-muted">For storing generated charts</p>
                        </div>
                        <div>
                            <?php if ($assetsDirExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning d-flex align-items-center">
                                <i class="bi bi-exclamation-circle-fill me-1 status-icon"></i>
                                Will be created
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Required Files</h5>
                <div class="list-group mb-4">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>get_analytics_data.py</strong>
                            <p class="mb-0 small text-muted">Main analytics data generator</p>
                        </div>
                        <div>
                            <?php if ($analyticsFileExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Missing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>enhanced_analytics.py</strong>
                            <p class="mb-0 small text-muted">Enhanced analytics with ML predictions</p>
                        </div>
                        <div>
                            <?php if ($enhancedAnalyticsFileExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Missing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>requirements.txt</strong>
                            <p class="mb-0 small text-muted">Python dependencies list</p>
                        </div>
                        <div>
                            <?php if ($requirementsFileExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Missing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>setup.py</strong>
                            <p class="mb-0 small text-muted">Python setup script</p>
                        </div>
                        <div>
                            <?php if ($setupFileExists): ?>
                            <span class="badge bg-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-1 status-icon"></i>
                                Found
                            </span>
                            <?php else: ?>
                            <span class="badge bg-danger d-flex align-items-center">
                                <i class="bi bi-x-circle-fill me-1 status-icon"></i>
                                Missing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($setupRun): ?>
                <h5 class="mb-3">Setup Output</h5>
                <div class="output-box mb-4">
                    <?php echo htmlspecialchars($setupOutput); ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                        <a href="admindashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        
                        <?php if ($pythonVersion && $analyticsDirExists && $requirementsFileExists && $setupFileExists): ?>
                        <button type="submit" name="setup_analytics" class="btn btn-primary">
                            <i class="bi bi-gear-fill"></i> Run Setup
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-primary" disabled>
                            <i class="bi bi-gear-fill"></i> Run Setup
                        </button>
                        <div class="form-text text-danger mt-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Missing required components. Please check the requirements above.
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="small text-muted text-center">
                    Analytics Dashboard Setup | ICTD Inventory Management System
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 