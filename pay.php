<?php
// Configuration for hidden parameters
$app_id = "YD3469"; // Set your Merchant ID here
$trade_type = "YOUR_TRADE_TYPE"; // Set the Trade Type here
$notify_url = "https://fk.ampelix.in"; // Set your Notify URL here
$client_ip = "175.29.21.168"; // Default IP, can be modified
$remark = "Optional Remark"; // Optional Remark
$secret_key = "DwjU6LUUhd5g5I2H"; // Set your secret key here

// Generate a random order number
$order_sn = uniqid("ORDER_"); // Prefix ORDER_ followed by a unique ID

// Include CSS for the page
echo "<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    box-sizing: border-box;
}
form, .result-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    max-width: 400px;
    width: 100%;
    margin-bottom: 20px;
}
input, button, a {
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 16px;
    text-align: center;
}
button {
    background-color: #4CAF50;
    color: white;
    border: none;
    cursor: pointer;
}
button:hover {
    background-color: #45a049;
}
input:read-only {
    background-color: #f9f9f9;
    cursor: default;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
.qr-container img {
    max-width: 100%;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-top: 10px;
}
</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User input amount
    $amount = floatval($_POST['money']);

    // Prepare Request Data
    $data = [
        "app_id" => $app_id,
        "trade_type" => "INRUPI",
        "order_sn" => $order_sn,
        "money" => intval($amount * 100), // Multiply by 100 and convert to integer
        "notify_url" => $notify_url,
        "ip" => $client_ip,
        "remark" => $remark
    ];

    // Generate Signature
    $data = array_filter($data); // Remove empty values
    ksort($data); // Sort by ASCII values
    $string = urldecode(http_build_query($data)) . "&key=" . $secret_key;
    $data['sign'] = strtoupper(md5($string)); // Add the signature

    // API Request
    $url = "https://www.lg-pay.com/api/order/create";
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        die("Error connecting to the payment gateway.");
    }

    $result = json_decode($response, true);

    // Log the raw API response for debugging
    file_put_contents('api_debug_log.txt', print_r($result, true), FILE_APPEND);

    // Check for payment URL
    if (isset($result['data']['pay_url']) && !empty($result['data']['pay_url'])) {
        $pay_url = $result['data']['pay_url'];
    } else {
        $error_message = $result['msg'] ?? "Unknown error";
        echo "<div style='color: red; text-align: center;'>Error: " . htmlspecialchars($error_message) . "</div>";
        // Debugging: Display the full API response
        echo "<pre>API Response:\n" . print_r($result, true) . "</pre>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Payment Gateway Integration">
    <meta name="author" content="Your Company">
    <title>Payment Gateway</title>
</head>
<body>
    <form method="POST">
        <h2>Create Payment</h2>
        <input type="hidden" name="order_sn" value="<?php echo $order_sn; ?>">
        <input type="number" step="0.01" name="money" placeholder="Enter Amount (Minimum 100)" required>
        <button type="submit">Generate Payment Link</button>
    </form>

    <?php if (isset($pay_url)): ?>
    <div class="result-box">
        <h2>Payment Link</h2>
        <input type="text" id="pay_url" value="<?php echo $pay_url; ?>" readonly>
        <button onclick="copyPayUrl()">Copy Link</button>
        <a href="<?php echo $pay_url; ?>" target="_blank" style="
    text-decoration: none;
    display: inline-block;
    background-color: #4CAF50;
    color: white;
    border-radius: 4px;
    padding: 10px;
    text-align: center;
    font-size: 16px;
    border: none;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 15px;
">Open in New Tab</a>
    
    </div>
    <script>
        function copyPayUrl() {
            const payUrlInput = document.getElementById('pay_url');
            payUrlInput.select();
            payUrlInput.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(payUrlInput.value);
            alert("Payment link copied to clipboard!");
        }
    </script>
    <?php endif; ?>
</body>
</html>