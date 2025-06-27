<?php
date_default_timezone_set('Europe/London');
/* === DB config === */
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '$Provis@2025'; // use environment variable in production
$dbName = 'fromzero_morevitility';

header('Content-Type: application/json');

   // Gmail user, same as SMTP login

/* ---------- PHPMailer SETUP ---------- */
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* === Connect === */
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$agent  = trim($_POST['agent']  ?? '');
$caller = trim($_POST['caller'] ?? '');
$action = trim($_POST['action'] ?? '');

file_put_contents("postdata.txt",date('H:i:s')." ".$agent." ".$caller." ".$action.PHP_EOL,FILE_APPEND);

if ($agent === '' || $caller === '' || $action === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

/* ---------- ESCAPE VALUES ONCE ---------- */
$todaydate = date('Y-m-d H:i:s');
$eAgent  = mysqli_real_escape_string($conn, $agent);
$eCaller = mysqli_real_escape_string($conn, $caller);
$eAction = mysqli_real_escape_string($conn, $action);

$aCaller = '61' . substr(substr($eCaller, 3), 0, -1); // 0 + drop first 3 + drop last
$adminCaller = mysqli_real_escape_string($conn, $aCaller);

/* ---------- 1) INSERT INTO adminsmsdata ---------- */
$sqlAdmin = "
  INSERT INTO adminsmsdata (agent, number, butclick,insertdate)
  VALUES ('$eAgent', '$adminCaller', '$eAction','$todaydate')
";
$okAdmin = mysqli_query($conn, $sqlAdmin);

if(!$okAdmin) {
    
    file_put_contents(date('Y-m-d')."_adminsqlerror",date('H:i:s')." _".mysqli_error($conn).PHP_EOL,FILE_APPEND);
    
}

/* ---------- 2) TRANSFORM CALLER & INSERT ownersmsdata ---------- */
$ownerCaller = '0' . substr(substr($caller, 3), 0, -1); // 0 + drop first 3 + drop last
$eOwnerCaller = mysqli_real_escape_string($conn, $ownerCaller);

if ($action === 'Block') {
    $blockedNumber = substr(substr($caller, 3), 0, -1); // same transformation
    $eBlockedNumber = mysqli_real_escape_string($conn, $blockedNumber);

    $sqlBlock = "
      INSERT INTO blocknum (number, agent)
      VALUES ('$eBlockedNumber', '$eAgent')
    ";
    $okBlock = mysqli_query($conn, $sqlBlock);

    if (!$okBlock) {
        file_put_contents(date('Y-m-d') . "_blocksqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
         //echo json_encode(['success' => false, 'message' => 'Action not triggered successfully']);
    }
   
}
$sqlOwner = "
  INSERT INTO ownersmsdata (agent, number, butclick,insertdate)
  VALUES ('$eAgent', '$adminCaller', '$eAction','$todaydate')
";
$okOwner = mysqli_query($conn, $sqlOwner);

if(!$okOwner) {
    
   file_put_contents(date('Y-m-d')."_adminsqlerror",date('H:i:s')." _".mysqli_error($conn).PHP_EOL,FILE_APPEND);
}

/* ---------- CLOSE & RESPOND ---------- */
//mysqli_close($conn);



if (in_array($action, ['Phrase', 'Ledge','Key', 'Key Cancel'], true)) 
{
    
    
    $subject = "ALERT: {$action} clicked by {$agent}";
    $body    = "Button  : {$action}\nAgent   : {$agent}\nReference  : {$adminCaller}\nTime    : " .  date('H:i:s d-m-Y'); 
    file_put_contents(date('Y-m-d')."_emailphrase.txt",$body.PHP_EOL,FILE_APPEND);
    $emails = [];  // array to hold multiple alert recipients
//define('ALERT_TO',   'gaurav.lokhande1706@gmail.com');          // where the alert goes
    define('ALERT_FROM', 'santi@allsmartone.com');  
    if ($agent !== '') 
    {
        //$eAgent = mysqli_real_escape_string($conn, $agent);
    
        $sql = " SELECT DISTINCT u.email FROM ausers a JOIN adminuser u ON a.adminid = u.id WHERE a.user = '$eAgent' OR u.role = 'owner' ";
    
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $email = trim($row['email']);
            if ($email !== '') {
                $emails[] = $email;
            }
        }
    }
    if (empty($emails)) {
        $emails[] = 'gaurav.lokhande1706@gmail.com';  // fallback or default
    }
   $edata = sendAlertEmail($emails,$subject, $body);
}

if ($okAdmin && $okOwner) {
    echo json_encode(['success' => true, 'message' => 'Action triggered successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Action not triggered successfully']);
}
mysqli_close($conn);

function sendsms($action,$ownerCaller)
{
    $smsUrl = 'https://allsmartone.com/santi/sendsms.php';   // change to real path
    $postData = http_build_query([
        'phone'  => $ownerCaller,
        'action' => $action
    ]);
    
    $ch = curl_init($smsUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        // OPTIONAL: set a short timeout and ignore SSL issues on localhost
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10
    ]);
    $smsResp = curl_exec($ch);
    $smsErr  = curl_error($ch);
    curl_close($ch);
    return true ;
}

function sendAlertEmail(array $emails, string $subject, string $body)
{
    $mail = new PHPMailer(true);
    $logFile = date('Y-m-d').'_email_log.txt';

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = ALERT_FROM;
        $mail->Password   = 'Santi@4321';  // use env var in production
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom(ALERT_FROM, 'Alert Bot');

        // Add each email as a recipient
        foreach ($emails as $email) {
            $mail->addAddress($email);
        }

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Email sent: $subject\n", FILE_APPEND);

    } catch (Exception $e) {
        $errorMsg = "[" . date('Y-m-d H:i:s') . "] Email failed: " . $mail->ErrorInfo . "\n";
        file_put_contents($logFile, $errorMsg, FILE_APPEND);
    }
}

?>
