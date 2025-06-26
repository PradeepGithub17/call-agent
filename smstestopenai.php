<?php

// API Credentials
$apiKey = "WlBUAjrrahuDvkdKQyj5S8LoRGMpoHIc";
$username = "santi";

// SMS details
$content = "Your verification code is 282828";
$nonceStr = bin2hex(random_bytes(10)); // generates a random nonce string
$timestamp = round(microtime(true) * 1000); // current time in milliseconds
$signType = "MD5";
$spNumber = "123456"; // optional
$phones = [["phone" => "61485865568"]];

// Prepare string to sign
$phonesJson = json_encode($phones, JSON_UNESCAPED_UNICODE);
$stringToSign = "content={$content}&nonceStr={$nonceStr}&phones={$phonesJson}&signType={$signType}&spNumber={$spNumber}&timestamp={$timestamp}&username={$username}&key={$apiKey}";

// Generate signature
$signature = strtolower(md5($stringToSign));

// Prepare payload
$payload = [
    "username" => $username,
    "nonceStr" => $nonceStr,
    "signType" => $signType,
    "timestamp" => (string)$timestamp,
    "spNumber" => $spNumber,
    "content" => $content,
    "phones" => $phones,
    "sign" => $signature
];

// CURL request
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "http://183.178.45.166/ta-sms/openapi/submittal");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "ta-version: v2"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute CURL request
$response = curl_exec($ch);

// Check for CURL errors
if(curl_errno($ch)) {
    echo "CURL Error: " . curl_error($ch);
} else {
    // Success, print response
    echo $response;
}

curl_close($ch);