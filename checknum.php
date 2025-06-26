<?php

//$number = $_GET['number'];
//$direction = $_GET['direction'];
$number = $_GET['number'] ?? 'nodata';
$direction = $_GET['direction'] ?? 'nodata';

file_put_contents("getnum.txt",$number. " ".$direction.PHP_EOL,FILE_APPEND) ;

if($number == "nodata")
{
    exit("Nodata");
}
$number = preg_replace('/\D/', '', $number);

// Get first 4 digits
$firstFour = substr($number, 0, 4);

// Compute ID: 100 - first 4 digits (as integer)
$calculatedID = intval($firstFour) + 100;

// Get last 3 digits
$lastThree = substr($number, -3);

// Build the response array
$response = [
    "id" => (string)$calculatedID,
    "fname" => "name" . $lastThree,
    "lname" => "lname" . $lastThree,
    "bphone" => $number,
    "url" => "https://allsmartone.com/santi/newdata.php?callid=" . $calculatedID
];

//$sendata = sendWebhookRequest($number, $direction);

// Output JSON
header('Content-Type: application/json');
http_response_code(200);
echo json_encode($response);

function sendWebhookRequest($number, $direction) {
    $baseUrl = "https://services.leadconnectorhq.com/hooks/bzU0wwGqyCh3MdEwjP1p/webhook-trigger/bb867cc5-77bd-4513-bd09-08accec3c4c4";
    $queryParams = http_build_query([
        'number' => $number,
        'direction' => $direction
    ]);

    $url = $baseUrl . '?' . $queryParams;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Webhook request failed: " . curl_error($ch));
    }

    curl_close($ch);
    return $response;
}

?>