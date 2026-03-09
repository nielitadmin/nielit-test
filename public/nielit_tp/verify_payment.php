<?php
require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';
use Razorpay\Api\Api;

if (!isset($_GET['payment_id'], $_GET['order_id'], $_GET['signature'], $_GET['submission_id'])) {
    exit('Invalid request.');
}

$submission_id = intval($_GET['submission_id']);
$payment_id = $_GET['payment_id'];
$order_id = $_GET['order_id'];
$signature = $_GET['signature'];

$api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);

try {
    $attributes = [
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // ✅ Payment Verified - Update DB
    $stmt = $pdo->prepare("UPDATE submissions 
        SET payment_status='paid', payment_id=:pid, paid_at=NOW() 
        WHERE id=:id");
    $stmt->execute(['pid' => $payment_id, 'id' => $submission_id]);

    echo "<script>alert('✅ Payment successful!'); window.location.href='tp_dashboard.php';</script>";
} catch (Exception $e) {
    echo "<script>alert('❌ Payment verification failed: " . addslashes($e->getMessage()) . "'); window.location.href='tp_dashboard.php';</script>";
}
?>
