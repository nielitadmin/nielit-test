<?php
require_once 'includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

echo "<pre>";

try {
    $mail->SMTPDebug = 3; // FULL DEBUG
    $mail->isSMTP();
    $mail->Host = "smtp.hostinger.com";
    $mail->SMTPAuth = true;

    $mail->Username = "noreply@nielitbhubaneswar.in"; 
    $mail->Password = "Nielitbbsr@2025";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom("noreply@nielitbhubaneswar.in", "SMTP Test");
    $mail->addAddress("yourgmail@gmail.com");

    $mail->Subject = "SMTP DEBUG TEST";
    $mail->Body = "SMTP Debug test message";

    $mail->send();
    echo "\nSUCCESS: Email sent.";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage();
    echo "\nMAILER ERROR INFO: " . $mail->ErrorInfo;
}

echo "</pre>";
