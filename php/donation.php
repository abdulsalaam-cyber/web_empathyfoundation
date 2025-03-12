<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["name"]) || !isset($data["phone"]) || !isset($data["amount"])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

$name = htmlspecialchars($data["name"]);
$phone = htmlspecialchars($data["phone"]);
$amount = htmlspecialchars($data["amount"]);

// Replace with your API credentials
$mpesa_api_url = "https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
$consumer_key = "YOUR_CONSUMER_KEY";
$consumer_secret = "YOUR_CONSUMER_SECRET";
$shortcode = "YOUR_SHORTCODE";
$passkey = "YOUR_PASSKEY";

$timestamp = date("YmdHis");
$password = base64_encode($shortcode . $passkey . $timestamp);

// Get access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$consumer_key:$consumer_secret")]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = json_decode(curl_exec($ch));
$access_token = $result->access_token;
curl_close($ch);

// Send STK Push request
$stk_data = [
    "BusinessShortCode" => $shortcode,
    "Password" => $password,
    "Timestamp" => $timestamp,
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => $amount,
    "PartyA" => $phone,
    "PartyB" => $shortcode,
    "PhoneNumber" => $phone,
    "CallBackURL" => "https://yourwebsite.com/callback.php",
    "AccountReference" => "CharityDonation",
    "TransactionDesc" => "Donation"
];

$ch = curl_init($mpesa_api_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$response = json_decode(curl_exec($ch));
curl_close($ch);

if (isset($response->ResponseCode) && $response->ResponseCode == "0") {
    echo json_encode(["success" => true, "message" => "Payment request sent"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to initiate payment"]);
}
?>
