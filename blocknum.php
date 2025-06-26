<?php
date_default_timezone_set("Asia/Calcutta");
/* === DB config === */
$dbHost = 'localhost';
$dbUser = 'fromzero_santi';
$dbPass = 'Santivoip4321';
$dbName = 'fromzero_morevitility';

$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// === Get input number (e.g., via POST or GET) ===
$inputNumber = trim($_POST['number'] ?? $_GET['number'] ?? '');

if ($inputNumber === '' || strlen($inputNumber) < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid number']);
    exit;
}

// === Transform: drop first 3 and last digit ===
$transformed = substr(substr($inputNumber, 3), 0, -1);
$escapedNum = mysqli_real_escape_string($conn, $transformed);

// === Query ===
$sql = "SELECT 1 FROM blocknum WHERE number = '$escapedNum' LIMIT 1";
$result = mysqli_query($conn, $sql);

$exists = mysqli_num_rows($result) > 0;

mysqli_close($conn);

// === Output ===
echo json_encode(['exists' => $exists ? 'yes' : 'no']);

?>