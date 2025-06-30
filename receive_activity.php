<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only POST requests accepted.']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit();
}

// Log all received data
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => $data
];

// Save to log file
$log_file = 'activity_logs/' . date('Y-m-d') . '.json';
if (!is_dir('activity_logs')) {
    mkdir('activity_logs', 0755, true);
}

file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);

// Process the data based on type
if (isset($data['activities']) && is_array($data['activities'])) {
    // Batch processing
    $processed = [];
    foreach ($data['activities'] as $activity) {
        $processed[] = processActivity($activity);
    }
    
    $response = [
        'success' => true,
        'message' => 'Batch activity data processed successfully',
        'batch_size' => count($data['activities']),
        'processed_activities' => $processed,
        'received_at' => date('Y-m-d H:i:s')
    ];
} else {
    // Single activity processing
    $processed = processActivity($data);
    
    $response = [
        'success' => true,
        'message' => 'Activity data processed successfully',
        'processed_activity' => $processed,
        'received_at' => date('Y-m-d H:i:s')
    ];
}

http_response_code(200);
echo json_encode($response);

/**
 * Process individual activity data
 */
function processActivity($activityData) {
    // Add processing logic here
    // For now, just return the data with processing timestamp
    return [
        'original_data' => $activityData,
        'processed_at' => date('Y-m-d H:i:s'),
        'status' => 'processed'
    ];
}
?>