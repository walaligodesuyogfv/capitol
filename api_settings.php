<?php
// API endpoint for settings management

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection file not found']);
    exit;
}

// Connect to database
$conn = getConnection();
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to connect to database']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Handle GET requests - retrieve settings
        handleGetRequest();
        break;
    case 'POST':
        // Handle POST requests - create or update settings
        handlePostRequest();
        break;
    default:
        // Method not allowed
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * Handle GET requests to retrieve settings
 */
function handleGetRequest() {
    global $conn;
    
    // Check what type of settings to get
    $settingType = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    // Prepare the SQL based on setting type
    switch ($settingType) {
        case 'roles':
            // Get role settings
            $sql = "SELECT * FROM roles";
            break;
        case 'permissions':
            // Get permissions, optionally filtered by role
            $role = isset($_GET['role']) ? $_GET['role'] : null;
            if ($role) {
                $sql = "SELECT * FROM permissions WHERE role = '$role'";
            } else {
                $sql = "SELECT * FROM permissions";
            }
            break;
        case 'thresholds':
            // Get inventory threshold settings
            $sql = "SELECT i.item_id, i.item_name, COUNT(DISTINCT i.id) as current_stock, 
                    COALESCE(it.threshold_value, gs.default_threshold) as threshold,
                    it.auto_order FROM inventory i
                    LEFT JOIN inventory_thresholds it ON i.item_name = it.item_name
                    LEFT JOIN general_settings gs ON gs.setting_key = 'default_threshold'
                    GROUP BY i.item_name";
            break;
        case 'general':
            // Get general settings
            $sql = "SELECT setting_key, setting_value FROM general_settings";
            break;
        default:
            // Get all settings
            $response = [
                'success' => true,
                'general' => getGeneralSettings(),
                'roles' => getRoles(),
                'thresholds' => getThresholds()
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            return;
    }
    
    // Execute query
    $result = $conn->query($sql);
    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to retrieve settings',
            'sql_error' => $conn->error
        ]);
        return;
    }
    
    // Prepare the response
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Return the response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
}

/**
 * Handle POST requests to create or update settings
 */
function handlePostRequest() {
    global $conn;
    
    // Get the raw POST data
    $rawData = file_get_contents('php://input');
    
    // Parse the JSON data
    $data = json_decode($rawData, true);
    
    if (!$data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        return;
    }
    
    // Determine what type of settings we're saving
    $settingType = isset($_GET['type']) ? $_GET['type'] : null;
    
    if (!$settingType) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Setting type not specified']);
        return;
    }
    
    // Process based on setting type
    switch ($settingType) {
        case 'roles':
            saveRoles($data);
            break;
        case 'permissions':
            savePermissions($data);
            break;
        case 'thresholds':
            saveThresholds($data);
            break;
        case 'general':
            saveGeneralSettings($data);
            break;
        case 'all':
            // Save all settings
            $result = true;
            $errors = [];
            
            if (isset($data['general'])) {
                $generalResult = saveGeneralSettings($data['general'], false);
                if (!$generalResult['success']) {
                    $result = false;
                    $errors[] = 'General settings: ' . $generalResult['error'];
                }
            }
            
            if (isset($data['roles'])) {
                $rolesResult = saveRoles($data['roles'], false);
                if (!$rolesResult['success']) {
                    $result = false;
                    $errors[] = 'Roles: ' . $rolesResult['error'];
                }
            }
            
            if (isset($data['permissions'])) {
                $permissionsResult = savePermissions($data['permissions'], false);
                if (!$permissionsResult['success']) {
                    $result = false;
                    $errors[] = 'Permissions: ' . $permissionsResult['error'];
                }
            }
            
            if (isset($data['thresholds'])) {
                $thresholdsResult = saveThresholds($data['thresholds'], false);
                if (!$thresholdsResult['success']) {
                    $result = false;
                    $errors[] = 'Thresholds: ' . $thresholdsResult['error'];
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'errors' => $errors
            ]);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unknown setting type']);
    }
}

/**
 * Get general settings from the database
 * @return array The general settings
 */
function getGeneralSettings() {
    global $conn;
    
    $sql = "SELECT setting_key, setting_value FROM general_settings";
    $result = $conn->query($sql);
    
    if (!$result) {
        return ['error' => $conn->error];
    }
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

/**
 * Get roles from the database
 * @return array The roles
 */
function getRoles() {
    global $conn;
    
    $sql = "SELECT * FROM roles";
    $result = $conn->query($sql);
    
    if (!$result) {
        return ['error' => $conn->error];
    }
    
    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    
    return $roles;
}

/**
 * Get threshold settings from the database
 * @return array The threshold settings
 */
function getThresholds() {
    global $conn;
    
    $sql = "SELECT i.item_name, COUNT(DISTINCT i.id) as current_stock, 
            COALESCE(it.threshold_value, gs.default_threshold) as threshold,
            it.auto_order FROM inventory i
            LEFT JOIN inventory_thresholds it ON i.item_name = it.item_name
            LEFT JOIN general_settings gs ON gs.setting_key = 'default_threshold'
            GROUP BY i.item_name";
    $result = $conn->query($sql);
    
    if (!$result) {
        return ['error' => $conn->error];
    }
    
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[] = $row;
    }
    
    return $thresholds;
}

/**
 * Save roles to the database
 * @param array $data The roles data
 * @param bool $returnJson Whether to return JSON or an array
 * @return mixed The result of the operation
 */
function saveRoles($data, $returnJson = true) {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // For each role in the data
        foreach ($data as $role) {
            // Check if the role exists
            $sql = "SELECT * FROM roles WHERE role_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $role['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Role exists, update it
                $sql = "UPDATE roles SET description = ? WHERE role_name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $role['description'], $role['name']);
                $stmt->execute();
            } else {
                // Role doesn't exist, insert it
                $sql = "INSERT INTO roles (role_name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $role['name'], $role['description']);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Return error
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Save permissions to the database
 * @param array $data The permissions data
 * @param bool $returnJson Whether to return JSON or an array
 * @return mixed The result of the operation
 */
function savePermissions($data, $returnJson = true) {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get the role from the data
        $role = $data['role'];
        
        // Delete existing permissions for the role
        $sql = "DELETE FROM permissions WHERE role = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $role);
        $stmt->execute();
        
        // Insert new permissions
        foreach ($data['permissions'] as $permission) {
            $sql = "INSERT INTO permissions (role, module, action) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $role, $permission['module'], $permission['action']);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Return error
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Save threshold settings to the database
 * @param array $data The threshold settings data
 * @param bool $returnJson Whether to return JSON or an array
 * @return mixed The result of the operation
 */
function saveThresholds($data, $returnJson = true) {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Save default threshold
        if (isset($data['defaultThreshold'])) {
            $sql = "INSERT INTO general_settings (setting_key, setting_value) VALUES ('default_threshold', ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $defaultThreshold = $data['defaultThreshold'];
            $stmt->bind_param("ii", $defaultThreshold, $defaultThreshold);
            $stmt->execute();
        }
        
        // Save enable auto order setting
        if (isset($data['enableAutoOrder'])) {
            $sql = "INSERT INTO general_settings (setting_key, setting_value) VALUES ('enable_auto_order', ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $enableAutoOrder = $data['enableAutoOrder'] ? 1 : 0;
            $stmt->bind_param("ii", $enableAutoOrder, $enableAutoOrder);
            $stmt->execute();
        }
        
        // Save threshold percentage
        if (isset($data['thresholdPercentage'])) {
            $sql = "INSERT INTO general_settings (setting_key, setting_value) VALUES ('threshold_percentage', ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $thresholdPercentage = $data['thresholdPercentage'];
            $stmt->bind_param("ii", $thresholdPercentage, $thresholdPercentage);
            $stmt->execute();
        }
        
        // Save item-specific thresholds
        if (isset($data['itemThresholds'])) {
            foreach ($data['itemThresholds'] as $item) {
                // Check if the threshold exists
                $sql = "SELECT * FROM inventory_thresholds WHERE item_name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $item['itemName']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Threshold exists, update it
                    $sql = "UPDATE inventory_thresholds SET threshold_value = ?, auto_order = ? WHERE item_name = ?";
                    $stmt = $conn->prepare($sql);
                    $autoOrder = $item['autoOrder'] ? 1 : 0;
                    $stmt->bind_param("iis", $item['threshold'], $autoOrder, $item['itemName']);
                    $stmt->execute();
                } else {
                    // Threshold doesn't exist, insert it
                    $sql = "INSERT INTO inventory_thresholds (item_name, threshold_value, auto_order) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $autoOrder = $item['autoOrder'] ? 1 : 0;
                    $stmt->bind_param("sii", $item['itemName'], $item['threshold'], $autoOrder);
                    $stmt->execute();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Return error
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Save general settings to the database
 * @param array $data The general settings data
 * @param bool $returnJson Whether to return JSON or an array
 * @return mixed The result of the operation
 */
function saveGeneralSettings($data, $returnJson = true) {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Save each setting
        foreach ($data as $key => $value) {
            $sql = "INSERT INTO general_settings (setting_key, setting_value) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Return error
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Helper function to create required tables
function createRequiredTables() {
    global $conn;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create roles table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Create permissions table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL,
            module VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_permission (role, module, action)
        )";
        $conn->query($sql);
        
        // Create general_settings table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS general_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Create inventory_thresholds table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS inventory_thresholds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(100) NOT NULL UNIQUE,
            threshold_value INT NOT NULL DEFAULT 5,
            auto_order TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($sql);
        
        // Insert default roles if they don't exist
        $defaultRoles = [
            ['admin', 'Full system access with all permissions enabled.'],
            ['manager', 'Access to most functions except user management and settings.'],
            ['staff', 'Limited access focused on inventory and daily operations.'],
            ['viewer', 'Read-only access to view reports and inventory items.']
        ];
        
        foreach ($defaultRoles as $role) {
            $sql = "INSERT IGNORE INTO roles (role_name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $role[0], $role[1]);
            $stmt->execute();
        }
        
        // Insert default general settings
        $defaultSettings = [
            ['default_threshold', '5'],
            ['enable_auto_order', '1'],
            ['threshold_percentage', '20'],
            ['system_name', 'ICTD Inventory Management System'],
            ['organization_name', 'STI College'],
            ['date_format', 'mm/dd/yyyy'],
            ['time_format', '12'],
            ['currency', 'PHP'],
            ['log_retention', '90'],
            ['backup_frequency', 'daily']
        ];
        
        foreach ($defaultSettings as $setting) {
            $sql = "INSERT IGNORE INTO general_settings (setting_key, setting_value) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        return false;
    }
}

// Special case for setup tables
if (isset($_GET['action']) && $_GET['action'] === 'setup_tables') {
    $result = createRequiredTables();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit;
} 