<?php
// tp_register.php - Futuristic Registration

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$page_title = 'Partner Registration';
include 'includes/header.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $centre = trim($_POST['centre_name'] ?? '');
    $accreditation_no = trim($_POST['accreditation_no'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $incharge = trim($_POST['incharge_name'] ?? '');
    $contact = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$centre || !$username || !$password) {
        $err = '⚠️ Missing required fields (Centre Name, Username, Password).';
    } elseif (strlen($password) < 6) {
        $err = '⚠️ Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $err = '❌ Passwords do not match.';
    } else {
        // Check for existing user
        $stmt = $pdo->prepare('SELECT id FROM tps WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute(['u' => $username, 'e' => $email]);

        if ($stmt->fetch()) {
            $err = '❌ Username or Email is already registered.';
        } else {
            // Register
            $pw = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO tps (centre_name, accreditation_no, location, pincode, incharge_name, contact_number, email, username, password_hash, created_at) VALUES (:c,:acc,:l,:p,:i,:cn,:em,:u,:pw, NOW())');

            if($ins->execute([
                'c'   => $centre, 'acc' => $accreditation_no, 'l' => $location, 'p' => $pincode,
                'i'   => $incharge, 'cn' => $contact, 'em' => $email, 'u' => $username, 'pw' => $pw
            ])) {
                $success = '✅ Registration successful! Your account is pending admin approval.';
            } else {
                $err = '❌ Database error. Please try again.';
            }
        }
    }
}
?>

<div class="fixed inset-0 -z-10 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900">
    <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20"></div>
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-purple-600/20 rounded-full blur-[100px] animate-pulse"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-indigo-600/20 rounded-full blur-[100px] animate-pulse" style="animation-delay: 2s"></div>
</div>

<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8 flex items-center justify-center relative z-10">
    
    <div class="max-w-4xl w-full bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden relative">
        
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-400 via-indigo-500 to-purple-500"></div>

        <div class="p-8 md:p-12">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <a href="index.php" class="inline-flex items-center text-sm text-indigo-300 hover:text-white mb-2 transition-colors">
                        <i class="fa-solid fa-arrow-left-long mr-2"></i> Back to Login
                    </a>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Partner Registration</h2>
                    <p class="text-indigo-200/80 text-sm mt-1">Join the network of authorized Training Centers.</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-white border border-white/20 shadow-inner">
                        <i class="fa-solid fa-user-plus text-xl"></i>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-emerald-500/20 border border-emerald-500/50 rounded-xl p-6 text-center animate-fade-in-up mb-8">
                    <div class="w-16 h-16 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg shadow-emerald-500/30">
                        <i class="fa-solid fa-check text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Registration Submitted!</h3>
                    <p class="text-emerald-100 mt-2"><?= htmlspecialchars($success) ?></p>
                    <div class="mt-6">
                        <a href="index.php" class="inline-block bg-white text-emerald-600 font-bold px-6 py-2.5 rounded-lg hover:bg-emerald-50 transition shadow-lg">
                            Go to Login
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <?php if ($err): ?>
                    <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-4 py-3 rounded-xl mb-8 flex items-center gap-3 animate-shake">
                        <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                        <?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-8" autocomplete="off">
                    
                    <div>
                        <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2 mb-6 flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-indigo-500 flex items-center justify-center text-xs">1</span>
                            Centre Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="col-span-1 md:col-span-2 group">
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Centre Name *</label>
                                <div class="relative">
                                    <i class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="centre_name" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none transition" placeholder="e.g. NIELIT Authorized Centre - Bhubaneswar" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Accreditation No.</label>
                                <div class="relative">
                                    <i class="fa-solid fa-certificate absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="accreditation_no" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-indigo-400 outline-none transition" placeholder="e.g. ACC-2025-001">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">In-Charge Name</label>
                                <div class="relative">
                                    <i class="fa-solid fa-user-tie absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="incharge_name" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-indigo-400 outline-none transition" placeholder="Full Name">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2 mb-6 flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-purple-500 flex items-center justify-center text-xs">2</span>
                            Contact & Location
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Email Address</label>
                                <div class="relative">
                                    <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="email" name="email" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-purple-400 outline-none transition" placeholder="contact@example.com">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Contact Number</label>
                                <div class="relative">
                                    <i class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="tel" name="contact_number" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-purple-400 outline-none transition" placeholder="+91 98765 43210">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">City / District</label>
                                <div class="relative">
                                    <i class="fa-solid fa-map-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="location" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-purple-400 outline-none transition" placeholder="e.g. Khordha">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Pincode</label>
                                <div class="relative">
                                    <i class="fa-solid fa-thumbtack absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="pincode" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-purple-400 outline-none transition" placeholder="e.g. 751001">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2 mb-6 flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-pink-500 flex items-center justify-center text-xs">3</span>
                            Account Security
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Create Username *</label>
                                <div class="relative">
                                    <i class="fa-solid fa-user-lock absolute left-4 top-1/2 -translate-y-1/2 text-indigo-400"></i>
                                    <input type="text" name="username" class="w-full pl-10 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-pink-400 outline-none transition" placeholder="Choose a unique username" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Password *</label>
                                <div class="relative">
                                    <input id="pw" type="password" name="password" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-pink-400 outline-none transition" placeholder="Min. 6 characters" required onkeyup="checkStrength()">
                                    <button type="button" onclick="togglePw('pw')" class="absolute right-4 top-1/2 -translate-y-1/2 text-indigo-400 hover:text-white transition"><i class="fa-regular fa-eye"></i></button>
                                </div>
                                <div class="flex gap-1 mt-2 h-1">
                                    <div id="bar1" class="flex-1 bg-white/10 rounded-full transition-colors duration-300"></div>
                                    <div id="bar2" class="flex-1 bg-white/10 rounded-full transition-colors duration-300"></div>
                                    <div id="bar3" class="flex-1 bg-white/10 rounded-full transition-colors duration-300"></div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-indigo-200 uppercase mb-1">Confirm Password *</label>
                                <div class="relative">
                                    <input id="cpw" type="password" name="confirm_password" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-white/30 focus:bg-white/10 focus:border-pink-400 outline-none transition" placeholder="Retype password" required>
                                    <button type="button" onclick="togglePw('cpw')" class="absolute right-4 top-1/2 -translate-y-1/2 text-indigo-400 hover:text-white transition"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-purple-500/30 transform hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2 group">
                            Complete Registration <i class="fa-solid fa-rocket group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        <p class="text-center text-indigo-200/60 text-xs mt-4">
                            By registering, you agree to NIELIT's terms and conditions.
                        </p>
                    </div>

                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}
.animate-shake { animation: shake 0.4s ease-in-out; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
</style>

<script>
function togglePw(id) {
    const f = document.getElementById(id);
    f.type = (f.type === 'password') ? 'text' : 'password';
}

function checkStrength() {
    const val = document.getElementById('pw').value;
    const b1 = document.getElementById('bar1');
    const b2 = document.getElementById('bar2');
    const b3 = document.getElementById('bar3');
    
    // Reset
    b1.className = 'flex-1 bg-white/10 rounded-full transition-colors duration-300';
    b2.className = 'flex-1 bg-white/10 rounded-full transition-colors duration-300';
    b3.className = 'flex-1 bg-white/10 rounded-full transition-colors duration-300';

    if(val.length > 0) {
        if(val.length < 6) {
            b1.className = 'flex-1 bg-red-500 rounded-full transition-colors duration-300';
        } else if(val.length < 10) {
            b1.className = 'flex-1 bg-yellow-400 rounded-full transition-colors duration-300';
            b2.className = 'flex-1 bg-yellow-400 rounded-full transition-colors duration-300';
        } else {
            b1.className = 'flex-1 bg-green-500 rounded-full transition-colors duration-300';
            b2.className = 'flex-1 bg-green-500 rounded-full transition-colors duration-300';
            b3.className = 'flex-1 bg-green-500 rounded-full transition-colors duration-300';
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>