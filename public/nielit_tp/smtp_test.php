<?php
require_once 'includes/functions.php';

$ok = send_smtp_email("your-email-here@gmail.com", "Test Mail", "SMTP working!");

echo $ok ? "EMAIL SENT" : "EMAIL FAILED";
