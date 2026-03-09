<?php
// ========================================
// Razorpay API Connectivity Test Script
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

use Razorpay\Api\Api;

// Get credentials from .env
$key_id     = $_ENV['RAZORPAY_KEY_ID'] ?? '';
$key_secret = $_ENV['RAZORPAY_KEY_SECRET'] ?? '';

if (empty($key_id) || empty($key_secret)) {
    exit('<h3 style="color:red;">❌ Razorpay keys not found in .env file!</h3>
          <p>Make sure you have:</p>
          <pre>RAZORPAY_KEY_ID=your_key_id
RAZORPAY_KEY_SECRET=your_secret</pre>');
}

// Initialize Razorpay API
try {
    $api = new Api($key_id, $key_secret);

    // Try creating a small test order
    $order = $api->order->create([
        'receipt'         => 'test_order_' . time(),
        'amount'          => 100, // ₹1.00 (in paise)
        'currency'        => 'INR',
        'payment_capture' => 1
    ]);

    echo "<h2 style='color:green;'>✅ Razorpay connection successful!</h2>";
    echo "<p><b>Order ID:</b> " . htmlspecialchars($order['id']) . "</p>";
    echo "<p><b>Status:</b> " . htmlspecialchars($order['status']) . "</p>";
    echo "<p><b>Amount:</b> ₹" . ($order['amount'] / 100) . "</p>";

    echo "<hr><p>That means your <b>live API keys</b> and <b>server connection</b> are working perfectly 🎉</p>";
} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ Connection failed!</h3>";
    echo "<p><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check:</p>
          <ul>
            <li>✔ Your RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env</li>
            <li>✔ Your PHP version is 8.1 or higher</li>
            <li>✔ cURL and OpenSSL extensions are enabled on Hostinger</li>
          </ul>";
}
?>
