<?php
// admin_login.php - High-Security Superadmin Portal

// 1. Start Session & Init
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$username = '';

// --- SECURITY: RATE LIMITING ---
// Lockout logic: 5 failed attempts = 5 minute lockout
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['lockout_time'])) $_SESSION['lockout_time'] = 0;

if ($_SESSION['login_attempts'] >= 5) {
    if (time() - $_SESSION['lockout_time'] < 300) { // 300 seconds = 5 mins
        $remaining = 300 - (time() - $_SESSION['lockout_time']);
        $error = "⛔ Security Lockout. Too many failed attempts. Try again in " . ceil($remaining/60) . " minute(s).";
    } else {
        // Reset after timeout
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
    }
}

// --- SECURITY: GENERATE CAPTCHA ---
if (!isset($_SESSION['adm_captcha_a']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['adm_captcha_a'] = rand(2, 9);
    $_SESSION['adm_captcha_b'] = rand(2, 9);
}
$captcha_question = $_SESSION['adm_captcha_a'] . " + " . $_SESSION['adm_captcha_b'];

// 2. HANDLE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captcha_input = intval($_POST['captcha_answer'] ?? 0);
    $captcha_correct = $_SESSION['adm_captcha_a'] + $_SESSION['adm_captcha_b'];

    if ($captcha_input !== $captcha_correct) {
        $error = "⚠️ Captcha verification failed.";
    } elseif (empty($username) || empty($password)) {
        $error = "⚠️ Credentials required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1");
            $stmt->execute(['u' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // SUCCESS
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                $_SESSION['login_attempts'] = 0; // Reset counter
                
                // Clear captcha
                unset($_SESSION['adm_captcha_a'], $_SESSION['adm_captcha_b']);

                header("Location: admin_dashboard.php");
                exit;
            } else {
                // FAILURE
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lockout_time'] = time();
                }
                $error = "❌ Access Denied: Invalid credentials.";
            }
        } catch (Throwable $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = "⚠️ System Error. Contact IT Support.";
        }
    }
}

// Header inclusion (Ensure your header.php handles HTML opening tags correctly)
$page_title = "Superadmin Secure Access";
include __DIR__ . "/includes/header.php";
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    body {
        background-color: #020617; /* Slate 950 */
        font-family: 'Inter', sans-serif;
        color: #e2e8f0;
    }
    
    /* Animated Grid Background */
    .cyber-grid {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
        background-image: 
            linear-gradient(rgba(99, 102, 241, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(99, 102, 241, 0.05) 1px, transparent 1px);
        background-size: 40px 40px;
        mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
    }

    /* Floating Orbs */
    .orb {
        position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.4;
        animation: float 10s infinite ease-in-out;
    }
    .orb-1 { top: 20%; left: 20%; width: 300px; height: 300px; background: #4f46e5; }
    .orb-2 { bottom: 20%; right: 20%; width: 400px; height: 400px; background: #06b6d4; animation-delay: -5s; }

    @keyframes float {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(30px, -30px); }
    }

    /* Glass Card */
    .glass-card {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    /* Neon Focus */
    .neon-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
    }
</style>

<div class="cyber-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="min-h-[85vh] flex items-center justify-center px-4 relative z-10">

    <div class="glass-card rounded-2xl w-full max-w-md p-8 md:p-10 relative overflow-hidden group">
        
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 via-indigo-500 to-purple-500"></div>

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800/50 border border-slate-700 mb-4 shadow-lg shadow-indigo-500/20">
                <i class="fa-solid fa-user-astronaut text-3xl text-indigo-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide">ADMIN<span class="text-indigo-500">CONSOLE</span></h2>
            <p class="text-slate-400 text-xs uppercase tracking-widest mt-1">Superuser Access Terminal</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-200 text-sm p-3 rounded-lg mb-6 flex items-center gap-3 animate-pulse">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" class="space-y-6">
            
            <div class="group">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 ml-1 group-focus-within:text-indigo-400 transition-colors">Identifier</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-regular fa-id-badge text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                    </div>
                    <input type="text" name="username" class="neon-input w-full pl-11 pr-4 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:bg-slate-900/80 transition-all" placeholder="Enter username" value="<?= htmlspecialchars($username) ?>" required>
                </div>
            </div>

            <div class="group">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 ml-1 group-focus-within:text-cyan-400 transition-colors">Passcode</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-fingerprint text-slate-500 group-focus-within:text-cyan-500 transition-colors"></i>
                    </div>
                    <input type="password" name="password" id="admin_pw" class="neon-input w-full pl-11 pr-12 py-3 bg-slate-900/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:bg-slate-900/80 transition-all" placeholder="••••••••••••" required>
                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors cursor-pointer">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Security Check</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-lg font-mono font-bold text-indigo-400"><?= $captcha_question ?></span>
                        <span class="text-slate-400">=</span>
                    </div>
                </div>
                <input type="number" name="captcha_answer" class="w-20 bg-slate-900 border border-slate-600 text-center text-white font-bold py-2 rounded-lg focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="?" required>
            </div>

            <button type="submit" class="w-full relative group overflow-hidden rounded-xl p-[1px]">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 rounded-xl opacity-70 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative bg-slate-900 text-white font-bold py-3.5 rounded-xl hover:bg-transparent hover:shadow-2xl transition-all duration-300 flex items-center justify-center gap-2">
                    <span>Authenticate</span>
                    <i class="fa-solid fa-arrow-right-long group-hover:translate-x-1 transition-transform"></i>
                </div>
            </button>

        </form>

        <div class="mt-8 text-center">
            <p class="text-[10px] text-slate-600 font-mono">
                SECURE CONNECTION ESTABLISHED<br>
                IP: <?= $_SERVER['REMOTE_ADDR'] ?>
            </p>
        </div>

    </div>
</div>

<script>
function togglePassword() {
    const p = document.getElementById("admin_pw");
    p.type = (p.type === "password") ? "text" : "password";
}
</script>

<?php include __DIR__ . "/includes/footer.php"; ?>