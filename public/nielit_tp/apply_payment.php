<?php
// apply_payment.php

// 1. Start Session & Connect DB (MUST BE FIRST)
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 2. Load Excel Library (Check if exists to avoid fatal error)
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    // Fallback or Error if composer not installed
    // We will handle this check inside the logic below
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 3. Login Check
require_login_tp();
$tp_id = $_SESSION['tp_id'] ?? null;
if (!$tp_id) redirect('index.php');

// 4. Fetch Exam Data (Needed for calculations)
$exam_id = intval($_GET['exam_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id=:id AND is_active=1");
$stmt->execute(['id'=>$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    $_SESSION['flash_msg'] = "Invalid or inactive exam selected.";
    redirect("tp_dashboard.php");
}

$err = "";
$success = "";

// ---------------- 5. Handle Sample Download (Before HTML) ----------------
if (isset($_GET['download_sample'])) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        die("Error: PhpSpreadsheet library not installed. Please run 'composer require phpoffice/phpspreadsheet'");
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Candidate Name');
    $sheet->setCellValue('B1', 'Father Name');
    $sheet->setCellValue('C1', 'Mobile Number');
    $sheet->setCellValue('D1', 'Email ID');
    $sheet->setCellValue('E1', 'Aadhar Number');
    
    // Add dummy data
    $sheet->setCellValue('A2', 'Rahul Kumar');
    $sheet->setCellValue('B2', 'Suresh Kumar');
    $sheet->setCellValue('C2', '9876543210');
    $sheet->setCellValue('D2', 'rahul@example.com');
    $sheet->setCellValue('E2', '123456789012');

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Student_Data_Format.xlsx"');
    $writer->save('php://output');
    exit;
}

// ---------------- 6. LOGIC: CREATE SUBMISSION (Excel Upload) ----------------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_submission'])) {
    $batch_name = trim($_POST['batch_name']);
    $file = $_FILES['students_file'] ?? null;

    // Check if library exists
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $err = "System Error: Excel library is missing. Contact Admin.";
    } elseif (empty($batch_name) || !$file || $file['size'] == 0) {
        $err = "Please enter a batch name and upload a valid Excel file.";
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx','xls','csv'])) {
            $err = "Invalid file format. Please upload .xlsx, .xls, or .csv.";
        } else {
            // Processing
            $dir = __DIR__ . "/uploads/student_lists/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            
            $fname = "students_tp{$tp_id}_" . time() . "." . $ext;
            $full_path = $dir . $fname;

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                try {
                    $reader = IOFactory::createReaderForFile($full_path);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($full_path);
                    $sheet = $spreadsheet->getActiveSheet();
                    $highestRow = $sheet->getHighestDataRow();

                    // Validation: Ensure there is data beyond the header
                    if ($highestRow < 2) {
                        $err = "The uploaded file appears to be empty (only headers found).";
                        @unlink($full_path); // Delete invalid file
                    } else {
                        $total_students = $highestRow - 1; // Subtract header
                        $amount = $total_students * floatval($exam['price_per_student']);

                        // Get Center Name
                        $tp = $pdo->prepare("SELECT centre_name FROM tps WHERE id=:id");
                        $tp->execute(['id'=>$tp_id]);
                        $center_name = $tp->fetch()['centre_name'];

                        // Database Insert
                        $sql = "INSERT INTO submissions (tp_id, exam_id, batch_name, center_name, upload_filename, total_students, total_amount, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                        // Note: Fixed column name 'center_name' to 'tp_name' based on previous context, 
                        // IF your DB uses 'center_name', change 'tp_name' back to 'center_name' in the SQL above.
                        
                        $stmt = $pdo->prepare($sql);
                        // Save relative path for DB
                        $db_filename = "uploads/student_lists/".$fname;
                        
                        $stmt->execute([$tp_id, $exam_id, $batch_name, $center_name, $db_filename, $total_students, $amount]);
                        
                        $new_sub_id = $pdo->lastInsertId();
                        
                        // REDIRECT (This works now because headers aren't sent yet)
                        header("Location: apply_payment.php?exam_id={$exam_id}&submission_id={$new_sub_id}");
                        exit;
                    }
                } catch (Exception $e) {
                    $err = "Error reading file: " . $e->getMessage();
                }
            } else {
                $err = "Failed to save the uploaded file to server.";
            }
        }
    }
}

// ---------------- 7. LOGIC: UPLOAD RECEIPT ----------------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_receipt'])) {
    $sub_id = intval($_POST['submission_id']);
    $file = $_FILES['receipt_file'] ?? null;

    if (!$file || $file['size'] == 0) {
        $err = "Please select a receipt file to upload.";
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            $err = "Invalid receipt format. Allowed: JPG, PNG, PDF.";
        } else {
            $dir = __DIR__ . "/uploads/payment_receipts/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $fname = "receipt_tp{$tp_id}_sub{$sub_id}_" . time() . "." . $ext;
            
            if (move_uploaded_file($file['tmp_name'], $dir . $fname)) {
                $db_path = "uploads/payment_receipts/" . $fname;
                $pdo->prepare("UPDATE submissions SET payment_receipt=?, payment_status='receipt_uploaded', updated_at=NOW() WHERE id=? AND tp_id=?")
                    ->execute([$db_path, $sub_id, $tp_id]);
                
                $success = "Payment receipt uploaded successfully! Admin will verify shortly.";
                // Refresh page to show success state
                // header("Location: apply_payment.php?exam_id={$exam_id}&submission_id={$sub_id}");
                // exit;
            } else {
                $err = "Failed to upload receipt file.";
            }
        }
    }
}

// ---------------- 8. Fetch Data for View ----------------
$submission_id = intval($_GET['submission_id'] ?? 0);
$submission = null;
if ($submission_id) {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id=? AND tp_id=?");
    $stmt->execute([$submission_id, $tp_id]);
    $submission = $stmt->fetch();
}

// Determine Current Step
$step = 1;
if ($submission) {
    $step = ($submission['payment_status'] == 'pending') ? 2 : 3;
}

// ---------------- 9. NOW Include Header (Safe to Output HTML) ----------------
$page_title = 'Exam Registration & Payment';
include 'includes/header.php';
?>

<div class="max-w-5xl mx-auto mt-8 mb-20 px-4">

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-800">Exam Registration</h1>
        <p class="text-gray-500 mt-1">Register candidates for <span class="text-indigo-600 font-semibold"><?= htmlspecialchars($exam['title']) ?></span></p>
    </div>

    <div class="flex items-center justify-center mb-10">
        <div class="flex items-center w-full max-w-2xl">
            <div class="relative flex flex-col items-center flex-1">
                <div class="w-10 h-10 flex items-center justify-center rounded-full <?= $step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' ?> font-bold z-10">1</div>
                <div class="text-xs mt-2 font-medium <?= $step >= 1 ? 'text-indigo-600' : 'text-gray-500' ?>">Upload Data</div>
                <div class="absolute top-5 left-1/2 w-full h-1 <?= $step > 1 ? 'bg-indigo-600' : 'bg-gray-200' ?> -z-0"></div>
            </div>
            <div class="relative flex flex-col items-center flex-1">
                <div class="w-10 h-10 flex items-center justify-center rounded-full <?= $step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' ?> font-bold z-10">2</div>
                <div class="text-xs mt-2 font-medium <?= $step >= 2 ? 'text-indigo-600' : 'text-gray-500' ?>">Make Payment</div>
                <div class="absolute top-5 left-1/2 w-full h-1 <?= $step > 2 ? 'bg-indigo-600' : 'bg-gray-200' ?> -z-0"></div>
            </div>
            <div class="relative flex flex-col items-center flex-1">
                <div class="w-10 h-10 flex items-center justify-center rounded-full <?= $step >= 3 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' ?> font-bold z-10">3</div>
                <div class="text-xs mt-2 font-medium <?= $step >= 3 ? 'text-green-600' : 'text-gray-500' ?>">Status</div>
            </div>
        </div>
    </div>

    <?php if ($err): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Error</p>
            <p><?= $err ?></p>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Success</p>
            <p><?= $success ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <?php if (!$submission): ?>
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Upload Candidate List</h2>
                    <a href="?exam_id=<?= $exam_id ?>&download_sample=1" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Sample Format
                    </a>
                </div>

                <form method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="create_submission" value="1">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch Name / Reference</label>
                        <input type="text" name="batch_name" placeholder="e.g. Batch A - Oct 2025" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                    </div>

                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:bg-gray-50 transition relative">
                        <input type="file" name="students_file" accept=".xlsx,.xls,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="document.getElementById('file-name').innerText = this.files[0].name">
                        <div class="space-y-2 pointer-events-none">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <span class="font-medium text-indigo-600 hover:text-indigo-500">Upload a file</span>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">Excel or CSV up to 10MB</p>
                            <p id="file-name" class="text-sm font-bold text-gray-800 mt-2"></p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow transition transform hover:-translate-y-0.5">
                            Proceed to Payment
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="grid md:grid-cols-5 min-h-[500px]">
                
                <div class="md:col-span-2 bg-gray-50 p-8 border-r border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Order Summary</h3>
                    
                    <div class="space-y-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm border">
                            <span class="text-xs text-gray-500 uppercase font-semibold">Batch Name</span>
                            <div class="text-gray-900 font-medium"><?= htmlspecialchars($submission['batch_name']) ?></div>
                        </div>

                        <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border">
                            <div>
                                <span class="text-xs text-gray-500 uppercase font-semibold">Total Students</span>
                                <div class="text-xl font-bold text-gray-900"><?= $submission['total_students'] ?></div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-500 uppercase font-semibold">Price/Student</span>
                                <div class="text-gray-700">₹<?= $exam['price_per_student'] ?></div>
                            </div>
                        </div>

                        <div class="bg-indigo-600 text-white p-5 rounded-xl shadow-lg mt-6">
                            <span class="text-indigo-200 text-sm uppercase font-semibold">Total Payable</span>
                            <div class="text-3xl font-bold mt-1">₹<?= number_format($submission['total_amount'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3 p-8">
                    
                    <?php if ($submission['payment_status'] == 'pending'): ?>
                        <h2 class="text-xl font-bold text-gray-800 mb-6">Scan & Pay</h2>

                        <div class="flex flex-col items-center gap-6">
                            
                            <div class="bg-white p-4 rounded-2xl shadow-lg border border-gray-200 w-full max-w-sm">
                                <div class="aspect-square relative w-full rounded-lg overflow-hidden border border-gray-100">
                                    <iframe src="assets/SBI_QR Code.pdf#toolbar=0&navpanes=0&scrollbar=0" class="absolute inset-0 w-full h-full object-contain"></iframe>
                                </div>
                                <div class="text-center mt-3">
                                    <p class="text-sm font-semibold text-gray-600">Scan with any UPI App</p>
                                    <div class="flex justify-center gap-3 mt-2 text-2xl text-gray-400">
                                        <i class="fa-brands fa-google-pay hover:text-gray-600"></i>
                                        <i class="fa-brands fa-amazon-pay hover:text-gray-600"></i>
                                        <i class="fa-solid fa-mobile-screen-button hover:text-gray-600"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="w-full max-w-sm space-y-4">
                                <div>
                                    <label class="text-xs text-gray-500 uppercase font-semibold">Or Pay to UPI ID</label>
                                    <div class="flex items-center gap-2 mt-1">
                                        <code class="bg-gray-100 px-4 py-3 rounded-lg text-gray-800 font-mono text-base border border-gray-300 flex-1 text-center">nielitbbsr@sbi</code>
                                        <button onclick="navigator.clipboard.writeText('nielitbbsr@sbi'); alert('UPI ID Copied!');" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 p-3 rounded-lg transition" title="Copy ID">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="text-sm text-amber-800 bg-amber-50 p-4 rounded-lg border border-amber-200 flex gap-3 items-start">
                                    <i class="fas fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                                    <span>Please ensure you transfer exactly <b>₹<?= number_format($submission['total_amount']) ?></b>. Mismatched amounts may delay verification.</span>
                                </div>
                            </div>
                        </div>

                        <hr class="my-8 border-gray-100">

                        <h3 class="font-bold text-gray-800 mb-3">Upload Payment Receipt</h3>
                        <form method="post" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-4 items-end">
                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                            <input type="hidden" name="upload_receipt" value="1">

                            <div class="w-full">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Screenshot/PDF</label>
                                <input type="file" name="receipt_file" accept=".png,.jpg,.jpeg,.pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer border border-gray-200 rounded-lg" required>
                            </div>
                            <button class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg shadow-md transition font-semibold">
                                Submit Receipt
                            </button>
                        </form>

                    <?php else: ?>
                        <div class="text-center py-10 h-full flex flex-col justify-center">
                            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-800 mb-2">Submission Received!</h2>
                            <p class="text-gray-500 max-w-md mx-auto leading-relaxed">Your list and payment receipt have been securely uploaded. Our admin team will verify the details and approve your batch shortly.</p>
                            
                            <div class="mt-10 flex justify-center gap-4">
                                <a href="<?= $submission['payment_receipt'] ?>" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium transition shadow-sm">
                                    <i class="fa-regular fa-eye"></i> View Receipt
                                </a>
                                <a href="tp_dashboard.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition shadow-md hover:shadow-lg">
                                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>