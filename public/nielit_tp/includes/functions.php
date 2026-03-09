<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| PHPMailer Load (Required for sending emails)
|--------------------------------------------------------------------------
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// *************** FINAL FIX ***************
// Use RELATIVE PATH instead of FULL PATH
// Because full path is blocked by Hostinger open_basedir
require __DIR__ . "/../vendor/autoload.php";
// *****************************************


/*
|--------------------------------------------------------------------------
| Fixed Base Path (Your project folder)
|--------------------------------------------------------------------------
*/
$BASE_PATH = '/public/nielit_tp/';

/*
|--------------------------------------------------------------------------
| Secure Redirect Function
|--------------------------------------------------------------------------
*/
function redirect($url) {
    global $BASE_PATH;
    $finalUrl = $BASE_PATH . ltrim($url, '/');
    header("Location: $finalUrl");
    exit;
}

/*
|--------------------------------------------------------------------------
| Login Protection (Admin + TP)
|--------------------------------------------------------------------------
*/
function require_login_tp() {
    if (!isset($_SESSION['tp_id'])) redirect('index.php');
}

function require_login_admin() {
    if (!isset($_SESSION['admin_id'])) redirect('admin_login.php');
}

/*
|--------------------------------------------------------------------------
| Secure File Upload Helper
|--------------------------------------------------------------------------
*/
function store_upload($file, $dest_dir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv'
    ];

    if (!in_array($mime, $allowed)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;

    if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);

    $target = rtrim($dest_dir, '/') . '/' . $safeName;

    return move_uploaded_file($file['tmp_name'], $target) ? $target : false;
}

/*
|--------------------------------------------------------------------------
| SMTP Email Function (Hostinger — FINAL WORKING VERSION)
|--------------------------------------------------------------------------
*/
function send_smtp_email($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = "smtp.hostinger.com";
        $mail->SMTPAuth = true;

        // Hostinger mailbox
        $mail->Username = "admin@nielitbhubaneswar.in";
        $mail->Password = "Nielitbbsr@2025";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom("admin@nielitbhubaneswar.in", "NIELIT Bhubaneswar Portal");
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("EMAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}
?>
