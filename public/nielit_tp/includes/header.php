<?php
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Kolkata');
$page_title = $page_title ?? "NIELIT Bhubaneswar";

$current = basename($_SERVER["PHP_SELF"]);

// Determine Home button visibility
$showHome = in_array($current, [
    "index.php",
    "tp_register.php",
    "admin_login.php"
]);

// Only index.php should show Admin Login button
$showAdminLogin = ($current === "index.php");
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($page_title); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      background: #f8fafc; /* Slate-50 */
      font-family: 'Inter', sans-serif;
      color: #334155; /* Slate-700 */
    }

    /* Top Gradient Line */
    .brand-stripe {
      height: 4px;
      background: linear-gradient(90deg, #4f46e5, #0ea5e9);
    }

    /* Modern Glass Header */
    .glass-header {
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      position: sticky;
      top: 0;
      z-index: 50;
      transition: all 0.3s ease;
    }

    /* Button Styles */
    .btn-nav {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1.25rem;
      border-radius: 9999px;
      font-size: 0.875rem;
      font-weight: 600;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .btn-nav:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .btn-home {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }

    .btn-admin {
      background: white;
      color: #475569;
      border: 1px solid #e2e8f0;
    }
    .btn-admin:hover {
      background: #f8fafc;
      color: #0f172a;
      border-color: #cbd5e1;
    }
  </style>
</head>

<body class="flex flex-col min-h-screen">

<div class="brand-stripe"></div>

<header class="glass-header shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">

      <div class="flex items-center gap-4">
        <img src="assets/nblogo.jpg" alt="NIELIT Logo" class="h-12 w-auto object-contain rounded-md shadow-sm border border-slate-100">
        <div class="flex flex-col">
          <span class="text-xl font-bold text-slate-800 tracking-tight leading-none">NIELIT Bhubaneswar</span>
          <span class="text-sm font-medium text-slate-500 mt-1"><?= htmlspecialchars($page_title); ?></span>
        </div>
      </div>

      <div class="flex items-center gap-4 sm:gap-6">
        
        <div class="hidden md:flex items-center gap-2 text-slate-500 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
          <i class="fa-regular fa-clock text-indigo-500"></i>
          <span id="live-clock" class="text-sm font-medium tabular-nums tracking-wide">
            Loading...
          </span>
        </div>

        <div class="flex items-center gap-3">
          <?php if ($showHome): ?>
            <a href="index.php" class="btn-nav btn-home" title="Go to Home">
              <i class="fa-solid fa-house"></i>
              <span class="hidden sm:inline">Home</span>
            </a>
          <?php endif; ?>

          <?php if ($showAdminLogin): ?>
            <a href="admin_login.php" class="btn-nav btn-admin" title="Admin Access">
              <i class="fa-solid fa-lock"></i>
              <span class="hidden sm:inline">Admin</span>
            </a>
          <?php endif; ?>
        </div>
        
      </div>

    </div>
  </div>
</header>

<script>
  function updateClock() {
    const now = new Date();
    
    // Arrays for formatting
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    
    // Components
    const day = String(now.getDate()).padStart(2, '0');
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    // Assemble string
    const dateString = `${day} ${month} ${year}, ${hours}:${minutes}:${seconds}`;
    
    // Update DOM
    const clockEl = document.getElementById('live-clock');
    if (clockEl) clockEl.textContent = dateString;
  }

  // Run immediately then every second
  updateClock();
  setInterval(updateClock, 1000);
</script>

<div class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">