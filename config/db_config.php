<?php
define('HOST', 'localhost');
define('USER', 'root');
define('PASS', '$Provis@2025');
define('DB', 'fromzero_morevitility');
define('TELEGRAM_BOT_URL', 'http://localhost/clickactivity/receive_activity.php');
define('TELEGRAM_ADMIN_BOT_URL', 'http://localhost/clickactivity/receive_activity.php');

function getDbConnection() {
    $conn = mysqli_connect(HOST, USER, PASS, DB);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
function closeDbConnection($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
}