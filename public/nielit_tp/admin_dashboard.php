<?php
// admin_dashboard.php - Fixed Timezone & Syntax

// 1. START SESSION & INIT VARIABLES
if (!session_id()) session_start();

// --- FIX: SET TIMEZONE TO ASIA/KOLKATA (IST) ---
date_default_timezone_set('Asia/Kolkata'); 

ob_start();

// Initialize message variable
$msg = ''; 

// DEV MODE: Error Reporting (Disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$page_title = 'Admin Console';
require_login_admin(); 

// --- CSRF PROTECTION ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

// --- 2. DATA FETCHING ---

// A. Global Stats
try {
    $total_tps = (int)$pdo->query("SELECT COUNT(*) FROM tps")->fetchColumn();
    $total_subs = (int)$pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
    $total_students = (int)$pdo->query("SELECT COALESCE(SUM(total_students),0) FROM submissions")->fetchColumn();
    $total_revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM submissions WHERE payment_status='paid'")->fetchColumn();
} catch (\Throwable $e) {
    error_log("Stats Error: " . $e->getMessage());
    $total_tps = $total_subs = $total_students = 0; $total_revenue = 0.0;
}

// B. Training Partners (Pending & Registered)
try {
    $pending_raw = $pdo->query('SELECT * FROM tps WHERE verified="pending" ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $registered_raw = $pdo->query('SELECT * FROM tps WHERE verified IN ("approved","rejected") ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $pending_raw = $registered_raw = [];
}

// Prepare metrics queries
$totStmt = $pdo->prepare("SELECT COALESCE(SUM(total_students),0) AS tp_total_students, COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END),0) AS tp_total_paid FROM submissions WHERE tp_id = :id");
$histStmt = $pdo->prepare("SELECT s.batch_name, s.total_students, s.total_amount, s.payment_status, s.created_at, e.title as exam_title FROM submissions s LEFT JOIN exams e ON s.exam_id = e.id WHERE s.tp_id = :id ORDER BY s.created_at DESC LIMIT 10");

$pending = [];
foreach ($pending_raw as $tp) {
    $totStmt->execute(['id' => $tp['id']]);
    $t = $totStmt->fetch(PDO::FETCH_ASSOC);
    $tp['tp_total_students'] = (int)($t['tp_total_students'] ?? 0);
    $tp['tp_total_paid'] = (float)($t['tp_total_paid'] ?? 0.0);
    $pending[] = $tp;
}

$registered = [];
foreach ($registered_raw as $tp) {
    $totStmt->execute(['id' => $tp['id']]);
    $t = $totStmt->fetch(PDO::FETCH_ASSOC);
    $tp['tp_total_students'] = (int)($t['tp_total_students'] ?? 0);
    $tp['tp_total_paid'] = (float)($t['tp_total_paid'] ?? 0.0);
    
    // Fetch History for modal
    $histStmt->execute(['id' => $tp['id']]);
    $tp['history'] = $histStmt->fetchAll(PDO::FETCH_ASSOC); 
    
    $registered[] = $tp;
}

// C. Exams & Submissions
try {
    $exams = $pdo->query('SELECT * FROM exams ORDER BY exam_date DESC')->fetchAll(PDO::FETCH_ASSOC);
    $submissions = $pdo->query('
        SELECT s.*, s.id AS submission_id, s.created_at AS applied_on,
               e.title AS exam_title, e.exam_date,
               t.centre_name AS tp_name, t.username AS tp_username, t.email AS tp_email
        FROM submissions s
        JOIN exams e ON s.exam_id = e.id
        JOIN tps t ON s.tp_id = t.id
        ORDER BY s.created_at DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $exams = $submissions = [];
}

// --- 3. CHART DATA ---
$chartLabels = [];
$chartData = [];
foreach($submissions as $sub) {
    if($sub['payment_status'] === 'paid') {
        $exTitle = $sub['exam_title'];
        if(!isset($chartData[$exTitle])) {
            $chartData[$exTitle] = 0;
        }
        $chartData[$exTitle] += $sub['total_amount'];
    }
}
$jsLabels = json_encode(array_keys($chartData));
$jsValues = json_encode(array_values($chartData));


// --- 4. ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    
    $posted_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)$posted_csrf)) {
        $_SESSION['flash_msg'] = 'Security Token Mismatch.';
        header('Location: admin_dashboard.php'); exit;
    }

    $action = $_POST['action'];

    
// --- TP ACTIONS (Professional & Futuristic) ---
    
    // 1. APPROVE TRAINING PARTNER
    if ($action === 'approve_tp' && !empty($_POST['tp_id'])) {
        $tp_id = intval($_POST['tp_id']);
        
        // Update Status
        $pdo->prepare('UPDATE tps SET verified="approved", verified_at = NOW() WHERE id = :id')->execute(['id' => $tp_id]);
        
        // Fetch Details for Email
        $stmt = $pdo->prepare('SELECT email, centre_name, username FROM tps WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $tp_id]);
        $tp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tp && !empty($tp['email'])) {
            // Determine Login URL (Dynamic)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $login_link = "$protocol://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/index.php";

            $subject = "Access Granted: Welcome to the NIELIT Ecosystem";
            
            // Modern "Futuristic" Email Template
            $body = '
            <div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
                    
                    <div style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); padding: 30px; text-align: center;">
                        <h1 style="color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px;">ACCESS GRANTED</h1>
                    </div>

                    <div style="padding: 40px 30px; text-align: center;">
                        <p style="font-size: 16px; color: #64748b; margin-bottom: 20px;">Hello <strong>'.htmlspecialchars($tp['centre_name']).'</strong>,</p>
                        <p style="font-size: 16px; color: #334155; line-height: 1.6;">
                            Your Training Partner application has been successfully <strong>verified and approved</strong> by our administration team.
                        </p>
                        
                        <div style="background: #f1f5f9; border-radius: 8px; padding: 15px; margin: 30px 0; text-align: left;">
                            <p style="margin: 5px 0; font-size: 14px; color: #475569;"><strong>Username:</strong> '.htmlspecialchars($tp['username']).'</p>
                            <p style="margin: 5px 0; font-size: 14px; color: #475569;"><strong>Status:</strong> <span style="color: #10b981; font-weight: bold;">Active</span></p>
                        </div>

                        <a href="'.$login_link.'" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 50px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);">Access Dashboard</a>
                        
                        <p style="margin-top: 30px; font-size: 13px; color: #94a3b8;">You can now apply for exams and manage your candidates.</p>
                    </div>

                    <div style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                        <p style="font-size: 12px; color: #94a3b8; margin: 0;">&copy; '.date('Y').' NIELIT Bhubaneswar. Automated System.</p>
                    </div>
                </div>
            </div>';

            send_smtp_email($tp['email'], $subject, $body);
        }

        $_SESSION['flash_msg'] = '✅ Partner Account Approved & Notified.';
        header('Location: admin_dashboard.php'); exit;
    }

    // 2. REJECT TRAINING PARTNER
    if ($action === 'reject_tp' && !empty($_POST['tp_id'])) {
        $tp_id = intval($_POST['tp_id']);
        
        // Notify BEFORE Updating (so we have the data)
        $stmt = $pdo->prepare('SELECT email, centre_name FROM tps WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $tp_id]);
        $tp = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update Status
        $pdo->prepare('UPDATE tps SET verified="rejected" WHERE id = :id')->execute(['id' => $tp_id]);

        if ($tp && !empty($tp['email'])) {
            $subject = "Application Status Update - NIELIT";
            
            // Professional Rejection Template
            $body = '
            <div style="font-family: \'Segoe UI\', sans-serif; background-color: #f8fafc; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                    <div style="padding: 40px 30px;">
                        <h2 style="color: #ef4444; margin-top: 0;">Application Status</h2>
                        <p style="font-size: 16px; color: #334155; line-height: 1.6;">
                            Dear <strong>'.htmlspecialchars($tp['centre_name']).'</strong>,<br><br>
                            We regret to inform you that your registration request has been <strong>declined</strong> at this time after our internal review.
                        </p>
                        <p style="font-size: 14px; color: #64748b;">
                            If you believe this is an error or wish to appeal, please contact the administration office directly.
                        </p>
                    </div>
                    <div style="background-color: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; color: #94a3b8;">
                        NIELIT Bhubaneswar Administration
                    </div>
                </div>
            </div>';
            
            send_smtp_email($tp['email'], $subject, $body);
        }

        $_SESSION['flash_msg'] = '❌ Partner Rejected & Notified.';
        header('Location: admin_dashboard.php'); exit;
    }

    if ($action === 'delete_tp' && !empty($_POST['tp_id'])) {
        $tp_id = intval($_POST['tp_id']);
        $pdo->prepare('DELETE FROM tps WHERE id = :id')->execute(['id' => $tp_id]);
        $_SESSION['flash_msg'] = '🗑 Partner Deleted.';
        header('Location: admin_dashboard.php'); exit;
    }

    // Exam Actions
    if ($action === 'create_exam') {
        $title = trim($_POST['title'] ?? '');
        $exam_date = $_POST['exam_date'] ?? null;
        $price = floatval($_POST['price'] ?? 0);
        if ($title && $exam_date) {
            $pdo->prepare('INSERT INTO exams (title, exam_date, price_per_student, is_active) VALUES (:t,:d,:p,1)')
                ->execute(['t'=>$title,'d'=>$exam_date,'p'=>$price]);
            $_SESSION['flash_msg'] = '🧾 Exam Created.';
        }
        header('Location: admin_dashboard.php'); exit;
    }

    if ($action === 'toggle_exam' && !empty($_POST['exam_id'])) {
        $id = intval($_POST['exam_id']);
        $new_state = intval($_POST['new_state']);
        $pdo->prepare('UPDATE exams SET is_active = :s WHERE id = :id')->execute(['s'=>$new_state,'id'=>$id]);
        $_SESSION['flash_msg'] = '✅ Status Updated.';
        header('Location: admin_dashboard.php'); exit;
    }

    if ($action === 'delete_exam' && !empty($_POST['exam_id'])) {
        $id = intval($_POST['exam_id']);
        $pdo->prepare('DELETE FROM exams WHERE id = :id')->execute(['id'=>$id]);
        $_SESSION['flash_msg'] = '🗑 Exam Deleted.';
        header('Location: admin_dashboard.php'); exit;
    }

    // Payment Actions
    // --- PAYMENT ACTIONS (Professional & Futuristic) ---

    // 1. APPROVE PAYMENT
    if ($action === 'approve_payment' && !empty($_POST['submission_id'])) {
        $submission_id = intval($_POST['submission_id']);
        
        // Update Status
        $pdo->prepare('UPDATE submissions SET payment_status = "paid", paid_at = NOW() WHERE id = :id')->execute(['id' => $submission_id]);
        
        // Fetch Details for Email
        $q = $pdo->prepare('SELECT s.total_amount, s.batch_name, s.paid_at, t.email, t.centre_name FROM submissions s JOIN tps t ON s.tp_id = t.id WHERE s.id = :id LIMIT 1');
        $q->execute(['id' => $submission_id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['email'])) {
            $subject = "Payment Verified: Batch " . htmlspecialchars($row['batch_name']);
            // FIXED: This will now use Indian Time because of the timezone set at the top
            $payDate = date('d M Y, h:i A'); 
            
            // Success Email Template
            $body = '
            <div style="font-family: \'Segoe UI\', sans-serif; background-color: #f0fdf4; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #bbf7d0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center;">
                        <div style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <span style="font-size: 30px; color: white;">&#10003;</span>
                        </div>
                        <h1 style="color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 0.5px;">PAYMENT SUCCESSFUL</h1>
                    </div>

                    <div style="padding: 40px 30px;">
                        <p style="font-size: 16px; color: #374151; margin-bottom: 25px;">
                            Dear <strong>'.htmlspecialchars($row['centre_name']).'</strong>,<br><br>
                            We have successfully verified your payment. Your batch submission has been processed and approved.
                        </p>

                        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="color: #64748b; font-size: 13px; padding-bottom: 8px;">Transaction ID</td>
                                    <td style="text-align: right; color: #1e293b; font-weight: bold; font-size: 13px;">#'.$submission_id.'</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-size: 13px; padding-bottom: 8px;">Batch Name</td>
                                    <td style="text-align: right; color: #1e293b; font-weight: bold; font-size: 13px;">'.htmlspecialchars($row['batch_name']).'</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748b; font-size: 13px; padding-bottom: 8px;">Date</td>
                                    <td style="text-align: right; color: #1e293b; font-weight: bold; font-size: 13px;">'.$payDate.'</td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 10px; border-top: 1px dashed #cbd5e1; color: #059669; font-weight: bold;">AMOUNT PAID</td>
                                    <td style="padding-top: 10px; border-top: 1px dashed #cbd5e1; text-align: right; color: #059669; font-weight: bold; font-size: 18px;">₹'.number_format($row['total_amount'], 2).'</td>
                                </tr>
                            </table>
                        </div>

                        <div style="text-align: center; margin-top: 30px;">
                            <p style="font-size: 13px; color: #9ca3af;">No further action is required from your side.</p>
                        </div>
                    </div>
                </div>
            </div>';

            send_smtp_email($row['email'], $subject, $body);
        }

        $_SESSION['flash_msg'] = '✔ Payment Verified & Partner Notified.';
        header('Location: admin_dashboard.php'); exit;
    }

    // 2. REJECT PAYMENT
    if ($action === 'reject_payment' && !empty($_POST['submission_id'])) {
        $submission_id = intval($_POST['submission_id']);
        
        // Fetch Details FIRST (Needed for email)
        $q = $pdo->prepare('SELECT s.total_amount, s.batch_name, t.email, t.centre_name FROM submissions s JOIN tps t ON s.tp_id = t.id WHERE s.id = :id LIMIT 1');
        $q->execute(['id' => $submission_id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);

        // Update Status
        $pdo->prepare('UPDATE submissions SET payment_status = "receipt_rejected" WHERE id = :id')->execute(['id' => $submission_id]);

        if ($row && !empty($row['email'])) {
            $subject = "Action Required: Payment Receipt Rejected";
            
            // Determine Dashboard URL (Dynamic)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $dash_link = "$protocol://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/tp_dashboard.php";

            // Warning Email Template
            $body = '
            <div style="font-family: \'Segoe UI\', sans-serif; background-color: #fff1f2; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #fecaca; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    
                    <div style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); padding: 30px; text-align: center;">
                        <h1 style="color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 0.5px;">RECEIPT REJECTED</h1>
                    </div>

                    <div style="padding: 40px 30px;">
                        <p style="font-size: 16px; color: #374151; margin-bottom: 20px;">
                            Dear <strong>'.htmlspecialchars($row['centre_name']).'</strong>,
                        </p>
                        <p style="font-size: 15px; color: #374151; line-height: 1.6;">
                            The payment receipt uploaded for Batch <strong>'.htmlspecialchars($row['batch_name']).'</strong> (Amount: ₹'.number_format($row['total_amount'], 2).') was rejected during verification.
                        </p>

                        <div style="background-color: #fff1f2; border-left: 4px solid #ef4444; padding: 15px; margin: 25px 0;">
                            <p style="margin: 0; color: #991b1b; font-size: 14px;"><strong>Likely Reason:</strong> The screenshot was blurry, the transaction ID was missing, or the amount did not match.</p>
                        </div>

                        <div style="text-align: center; margin: 30px 0;">
                            <a href="'.$dash_link.'" style="background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 14px;">Re-upload Receipt</a>
                        </div>
                        
                        <p style="font-size: 13px; color: #6b7280; text-align: center;">Please verify the details and upload a valid proof of payment immediately to avoid delays.</p>
                    </div>
                </div>
            </div>';

            send_smtp_email($row['email'], $subject, $body);
        }

        $_SESSION['flash_msg'] = '❌ Receipt Rejected & User Notified.';
        header('Location: admin_dashboard.php'); exit;
    }
} // --- FIXED: CLOSED THE POST BLOCK HERE ---

// Retrieve flash message if exists
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

include __DIR__ . '/includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* MODERN LIGHT THEME (SaaS Style) */
    :root {
        --primary: #4338ca; /* Indigo-700 */
        --secondary: #64748b;
        --success: #059669;
        --bg-body: #f1f5f9; /* Slate-100 */
        --bg-card: #ffffff;
        --text-main: #1e293b;
    }
    body { background-color: var(--bg-body); font-family: 'Inter', system-ui, sans-serif; color: var(--text-main); }
    
    /* Sticky White Navbar */
    .admin-nav {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        z-index: 1000;
        height: 70px;
    }
    
    /* Layout Spacing */
    .main-content { margin-top: 85px; padding-bottom: 50px; }
    
    /* Cards */
    .stat-card {
        background: var(--bg-card);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    
    .icon-box {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
    }
    
    /* Modern Tables */
    .table-card {
        background: var(--bg-card);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .table thead th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .table tbody td { padding: 1rem; vertical-align: middle; }
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    
    /* Nav Tabs */
    .nav-pills { gap: 0.5rem; }
    .nav-pills .nav-link {
        color: #64748b;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.6rem 1.2rem;
        transition: all 0.2s;
    }
    .nav-pills .nav-link:hover { background-color: #e2e8f0; color: var(--text-main); }
    .nav-pills .nav-link.active {
        background-color: var(--primary);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(67, 56, 202, 0.3);
    }
</style>

<nav class="navbar navbar-expand-lg admin-nav fixed-top px-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-dark" href="#">
            <div class="bg-indigo-700 text-white rounded p-1 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span>Admin<span class="text-indigo-700">Console</span></span>
        </a>
        
        <div class="d-flex align-items-center gap-4">
            <div class="d-none d-md-block text-end">
                <div class="text-xs text-muted fw-bold text-uppercase tracking-wider">System Time</div>
                <div class="text-sm fw-bold text-dark font-monospace" id="clock">--:--:--</div>
            </div>
            <div class="vr bg-secondary opacity-25"></div>
            <form method="post" action="logout.php" class="m-0">
                <button class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-power-off me-2"></i>Logout
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid main-content px-4">

    <?php if ($msg): ?>
    <div class="alert alert-success d-flex align-items-center shadow-sm border-0 rounded-3 mb-4 animate__animated animate__fadeInDown bg-emerald-50 text-emerald-800 border-start border-4 border-emerald-500">
        <i class="fa-solid fa-circle-check fs-4 me-3 text-emerald-600"></i>
        <div><strong>Success:</strong> <?= htmlspecialchars($msg) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#overview"><i class="fa-solid fa-chart-pie me-2"></i>Overview</button></li>
        <li class="nav-item position-relative">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#partners">
                <i class="fa-solid fa-users me-2"></i>Partners
                <?php if(count($pending)>0) echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">'.count($pending).'</span>'; ?>
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#exams"><i class="fa-solid fa-file-contract me-2"></i>Exams</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#finance"><i class="fa-solid fa-wallet me-2"></i>Verification</button></li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="overview">
            <div class="row g-4">
                <?php 
                $kpis = [
                    ['Total Revenue', '₹'.number_format($total_revenue), 'fa-indian-rupee-sign', 'text-emerald-600', 'bg-emerald-50'],
                    ['Active Partners', $total_tps, 'fa-building-user', 'text-indigo-600', 'bg-indigo-50'],
                    ['Total Students', $total_students, 'fa-user-graduate', 'text-cyan-600', 'bg-cyan-50'],
                    ['Submissions', $total_subs, 'fa-clipboard-list', 'text-amber-600', 'bg-amber-50']
                ];
                foreach ($kpis as $k): ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted small fw-bold text-uppercase mb-1 tracking-wide"><?= $k[0] ?></p>
                            <h3 class="fw-bold mb-0 text-slate-800"><?= $k[1] ?></h3>
                        </div>
                        <div class="icon-box <?= $k[4] ?> <?= $k[3] ?>"><i class="fa-solid <?= $k[2] ?>"></i></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="col-12">
                    <div class="table-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0 text-slate-800">Revenue Analytics</h5>
                            <span class="badge bg-indigo-50 text-indigo-700">Real-time Data</span>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="partners">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="table-card h-100">
                        <div class="p-3 border-bottom bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-warning-emphasis mb-0"><i class="fa-solid fa-hourglass-half me-2"></i>Pending Approvals</h6>
                            <span class="badge bg-warning text-dark rounded-pill"><?= count($pending) ?></span>
                        </div>
                        <div class="overflow-auto" style="max-height: 600px;">
                            <?php if (empty($pending)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fa-regular fa-circle-check fs-1 mb-2"></i><br>No pending requests.
                                </div>
                            <?php else: foreach ($pending as $p): ?>
                                <div class="p-3 border-bottom hover:bg-light transition">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="text-dark"><?= htmlspecialchars($p['centre_name']) ?></strong>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <i class="fa-regular fa-user me-1"></i><?= htmlspecialchars($p['username']) ?><br>
                                        <i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($p['email']) ?>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <form method="post" class="flex-fill"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="tp_id" value="<?= $p['id'] ?>"><button name="action" value="approve_tp" class="btn btn-sm btn-success w-100 fw-bold">Approve</button></form>
                                        <form method="post" class="flex-fill"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="tp_id" value="<?= $p['id'] ?>"><button name="action" value="reject_tp" class="btn btn-sm btn-outline-danger w-100 fw-bold">Reject</button></form>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="table-card h-100">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                            <h6 class="fw-bold mb-0 text-slate-800">Partner Ecosystem</h6>
                            <div class="input-group input-group-sm w-auto">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" id="tpSearch" onkeyup="filterTable('tpSearch', 'tpTable')" class="form-control border-start-0 ps-0" placeholder="Search partners...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tpTable">
                                <thead><tr><th>Name / Contact</th><th class="text-center">Performance</th><th class="text-end">Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($registered as $tp): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($tp['centre_name']) ?></div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($tp['username']) ?> <span class="mx-1">•</span> <?= htmlspecialchars($tp['contact_number']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <i class="fa-solid fa-users me-1"></i> <?= $tp['tp_total_students'] ?>
                                            </span>
                                            <div class="small text-success fw-bold mt-1">₹<?= number_format($tp['tp_total_paid']) ?></div>
                                        </td>
                                        <td class="text-end">
                                            <button onclick="openDetails(<?= htmlspecialchars(json_encode($tp), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-sm btn-outline-primary border-0 bg-indigo-50 text-indigo-700"><i class="fa-regular fa-eye"></i> Details</button>
                                            
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this Partner?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="tp_id" value="<?= $tp['id'] ?>">
                                                <button name="action" value="delete_tp" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="exams">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="stat-card">
                        <h5 class="fw-bold mb-4 text-slate-800">Create New Exam</h5>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="create_exam">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Exam Title</label>
                                <input name="title" class="form-control bg-light" placeholder="e.g. CCC Jan 2026" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Date & Time</label>
                                <input name="exam_date" type="datetime-local" class="form-control bg-light" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Price per Student (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">₹</span>
                                    <input name="price" type="number" step="0.01" class="form-control bg-light border-start-0" placeholder="0.00" required>
                                </div>
                            </div>
                            <button class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Launch Exam <i class="fa-solid fa-rocket ms-2"></i></button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="table-card">
                        <div class="p-3 border-bottom bg-white"><h6 class="fw-bold mb-0 text-slate-800">Exam History</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Status</th><th>Title</th><th>Date</th><th>Price</th><th class="text-end">Controls</th></tr></thead>
                                <tbody>
                                    <?php foreach ($exams as $e): ?>
                                    <tr>
                                        <td>
                                            <span class="badge rounded-pill <?= $e['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $e['is_active'] ? 'Live' : 'Offline' ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($e['title']) ?></td>
                                        <td class="text-muted small"><?= date('d M, Y h:i A', strtotime($e['exam_date'])) ?></td>
                                        <td class="fw-bold text-emerald-600">₹<?= $e['price_per_student'] ?></td>
                                        <td class="text-end">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                                <input type="hidden" name="new_state" value="<?= $e['is_active'] ? 0 : 1 ?>">
                                                <button name="action" value="toggle_exam" class="btn btn-sm btn-light border me-1"><i class="fa-solid fa-power-off"></i></button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                                <button name="action" value="delete_exam" class="btn btn-sm btn-light text-danger border"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="finance">
            <div class="table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h6 class="mb-0 fw-bold text-slate-800">Submission Verification</h6>
                    <div class="input-group input-group-sm w-auto">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="subSearch" onkeyup="filterTable('subSearch', 'subTable')" class="form-control border-start-0 ps-0" placeholder="Search batch...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="subTable">
                        <thead><tr><th>#</th><th>TP Info</th><th>Batch Details</th><th>Students</th><th>Amount</th><th>Files</th><th>Status</th><th class="text-end">Verify</th></tr></thead>
                        <tbody>
                            <?php foreach ($submissions as $s): 
                                $st = $s['payment_status'];
                                $badge = ($st==='paid') ? 'bg-success' : (($st==='receipt_uploaded') ? 'bg-warning text-dark' : 'bg-secondary');
                            ?>
                            <tr>
                                <td class="text-muted small"><?= $s['submission_id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['tp_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($s['tp_username']) ?></div>
                                </td>
                                <td>
                                    <div class="text-primary small fw-bold"><?= htmlspecialchars($s['exam_title']) ?></div>
                                    <div class="small"><?= htmlspecialchars($s['batch_name']) ?></div>
                                </td>
                                <td class="text-center fw-bold"><?= $s['total_students'] ?></td>
                                <td class="fw-bold text-emerald-600">₹<?= number_format($s['total_amount'],2) ?></td>
                                <td>
                                    <?php if($s['upload_filename']): ?><a href="<?= $s['upload_filename'] ?>" download class="text-secondary me-2 hover:text-dark"><i class="fa-solid fa-file-excel fs-5"></i></a><?php endif; ?>
                                    <?php if($s['payment_receipt']): ?><a href="<?= $s['payment_receipt'] ?>" target="_blank" class="text-primary hover:text-indigo-800"><i class="fa-solid fa-file-image fs-5"></i></a><?php endif; ?>
                                </td>
                                <td><span class="badge <?= $badge ?> rounded-pill"><?= ucfirst(str_replace('_',' ',$st)) ?></span></td>
                                <td class="text-end">
                                    <?php if($st === 'receipt_uploaded'): ?>
                                    <div class="btn-group shadow-sm">
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="submission_id" value="<?= $s['submission_id'] ?>">
                                            <button name="action" value="approve_payment" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Verify Payment?')"><i class="fa-solid fa-check"></i></button>
                                            <button name="action" value="reject_payment" class="btn btn-sm btn-danger fw-bold" onclick="return confirm('Reject?')"><i class="fa-solid fa-xmark"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-slate-800">Partner Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-0" id="m_centre"></h4>
                        <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 mt-2" id="m_accr"></span>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted fw-bold text-uppercase">Total Revenue</div>
                        <div class="h3 fw-bold text-emerald-600 mb-0" id="m_rev">₹0</div>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <div class="small text-muted fw-bold text-uppercase mb-2">Contact</div>
                            <div class="fw-bold text-dark" id="m_contact"></div>
                            <div class="small text-primary text-truncate" id="m_email"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <div class="small text-muted fw-bold text-uppercase mb-2">Location</div>
                            <div class="fw-bold text-dark" id="m_loc"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <div class="small text-muted fw-bold text-uppercase mb-2">Students</div>
                            <div class="h4 fw-bold text-dark mb-0" id="m_stud">0</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold border-bottom pb-2 mb-3 text-slate-700">Recent Transactions</h6>
                <div class="table-responsive rounded border">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Exam</th><th class="text-end">Amount</th><th class="text-center">Status</th></tr></thead>
                        <tbody id="m_history">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Live Clock
    setInterval(() => {
        document.getElementById('clock').innerText = new Date().toLocaleTimeString();
    }, 1000);

    // 2. Client-side Search
    function filterTable(inputId, tableId) {
        let filter = document.getElementById(inputId).value.toLowerCase();
        let rows = document.querySelectorAll(`#${tableId} tbody tr`);
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    // 3. Populate TP Details Modal (FIXED: Safe JSON Handling)
    function openDetails(data) {
        document.getElementById('m_centre').innerText = data.centre_name;
        document.getElementById('m_accr').innerText = data.accreditation_no || 'No Accreditation ID';
        document.getElementById('m_rev').innerText = '₹' + (parseFloat(data.tp_total_paid)||0).toLocaleString('en-IN');
        document.getElementById('m_stud').innerText = data.tp_total_students;
        document.getElementById('m_contact').innerText = data.contact_number;
        document.getElementById('m_email').innerText = data.email;
        document.getElementById('m_loc').innerText = data.location + ' - ' + data.pincode;

        const tbody = document.getElementById('m_history');
        tbody.innerHTML = '';
        
        // This was the missing feature: Checking the history array inside the JSON object
        if(!data.history || data.history.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No transaction history found.</td></tr>';
        } else {
            data.history.forEach(row => {
                let badge = row.payment_status === 'paid' ? 'bg-success' : 'bg-secondary';
                let html = `<tr>
                    <td class="small">${new Date(row.created_at).toLocaleDateString()}</td>
                    <td class="small fw-bold">${row.exam_title}<div class="text-muted fw-normal" style="font-size:0.8em">${row.batch_name}</div></td>
                    <td class="text-end fw-bold small text-success">₹${parseFloat(row.total_amount).toFixed(2)}</td>
                    <td class="text-center"><span class="badge ${badge}" style="font-size:0.7em">${row.payment_status}</span></td>
                </tr>`;
                tbody.innerHTML += html;
            });
        }
        
        new bootstrap.Modal(document.getElementById('detailsModal')).show();
    }

    // 4. Initialize Revenue Chart
    const ctx = document.getElementById('revenueChart');
    if(ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= $jsLabels ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?= $jsValues ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.8)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 14 }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: "'Inter', sans-serif" } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { family: "'Inter', sans-serif" } }
                    }
                }
            }
        });
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ob_end_flush(); ?>