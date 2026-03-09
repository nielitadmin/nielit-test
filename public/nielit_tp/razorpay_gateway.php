<?php
// razorpay_gateway.php
require_once 'includes/db_connect.php';
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

session_start();

// Validate request
if (!isset($_GET['submission_id'])) {
    exit('Invalid request.');
}

$submission_id = intval($_GET['submission_id']);

// Fetch submission details
$stmt = $pdo->prepare("
    SELECT s.*, t.email, t.contact_number, t.centre_name
    FROM submissions s
    JOIN tps t ON s.tp_id = t.id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $submission_id]);
$submission = $stmt->fetch();

if (!$submission) {
    exit('Submission not found.');
}

// Initialize Razorpay API
$key_id = $_ENV['RAZORPAY_KEY_ID'];
$key_secret = $_ENV['RAZORPAY_KEY_SECRET'];
$api = new Api($key_id, $key_secret);

// Create Razorpay Order
$orderData = [
    'receipt'         => 'TP-' . $submission_id,
    'amount'          => $submission['total_amount'] * 100, // amount in paise
    'currency'        => 'INR',
    'payment_capture' => 1 // auto capture
];

try {
    $razorpayOrder = $api->order->create($orderData);
    $order_id = $razorpayOrder['id'];

    // Store order_id in DB
    $pdo->prepare("UPDATE submissions SET razorpay_order_id=:oid WHERE id=:id")
        ->execute(['oid' => $order_id, 'id' => $submission_id]);

} catch (Exception $e) {
    exit("Error creating Razorpay order: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Razorpay Payment | NIELIT Bhubaneswar</title>
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex; justify-content: center; align-items: center;
      height: 100vh; background: #f8fafc;
    }
    .pay-box {
      text-align: center; background: #fff; padding: 30px;
      border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .btn-pay {
      background-color: #2563eb; color: white;
      border: none; padding: 10px 20px; border-radius: 6px;
      cursor: pointer; font-size: 16px;
    }
    .btn-pay:hover { opacity: 0.9; }
  </style>
</head>
<body>
  <div class="pay-box">
    <h2>Exam Fee Payment</h2>
    <p><b>Training Partner:</b> <?= htmlspecialchars($submission['centre_name']) ?></p>
    <p><b>Amount:</b> ₹<?= number_format($submission['total_amount'], 2) ?></p>
    <button id="payBtn" class="btn-pay">Proceed to Pay</button>
  </div>

  <script>
  document.getElementById('payBtn').onclick = function(e){
      var options = {
          "key": "<?= htmlspecialchars($key_id) ?>",
          "amount": "<?= $submission['total_amount'] * 100 ?>",
          "currency": "INR",
          "name": "NIELIT Bhubaneswar",
          "description": "Exam Fee Payment",
          "order_id": "<?= $order_id ?>",
          "handler": function (response){
              // After successful payment, verify
              window.location.href = "verify_payment.php?payment_id=" + response.razorpay_payment_id +
                  "&order_id=" + response.razorpay_order_id +
                  "&signature=" + response.razorpay_signature +
                  "&submission_id=<?= $submission_id ?>";
          },
          "prefill": {
              "name": "<?= htmlspecialchars($submission['centre_name']) ?>",
              "email": "<?= htmlspecialchars($submission['email']) ?>",
              "contact": "<?= htmlspecialchars($submission['contact_number']) ?>"
          },
          "theme": { "color": "#2563eb" }
      };
      var rzp = new Razorpay(options);
      rzp.open();
      e.preventDefault();
  }
  </script>
</body>
</html>
