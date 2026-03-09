<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<pre>";
print_r([
    'DB_HOST' => $_ENV['DB_HOST'] ?? null,
    'DB_NAME' => $_ENV['DB_NAME'] ?? null,
    'RAZORPAY_KEY_ID' => $_ENV['RAZORPAY_KEY_ID'] ?? null,
    'APP_URL' => $_ENV['APP_URL'] ?? null
]);
echo "</pre>";
