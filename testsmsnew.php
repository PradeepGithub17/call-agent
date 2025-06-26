<?php

// Configuration
$username = 'kIOCiE';
$key = 'WlBUAjrrahuDvkdKQyj5S8LoRGMpoHIc';
$spNumber = '123456';

// Example data
$content = "Your verification code is 282828";
$phones = [['phone' => '61485865568']];

// Generate nonceStr and timestamp
$nonceStr = bin2hex(random_bytes(8));
$timestamp = round(microtime(true) * 1000);

// Construct string to sign
$phonesJson = json_encode($phones, JSON_UNESCAPED_UNICODE);
$stringToSign = "content=$content&key=$key&nonceStr=$nonceStr&phones=$phonesJson&signType=MD5&spNumber=$spNumber&timestamp=$timestamp&username=$username&key=$key";

// Generate signature
$sign = strtoupper(md5($stringToSign));

// Log constructed string and signature
file_put_contents('sms_api_debug.log', "String to Sign: $stringToSign\nSignature: $sign\n", FILE_APPEND);

// Prepare payload
$payload = [
    'username' => $username,
    'nonceStr' => $nonceStr,
    'signType' => 'MD5',
    'timestamp' => "$timestamp",
    'spNumber' => $spNumber,
    'content' => $content,
    'phones' => $phones,
    'sign' => $sign
];

// Log payload
file_put_contents('sms_api_debug.log', "Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// Initialize cURL
$ch = curl_init('http://183.178.45.166/ta-sms/openapi/submittal');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

// Execute and get response
$response = curl_exec($ch);
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    file_put_contents('sms_api_debug.log', "cURL Error: $error_msg\n", FILE_APPEND);
} else {
    file_put_contents('sms_api_debug.log', "Response: $response\n", FILE_APPEND);
}

curl_close($ch);

// Output response for debugging
header('Content-Type: application/json');
echo $response;