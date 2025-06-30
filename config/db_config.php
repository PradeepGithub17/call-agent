<?php
define('HOST', 'localhost');
define('USER', 'root');
define('PASS', '$Provis@2025');
define('DB', 'fromzero_morevitility');

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