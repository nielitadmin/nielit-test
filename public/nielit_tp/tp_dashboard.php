<?php
// tp_dashboard.php - Futuristic Redesign
// 1. Start Session & Connect DB
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = 'TP Dashboard';
require_login_tp();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

$tp_id = $_SESSION['tp_id'] ?? null;
if (!$tp_id) redirect('index.php');

// --- HANDLE DELETE (Logic Unchanged) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    $token = $_POST['csrf_token'] ?? '';
    $del_id = intval($_POST['submission_id'] ?? 0);

    if (!hash_equals($csrf_token, (string)$token)) {
        $_SESSION['flash_msg'] = "Invalid request (CSRF).";
        redirect('tp_dashboard.php');
    }

    // Verify ownership
    $stmtS = $pdo->prepare("SELECT upload_filename, payment_receipt, payment_status, tp_id FROM submissions WHERE id = :id LIMIT 1");
    $stmtS->execute(['id' => $del_id]);
    $row = $stmtS->fetch(PDO::FETCH_ASSOC);

    if ($row && intval($row['tp_id']) === intval($tp_id)) {
        $status = strtolower(trim($row['payment_status'] ?? ''));
        if ($status !== 'paid') {
            // Delete files
            if (!empty($row['upload_filename'])) @unlink(__DIR__ . '/' . ltrim($row['upload_filename'], '/'));
            if (!empty($row['payment_receipt'])) @unlink(__DIR__ . '/' . ltrim($row['payment_receipt'], '/'));
            
            // Delete DB
            $del = $pdo->prepare("DELETE FROM submissions WHERE id = :id");
            if ($del->execute(['id' => $del_id])) {
                $_SESSION['flash_msg'] = "Submission deleted successfully.";
            }
        } else {
            $_SESSION['flash_msg'] = "Cannot delete paid submissions.";
        }
    }
    redirect('tp_dashboard.php');
    exit;
}

// --- FETCH DATA ---
// TP Details
$tpInfo = $pdo->prepare("SELECT * FROM tps WHERE id = :id LIMIT 1");
$tpInfo->execute(['id' => $tp_id]);
$tpData = $tpInfo->fetch(PDO::FETCH_ASSOC);

// Active Exams
$exams = $pdo->query("SELECT * FROM exams WHERE is_active = 1 ORDER BY exam_date DESC")->fetchAll();

// Submissions
$subs = $pdo->prepare("
    SELECT s.*, e.title, e.exam_date 
    FROM submissions s
    JOIN exams e ON s.exam_id = e.id
    WHERE s.tp_id = :tp 
    ORDER BY s.created_at DESC
");
$subs->execute(['tp' => $tp_id]);
$submissions = $subs->fetchAll();

// --- CALCULATE DASHBOARD STATS ---
$total_students_enrolled = 0;
$total_amount_spent = 0;
$pending_requests = 0;

foreach ($submissions as $s) {
    $total_students_enrolled += intval($s['total_students']);
    if ($s['payment_status'] === 'paid') {
        $total_amount_spent += floatval($s['total_amount']);
    }
    if ($s['payment_status'] === 'pending' || $s['payment_status'] === 'receipt_uploaded') {
        $pending_requests++;
    }
}

// Flash message
$msg = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

include 'includes/header.php';
?>

<div class="fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
    <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">
                Dashboard Overview
            </h1>
            <p class="text-slate-500 mt-1">
                Welcome back, <span class="font-semibold text-indigo-600"><?= htmlspecialchars($tpData['username']) ?></span>. Here's what's happening today.
            </p>
        </div>
        
        <form method="post" action="tp_logout.php">
            <button class="group flex items-center gap-2 px-5 py-2.5 bg-white/60 backdrop-blur-sm border border-slate-200 rounded-full text-slate-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all shadow-sm">
                <i class="fa-solid fa-right-from-bracket transition-transform group-hover:translate-x-1"></i>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="relative overflow-hidden bg-white/70 backdrop-blur-xl p-6 rounded-2xl border border-white/40 shadow-xl group hover:-translate-y-1 transition duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-user-graduate text-6xl text-indigo-600"></i>
            </div>
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Students</div>
            <div class="text-3xl font-bold text-slate-800 mt-2"><?= number_format($total_students_enrolled) ?></div>
            <div class="text-xs text-green-600 mt-1 font-medium flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up"></i> Enrolled candidates
            </div>
        </div>

        <div class="relative overflow-hidden bg-white/70 backdrop-blur-xl p-6 rounded-2xl border border-white/40 shadow-xl group hover:-translate-y-1 transition duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-wallet text-6xl text-emerald-600"></i>
            </div>
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Paid</div>
            <div class="text-3xl font-bold text-slate-800 mt-2">₹<?= number_format($total_amount_spent) ?></div>
            <div class="text-xs text-emerald-600 mt-1 font-medium flex items-center gap-1">
                <i class="fa-solid fa-check-circle"></i> Verified payments
            </div>
        </div>

        <div class="relative overflow-hidden bg-white/70 backdrop-blur-xl p-6 rounded-2xl border border-white/40 shadow-xl group hover:-translate-y-1 transition duration-300">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                <i class="fa-solid fa-hourglass-half text-6xl text-amber-500"></i>
            </div>
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wider">Pending Action</div>
            <div class="text-3xl font-bold text-slate-800 mt-2"><?= $pending_requests ?></div>
            <div class="text-xs text-amber-600 mt-1 font-medium flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation"></i> Requiring attention
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-8 space-y-8">
            
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2 h-8 bg-indigo-500 rounded-full"></span> Available Exams
                    </h2>
                    <span class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full"><?= count($exams) ?> Active</span>
                </div>

                <?php if (empty($exams)): ?>
                    <div class="bg-white/50 border border-dashed border-slate-300 rounded-xl p-8 text-center text-slate-500">
                        No active exams available right now.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($exams as $ex): ?>
                        <div class="group relative bg-white/80 backdrop-blur-md rounded-xl p-5 border border-white shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-indigo-500 to-purple-500 group-hover:w-1.5 transition-all"></div>
                            
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pl-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">
                                        <?= htmlspecialchars($ex['title']) ?>
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-slate-600">
                                        <div class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-md">
                                            <i class="fa-regular fa-calendar text-indigo-500"></i>
                                            <?= date('d M Y, h:i A', strtotime($ex['exam_date'])) ?>
                                        </div>
                                        <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                                            <i class="fa-solid fa-tag text-emerald-500"></i>
                                            ₹<?= number_format($ex['price_per_student'],2) ?> <span class="text-xs font-normal text-slate-400">/student</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <a href="apply_payment.php?exam_id=<?= $ex['id'] ?>" class="whitespace-nowrap bg-slate-900 text-white px-6 py-2.5 rounded-lg font-medium shadow-lg shadow-indigo-500/20 hover:bg-indigo-600 hover:shadow-indigo-600/30 hover:-translate-y-0.5 transition-all duration-300">
                                    Apply Now <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2 h-8 bg-emerald-500 rounded-full"></span> Recent Submissions
                    </h2>
                    
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="searchBox" onkeyup="searchSubmissions()" placeholder="Search batch..." class="w-full pl-10 pr-4 py-2 bg-white/60 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition">
                    </div>
                </div>

                <?php if (empty($submissions)): ?>
                    <div class="bg-white/50 border border-dashed border-slate-300 rounded-xl p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                            <i class="fa-solid fa-folder-open text-2xl"></i>
                        </div>
                        <h3 class="text-slate-800 font-medium">No submissions yet</h3>
                        <p class="text-slate-500 text-sm mt-1">Apply for an exam above to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4" id="submissionList">
                        <?php foreach ($submissions as $s): ?>
                            <?php 
                               $st = strtolower(trim($s['payment_status'] ?? 'pending'));
                               $can_delete = ($st === 'pending' || $st === 'receipt_uploaded');
                               
                               // Status Badge Logic
                               $statusBadge = '';
                               if($st === 'paid') 
                                   $statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fa-solid fa-check mr-1"></i> Paid</span>';
                               elseif($st === 'receipt_uploaded') 
                                   $statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Processing</span>';
                               elseif($st === 'receipt_rejected') 
                                   $statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200"><i class="fa-solid fa-circle-xmark mr-1"></i> Rejected</span>';
                               else 
                                   $statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200"><i class="fa-regular fa-clock mr-1"></i> Pending</span>';
                            ?>

                            <div class="submission-card relative bg-white rounded-xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition-all">
                                <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
                                    
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h4 class="text-lg font-bold text-slate-800 batch-name"><?= htmlspecialchars($s['batch_name']) ?></h4>
                                            <?= $statusBadge ?>
                                        </div>
                                        <p class="text-sm text-slate-500 mb-2">
                                            <span class="font-medium text-indigo-600"><?= htmlspecialchars($s['title']) ?></span>
                                            <span class="mx-1">•</span>
                                            Exam Date: <?= date('d M Y', strtotime($s['exam_date'])) ?>
                                        </p>
                                        <div class="flex items-center gap-4 text-xs font-medium text-slate-600 bg-slate-50 px-3 py-2 rounded-lg inline-flex">
                                            <span><i class="fa-solid fa-users text-slate-400 mr-1"></i> <?= $s['total_students'] ?> Students</span>
                                            <span class="w-px h-3 bg-slate-300"></span>
                                            <span><i class="fa-solid fa-indian-rupee-sign text-slate-400 mr-1"></i> <?= number_format($s['total_amount'],2) ?></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        
                                        <?php if (!empty($s['upload_filename']) && file_exists(__DIR__ . '/' . $s['upload_filename'])): ?>
                                            <a href="<?= htmlspecialchars($s['upload_filename']) ?>" download class="px-3 py-1.5 text-xs font-medium bg-white border border-slate-200 text-slate-600 rounded-md hover:border-green-500 hover:text-green-600 transition">
                                                <i class="fa-solid fa-file-excel mr-1"></i> List
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($s['payment_receipt']) && file_exists(__DIR__ . '/' . $s['payment_receipt'])): ?>
                                            <a href="<?= htmlspecialchars($s['payment_receipt']) ?>" target="_blank" class="px-3 py-1.5 text-xs font-medium bg-white border border-slate-200 text-slate-600 rounded-md hover:border-indigo-500 hover:text-indigo-600 transition">
                                                <i class="fa-solid fa-eye mr-1"></i> Receipt
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($s['payment_status'] !== 'paid'): ?>
                                            <a href="upload_receipt.php?submission_id=<?= intval($s['id']) ?>" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm shadow-indigo-200 transition">
                                                <i class="fa-solid fa-upload mr-1"></i> <?= empty($s['payment_receipt']) ? 'Upload' : 'Re-upload' ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($can_delete): ?>
                                            <button class="delete-btn px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 border border-red-100 rounded-md hover:bg-red-100 transition" 
                                                    data-id="<?= intval($s['id']) ?>" 
                                                    data-batch="<?= htmlspecialchars($s['batch_name'], ENT_QUOTES) ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>

        <div class="lg:col-span-4 space-y-6">
            
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-lg border border-white/50 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-r from-indigo-500 to-purple-500 opacity-90"></div>
                <div class="relative z-10 flex flex-col items-center mt-8">
                    <div class="w-20 h-20 bg-white p-1 rounded-full shadow-md">
                        <div class="w-full h-full bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl font-bold">
                            <?= strtoupper(substr($tpData['username'], 0, 1)) ?>
                        </div>
                    </div>
                    <h3 class="mt-3 text-lg font-bold text-slate-800 text-center"><?= htmlspecialchars($tpData['centre_name'] ?? 'Training Partner') ?></h3>
                    <div class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full mt-1">
                        <?= !empty($tpData['accreditation_no']) ? $tpData['accreditation_no'] : 'No Accr. ID' ?>
                    </div>

                    <div class="w-full mt-6 space-y-3">
                        <div class="flex items-center text-sm text-slate-600">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 mr-3"><i class="fa-solid fa-user-tie"></i></div>
                            <div class="truncate"><?= htmlspecialchars($tpData['incharge_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="flex items-center text-sm text-slate-600">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 mr-3"><i class="fa-solid fa-phone"></i></div>
                            <div class="truncate"><?= htmlspecialchars($tpData['contact_number'] ?? 'N/A') ?></div>
                        </div>
                        <div class="flex items-center text-sm text-slate-600">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 mr-3"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="truncate text-xs"><?= htmlspecialchars($tpData['location'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden group">
                <div class="absolute right-[-20px] top-[-20px] text-white opacity-10 text-9xl group-hover:scale-110 transition-transform duration-500">
                    <i class="fa-brands fa-microsoft"></i>
                </div>
                <h3 class="font-bold text-lg relative z-10">Student Data Format</h3>
                <p class="text-emerald-100 text-sm mt-1 mb-4 relative z-10 text-opacity-90">Download the mandatory Excel format for student uploads.</p>
                <a href="assets/Sample_Format_for_uploading_student_data.xlsx" download class="inline-flex items-center justify-center w-full py-2.5 bg-white text-emerald-600 font-bold rounded-lg hover:bg-emerald-50 transition shadow-sm relative z-10">
                    <i class="fa-solid fa-download mr-2"></i> Download Excel
                </a>
            </div>

            <div class="bg-white/60 rounded-xl border border-slate-200 p-5 text-center">
                <div class="text-slate-400 mb-2"><i class="fa-solid fa-headset text-2xl"></i></div>
                <h4 class="font-bold text-slate-700 text-sm">Need Help?</h4>
                <p class="text-xs text-slate-500 mt-1">Contact Admin Support</p>
                <div class="mt-2 text-sm font-semibold text-indigo-600">admin@nielit.gov.in</div>
            </div>

        </div>
    </div>
</div>

<form id="deleteForm" method="post" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="submission_id" id="delete_submission_id" value="">
  <input type="hidden" name="delete_submission" value="1">
</form>

<div id="confirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
  <div id="modalOverlay" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
  <div id="modalBox" class="relative bg-white rounded-2xl max-w-sm w-full mx-4 transform scale-90 opacity-0 transition-all duration-300 shadow-2xl p-6 text-center">
      <div class="w-14 h-14 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
        <i class="fa-solid fa-trash-can"></i>
      </div>
      <h3 class="text-lg font-bold text-slate-800">Delete Submission?</h3>
      <p id="modalBody" class="mt-2 text-sm text-slate-500">This action cannot be undone.</p>
      <div class="mt-6 flex gap-3">
        <button id="modalCancel" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium transition">Cancel</button>
        <button id="modalConfirm" class="flex-1 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30 font-medium transition">Delete</button>
      </div>
  </div>
</div>

<div id="toast" class="fixed top-24 right-5 z-[60] pointer-events-none opacity-0 transition-opacity duration-300">
  <div id="toastBox" class="flex items-center gap-3 bg-white border-l-4 border-green-500 shadow-xl rounded-lg p-4 transform translate-x-10 transition-transform duration-300">
    <div class="text-green-500 text-xl"><i class="fa-solid fa-circle-check"></i></div>
    <div>
        <h4 id="toastTitle" class="font-bold text-slate-800 text-sm">Success</h4>
        <p id="toastMsg" class="text-xs text-slate-500"></p>
    </div>
  </div>
</div>

<style>
/* Animations */
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }

#confirmModal.show #modalOverlay { opacity: 1; }
#confirmModal.show #modalBox { opacity: 1; transform: scale(1); }
#toast.show { opacity: 1; }
#toast.show #toastBox { transform: translate(0); }
</style>

<script>
// Search Functionality
function searchSubmissions() {
    let input = document.getElementById('searchBox').value.toLowerCase();
    let cards = document.getElementsByClassName('submission-card');
    let container = document.getElementById('submissionList');

    let hasVisible = false;

    for (let i = 0; i < cards.length; i++) {
        let name = cards[i].querySelector('.batch-name').innerText.toLowerCase();
        if (name.includes(input)) {
            cards[i].style.display = "";
            hasVisible = true;
        } else {
            cards[i].style.display = "none";
        }
    }
}

// Modal Logic
(function(){
  const modal = document.getElementById('confirmModal');
  const modalOverlay = document.getElementById('modalOverlay');
  const modalBox = document.getElementById('modalBox');
  const deleteIdInput = document.getElementById('delete_submission_id');
  const deleteForm = document.getElementById('deleteForm');

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      const batch = this.dataset.batch;
      document.getElementById('modalBody').innerHTML = `Are you sure you want to remove <b>${batch}</b>?`;
      modal.dataset.deleteId = id;
      modal.classList.remove('hidden');
      setTimeout(() => modal.classList.add('show'), 10);
    });
  });

  const hideModal = () => {
      modal.classList.remove('show');
      setTimeout(() => modal.classList.add('hidden'), 300);
  };

  document.getElementById('modalCancel').onclick = hideModal;
  document.getElementById('modalOverlay').onclick = hideModal;

  document.getElementById('modalConfirm').onclick = function() {
      deleteIdInput.value = modal.dataset.deleteId;
      deleteForm.submit();
  };

  // Toast Logic
  const serverMsg = <?= json_encode($msg ?: '') ?>;
  if(serverMsg) {
      const t = document.getElementById('toast');
      document.getElementById('toastMsg').innerText = serverMsg;
      t.classList.remove('pointer-events-none'); // enable clicks if needed
      t.classList.add('show');
      setTimeout(() => { t.classList.remove('show'); }, 4000);
  }
})();
</script>

<?php include 'includes/footer.php'; ?>