<?php
// index.php - Futuristic Login Page

// 1. Start Session & Include DB (MUST BE FIRST)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$login_error = '';
$captcha_question = '';

// 2. Handle Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tp_login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha_input = trim($_POST['captcha_answer'] ?? '');

    $correct_answer = ($_SESSION['captcha_a'] ?? 0) + ($_SESSION['captcha_b'] ?? 0);

    if ($captcha_input != $correct_answer) {
        $login_error = "⚠️ Verification failed. Incorrect Captcha.";
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, verified, username FROM tps WHERE username = :u LIMIT 1');
        $stmt->execute(['u' => $username]);
        $tp = $stmt->fetch();

        if ($tp && password_verify($password, $tp['password_hash'])) {
            if ($tp['verified'] !== 'approved') {
                $login_error = '🔒 Account pending approval from Admin.';
            } else {
                session_regenerate_id(true);
                $_SESSION['tp_id'] = $tp['id'];
                $_SESSION['username'] = $tp['username'];
                unset($_SESSION['captcha_a'], $_SESSION['captcha_b']);
                header("Location: tp_dashboard.php");
                exit; 
            }
        } else {
            $login_error = '❌ Invalid credentials provided.';
        }
    }
}

// 3. Generate New Captcha
if (!isset($_SESSION['captcha_a']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['captcha_a'] = rand(1, 9);
    $_SESSION['captcha_b'] = rand(1, 9);
}
$captcha_question = $_SESSION['captcha_a'] . " + " . $_SESSION['captcha_b'];

$page_title = 'Secure Access Portal';
include 'includes/header.php';
?>

<div class="fixed inset-0 -z-10 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900">
    <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"></div>
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500 rounded-full mix-blend-screen filter blur-[128px] opacity-20 animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-screen filter blur-[128px] opacity-20 animate-pulse" style="animation-delay: 2s"></div>
</div>

<div class="min-h-[85vh] flex items-center justify-center px-4 py-12 relative">
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl w-full z-10">

        <div class="hidden lg:flex flex-col justify-center p-12 rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-2xl relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

            <div class="relative z-10">
                <div class="w-16 h-16 bg-indigo-500/20 rounded-2xl flex items-center justify-center mb-6 text-indigo-300">
                    <i class="fa-solid fa-layer-group text-3xl"></i>
                </div>
                
                <h2 class="text-4xl font-bold text-white mb-6 leading-tight">
                    Next-Gen <br> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-purple-300">Exam Management</span>
                </h2>
                
                <p class="text-indigo-100/70 text-lg leading-relaxed mb-8">
                    Empowering Training Partners with a seamless, secure, and real-time dashboard for managing candidate mock exams and payments.
                </p>

                <div class="flex items-center gap-4 text-sm font-medium text-white/60">
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-full border border-white/10">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i> Secure
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-full border border-white/10">
                        <i class="fa-solid fa-bolt text-amber-400"></i> Fast
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-full border border-white/10">
                        <i class="fa-solid fa-cloud text-sky-400"></i> Cloud
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <div class="mb-8">
                <h3 class="text-2xl font-bold text-slate-800">Partner Login</h3>
                <p class="text-slate-500 mt-2 text-sm">Enter your credentials to access the console.</p>
            </div>

            <?php if ($login_error): ?>
                <div class="flex items-center gap-3 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-6 animate-shake">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($login_error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <input type="hidden" name="action" value="tp_login">

                <div class="group">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Username / ID</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <i class="fa-regular fa-user"></i>
                        </span>
                        <input name="username" class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none placeholder:text-slate-400" placeholder="e.g. TP_1024" required>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 ml-1">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input id="password" type="password" name="password" class="w-full pl-11 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none placeholder:text-slate-400" placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600 transition-colors">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">Security Check</span>
                            <span class="text-sm font-semibold text-slate-700 mt-1">What is <?= $captcha_question ?> ?</span>
                        </div>
                        <input type="number" name="captcha_answer" class="w-20 py-2 text-center font-bold text-indigo-600 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 outline-none" placeholder="?" required>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="tp_register.php" class="text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors">
                        Register New Partner
                    </a>
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-600/40 hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2">
                        Login <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
/* Custom Animations */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake { animation: shake 0.4s ease-in-out; }

/* Custom Scrollbar for form if needed */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none; margin: 0;
}
</style>

<script>
function togglePassword() {
  const p = document.getElementById('password');
  p.type = p.type === 'password' ? 'text' : 'password';
}
</script>

<?php include 'includes/footer.php'; ?>