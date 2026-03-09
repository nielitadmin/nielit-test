<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . "/__test/public/nielit_tp/vendor/autoload.php";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = "smtp.hostinger.com";
    $mail->SMTPAuth = true;

    $mail->Username = "noreply@nielitbhubaneswar.in";
    $mail->Password = "Nielitbbsr@2025";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom("noreply@nielitbhubaneswar.in", "Test Mail");
    $mail->addAddress("yourgmail@gmail.com");

    $mail->isHTML(true);
    $mail->Subject = "Hostinger SMTP Test";
    $mail->Body = "SMTP working perfectly! 🎉";

    $mail->send();
    echo "Mail sent successfully!";
} catch (Exception $e) {
    echo "Mail error: " . $mail->ErrorInfo;
}
