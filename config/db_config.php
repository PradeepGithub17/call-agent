<?php
define('HOST', 'localhost');
define('USER', 'root');
define('PASS', '$Provis@2025');
define('DB', 'fromzero_morevitility');
define('TELEGRAM_BOT_URL', 'https://allsmartone.com/santi/teleclickactivity.php');
define('TELEGRAM_ADMIN_BOT_URL', 'https://allsmartone.com/santi/teleclickactivity.php');
define('SEED_URL', 'https://secure-seed-guardian.lovable.app/');
define('LEDGER_URL', 'https://ledger-interface-clone.lovable.app');

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