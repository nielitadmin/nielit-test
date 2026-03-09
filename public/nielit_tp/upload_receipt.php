
<?php
// upload_receipt.php (debug-friendly, modern table UI, disables upload when status = paid)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple file logger
function ur_log($msg) {
    $t = "[".date('Y-m-d H:i:s')."] ".$msg."\n";
    @file_put_contents('/tmp/upload_receipt_debug.log', $t, FILE_APPEND);
}

try {
    require_once 'includes/db_connect.php';
    require_once 'includes/functions.php';
} catch (Exception $e) {
    ur_log("Require failure: ".$e->getMessage());
    echo "Server configuration error. Check logs.";
    http_response_code(500);
    exit;
}

$page_title = "Upload Payment Receipt";
require_login_tp();

$tp_id = $_SESSION['tp_id'] ?? null;
if (!$tp_id) {
    ur_log("No TP session");
    redirect('index.php');
}

$err = "";
$success = "";

// Accept submission_id via GET (view) or POST (upload)
$submission_id = intval($_GET['submission_id'] ?? $_POST['submission_id'] ?? 0);

// helper: safe random token fallback
function safe_random_hex($len = 8) {
    if (function_exists('random_bytes')) {
        try {
            return bin2hex(random_bytes($len));
        } catch (Throwable $e) {
            ur_log("random_bytes failed: ".$e->getMessage());
        }
    }
    // fallback
    return substr(bin2hex(uniqid("", true)), 0, $len*2);
}

// If no submission_id provided -> show list of recent submissions for this TP
if (!$submission_id) {
    try {
        $listQ = $pdo->prepare("SELECT id, batch_name, total_students, total_amount, payment_status, payment_receipt, created_at
                                FROM submissions
                                WHERE tp_id = :tp
                                ORDER BY created_at DESC
                                LIMIT 200");
        $listQ->execute(['tp' => $tp_id]);
        $submissions_list = $listQ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        ur_log("DB error fetching list: ".$e->getMessage());
        $submissions_list = [];
    }

    include 'includes/header.php';
    ?>

    <div class="max-w-4xl mx-auto mt-10 mb-20">

        <div class="bg-white shadow-xl rounded-xl overflow-hidden">

            <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                <h2 class="text-xl font-bold">Your Recent Submissions</h2>
                <div class="text-sm">Manage batches, upload receipts and track payment status.</div>
            </div>

            <div class="p-6 space-y-4">

                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                         
                        
                        <a href="tp_dashboard.php" class="text-sm text-indigo-600">Back to Dashboard</a>
                    </div>

                    <div class="w-1/3">
                        <input id="searchInput" type="search" placeholder="Search batch, students, amount, status..." class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="submissionsTable" class="min-w-full divide-y">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Batch</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Students</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Amount</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Status</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Receipt</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600">Created At</th>
                                <th class="px-4 py-2 text-right text-sm font-medium text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y">
                        <?php if (empty($submissions_list)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No submissions found. Create one from <a href="apply_payment.php" class="text-indigo-600">Apply &amp; Pay</a>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions_list as $row): ?>
                                <?php
                                    $status = strtolower(trim($row['payment_status'] ?? 'pending'));
                                    $badge = 'bg-gray-200 text-gray-800';
                                    if (strpos($status, 'pending') !== false) $badge = 'bg-yellow-100 text-yellow-800';
                                    elseif (strpos($status, 'uploaded') !== false) $badge = 'bg-sky-100 text-sky-800';
                                    elseif (strpos($status, 'paid') !== false) $badge = 'bg-green-100 text-green-800';
                                    elseif (strpos($status, 'rejected') !== false) $badge = 'bg-red-100 text-red-800';

                                    $is_paid = ($status === 'paid');
                                ?>
                                <tr>
                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['batch_name']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= intval($row['total_students']) ?></td>
                                    <td class="px-4 py-3 text-sm">₹<?= number_format(floatval($row['total_amount']), 2) ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded <?= $badge ?>">
                                            <?= htmlspecialchars(ucfirst($row['payment_status'] ?? 'Pending')) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if (!empty($row['payment_receipt'])): ?>
                                            <a href="<?= htmlspecialchars($row['payment_receipt']) ?>" target="_blank" class="text-indigo-600 text-sm">View</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?= htmlspecialchars(date("d M Y, H:i", strtotime($row['created_at']))) ?></td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <?php if ($is_paid): ?>
                                            <button class="inline-flex items-center px-3 py-1 bg-gray-300 text-gray-700 rounded text-sm shadow-sm" disabled>Upload</button>
                                        <?php else: ?>
                                            <a href="upload_receipt.php?submission_id=<?= intval($row['id']) ?>" class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white rounded text-sm shadow-sm">Upload</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-sm text-gray-500">
                    Showing <?= count($submissions_list) ?> recent submissions
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const search = document.getElementById('searchInput');
        const tableBody = document.querySelector('#submissionsTable tbody');
        const rows = tableBody ? Array.from(tableBody.rows) : [];

        function norm(s){ return String(s || '').toLowerCase(); }

        search && search.addEventListener('input', function(){
            const q = norm(this.value.trim());
            rows.forEach(r => {
                const text = norm(r.innerText);
                r.style.display = text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    })();
    </script>

    <?php
    include 'includes/footer.php';
    exit;
}

//
// We have a $submission_id -> fetch it and show/upload form
//
try {
    $q = $pdo->prepare("
        SELECT s.*, e.title AS exam_title, e.exam_date
        FROM submissions s
        LEFT JOIN exams e ON s.exam_id = e.id
        WHERE s.id = :id AND s.tp_id = :tp
    ");
    $q->execute(['id' => $submission_id, 'tp' => $tp_id]);
    $submission = $q->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ur_log("DB error fetching submission {$submission_id}: ".$e->getMessage());
    $submission = false;
}

if (!$submission) {
    ur_log("Submission not found or access denied for id={$submission_id}, tp={$tp_id}");
    $_SESSION['flash_msg'] = "Submission not found or you don't have access.";
    redirect('tp_dashboard.php');
}

// Determine if paid (disable uploads if true)
$submission_status_lower = strtolower(trim($submission['payment_status'] ?? ''));
$is_paid_submission = ($submission_status_lower === 'paid');

// Handle POST upload (only run if not paid)
if (!$is_paid_submission && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_receipt'])) {
    ur_log("Upload attempt for submission_id={$submission_id}, tp={$tp_id}");
    if (!isset($_FILES['receipt_file'])) {
        $err = "No file received. Check PHP settings (post_max_size / upload_max_filesize).";
        ur_log("No \$_FILES['receipt_file'] present. \$_FILES keys: ".json_encode(array_keys($_FILES)));
    } else {
        $file = $_FILES['receipt_file'];
        ur_log("File info: name={$file['name']} tmp={$file['tmp_name']} size={$file['size']} error={$file['error']}");

        // Basic validations
        $maxSize = 5 * 1024 * 1024; // 5MB
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'pdf'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = "File upload error code: ".$file['error'];
            ur_log("Upload error code: ".$file['error']);
        } elseif (!in_array($ext, $allowed)) {
            $err = "Invalid file type. Allowed: JPG, PNG, PDF.";
            ur_log("Invalid extension: $ext");
        } elseif ($file['size'] > $maxSize) {
            $err = "File too large. Max allowed is 5 MB.";
            ur_log("File too large: {$file['size']}");
        } else {
            // ensure directory exists
            $dir = __DIR__ . "/uploads/payment_receipts/";
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true)) {
                    $err = "Server error: cannot create upload directory.";
                    ur_log("mkdir failed for $dir");
                } else {
                    ur_log("Created upload directory $dir");
                }
            }

            if (empty($err)) {
                // create unique filename
                $fname = "receipt_tp{$tp_id}_sub{$submission_id}_" . time() . "_" . safe_random_hex(6) . "." . $ext;
                $fullpath = $dir . $fname;
                $relative = "uploads/payment_receipts/" . $fname;

                // debug: ensure tmp exists
                if (!is_uploaded_file($file['tmp_name'])) {
                    ur_log("is_uploaded_file returned false for tmp_name: {$file['tmp_name']}");
                }

                if (!@move_uploaded_file($file['tmp_name'], $fullpath)) {
                    $err = "Failed to move uploaded file. Check directory permissions.";
                    ur_log("move_uploaded_file FAILED. src={$file['tmp_name']} dest={$fullpath} error={$file['error']}. PHP user: " . get_current_user());
                    ur_log("Destination dir writable? " . (is_writable($dir) ? "yes" : "no"));
                } else {
                    ur_log("File moved to $fullpath");

                    // remove previous receipt file if any (optional)
                    if (!empty($submission['payment_receipt']) && is_string($submission['payment_receipt'])) {
                        $prev = __DIR__ . '/' . ltrim($submission['payment_receipt'], '/');
                        if (file_exists($prev)) {
                            @unlink($prev);
                            ur_log("Removed previous receipt $prev");
                        }
                    }

                    // Update DB (no updated_at)
                    try {
                        $upd = $pdo->prepare("UPDATE submissions 
                                               SET payment_receipt = :r, payment_status = 'receipt_uploaded'
                                               WHERE id = :id AND tp_id = :tp");
                        $ok = $upd->execute(['r' => $relative, 'id' => $submission_id, 'tp' => $tp_id]);
                        if ($ok) {
                            $success = "Receipt uploaded successfully.";
                            ur_log("DB updated for submission {$submission_id} with file {$relative}");
                            // refresh submission
                            $q->execute(['id' => $submission_id, 'tp' => $tp_id]);
                            $submission = $q->fetch(PDO::FETCH_ASSOC);

                            // update paid flag in case payment_status changed externally
                            $submission_status_lower = strtolower(trim($submission['payment_status'] ?? ''));
                            $is_paid_submission = ($submission_status_lower === 'paid');
                        } else {
                            $err = "Database update failed.";
                            ur_log("DB update failed for submission {$submission_id}. PDO error info: " . json_encode($pdo->errorInfo()));
                            if (file_exists($fullpath)) @unlink($fullpath);
                        }
                    } catch (Exception $e) {
                        $err = "Database error: " . $e->getMessage();
                        ur_log("Exception updating DB: ".$e->getMessage());
                        if (file_exists($fullpath)) @unlink($fullpath);
                    }
                }
            }
        }
    }
} elseif ($is_paid_submission && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // If somehow a POST arrived for a paid submission, ignore and show message
    ur_log("Blocked upload attempt for paid submission {$submission_id} by tp {$tp_id}");
    $err = "This submission is already marked Paid; uploading a new receipt is disabled.";
}

// render UI matching apply_payment.php
include 'includes/header.php';
?>
<div class="max-w-4xl mx-auto mt-10 mb-20">

    <div class="bg-white shadow-xl rounded-xl overflow-hidden">

        <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
            <h2 class="text-xl font-bold"><?= htmlspecialchars($submission['batch_name'] ?: 'Submission') ?></h2>
            <?php if (!empty($submission['exam_title'])): ?>
                <div class="text-sm">Exam: <?= htmlspecialchars($submission['exam_title']) ?></div>
                <div class="text-sm">Exam Date: <?= !empty($submission['exam_date']) ? date("d M Y, H:i", strtotime($submission['exam_date'])) : '-' ?></div>
            <?php endif; ?>
        </div>

        <div class="p-6 grid md:grid-cols-2 gap-6">

            <!-- QR (left column kept for visual balance like apply_payment.php) -->
            <div class="border rounded-lg p-4 text-center">
                <p class="font-medium text-gray-700 mb-2">Scan to Pay (SBI UPI)</p>
                <div class="bg-white shadow p-4 rounded-lg inline-block">
                    <embed src="assets/SBI_QR Code.pdf" width="260" height="260">
                </div>
                <p class="text-sm mt-2 text-gray-700">
                    UPI ID: <b>nielitbbsr@sbi</b><br>
                    Pay exact amount then upload receipt.
                </p>
            </div>

            <!-- FORM (right column) -->
            <div class="space-y-4">

                <?php if ($err): ?>
                    <div class="bg-red-50 text-red-700 border p-2 rounded"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-700 border p-2 rounded"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <!-- Summary -->
                <div class="text-sm space-y-1">
                    <p>Batch: <b><?= htmlspecialchars($submission['batch_name']) ?></b></p>
                    <p>Students: <b><?= intval($submission['total_students']) ?></b></p>
                    <p>Amount: <b>₹<?= number_format($submission['total_amount'],2) ?></b></p>
                    <p>Status: <b><?= htmlspecialchars(ucfirst($submission['payment_status'] ?? 'pending')) ?></b></p>
                </div>

                <hr>

                <!-- Receipt Upload -->
                <?php if ($is_paid_submission): ?>
                    <div class="p-3 border rounded bg-gray-50 text-sm text-gray-700">
                        Payment is already marked <strong>Paid</strong>. Uploading new receipts is disabled for this submission.
                        <?php if (!empty($submission['payment_receipt'])): ?>
                            <div class="mt-2">
                                Existing Receipt: <a href="<?= htmlspecialchars($submission['payment_receipt']) ?>" target="_blank" class="text-indigo-600">View</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data" class="space-y-3">
                        <input type="hidden" name="submission_id" value="<?= intval($submission['id']) ?>">
                        <input type="hidden" name="upload_receipt" value="1">

                        <label class="text-sm">Upload Payment Receipt
                            <input type="file" name="receipt_file" accept=".png,.jpg,.jpeg,.pdf" class="mt-1 block w-full" required>
                        </label>

                        <?php if (!empty($submission['payment_receipt'])): ?>
                            <p class="text-sm">
                                Existing Receipt:
                                <a href="<?= htmlspecialchars($submission['payment_receipt']) ?>" target="_blank" class="text-indigo-600">View</a>
                            </p>
                        <?php endif; ?>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded shadow">Upload Receipt</button>
                            <a href="upload_receipt.php" class="text-sm text-indigo-600">Back to Submissions</a>
                            <a href="tp_dashboard.php" class="text-sm text-indigo-600">Dashboard</a>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
