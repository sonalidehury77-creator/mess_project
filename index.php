<?php
/**
 * 🌐 Hostel Mess Management System - Main Entry Point
 * Features:
 * - Session Hijacking Prevention (Secure configuration)
 * - Security Headers (X-Content-Type, X-Frame-Options)
 * - Smart Session Routing based on user role
 */

// 1. STANDARD SECURITY HEADERS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// 2. SECURE SESSION INITIALIZATION
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // Automatically activates if SSL is live
        'use_strict_mode' => true,
    ]);
}

// 3. SMART DASHBOARD ROUTING MATCH
if (isset($_SESSION['admin'])) {
    header("Location: admin/dashboard.php");
    exit();
} elseif (isset($_SESSION['student_roll'])) {
    header("Location: student/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hostel Mess Campus Dining Management Portal">
    
    <title>Hostel Mess Portal | Welcome</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: radial-gradient(circle at top right, #F8FAFC, #EEF2F6); 
        }
        .smooth-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .smooth-card:hover {
            transform: translateY(-6px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased selection:bg-indigo-100">

    <header class="w-full max-w-6xl mx-auto px-6 py-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="bg-indigo-600 text-white w-10 h-10 flex items-center justify-center rounded-xl shadow-lg shadow-indigo-600/30">
                <i class="fa-solid fa-utensils text-lg"></i>
            </div>
            <div>
                <h1 class="text-sm font-extrabold tracking-tight text-slate-900 leading-none">HOSTEL MESS</h1>
                <span class="text-[9px] text-indigo-600 font-bold uppercase tracking-[0.15em]">Digital Hub</span>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            SYSTEM ONLINE V2.0
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-6 py-8">
        <div class="w-full max-w-4xl text-center">
            
            <div class="mb-12">
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-4">
                    Hostel Mess Portal
                </h2>
                <p class="text-slate-600 max-w-md mx-auto font-medium text-base">
                    Welcome! Please select your portal option below to log in and manage your account.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 w-full max-w-2xl mx-auto">
                
                <article class="smooth-card bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 hover:shadow-xl hover:border-emerald-500 group">
                    <div class="bg-emerald-50 text-emerald-600 w-14 h-14 flex items-center justify-center rounded-2xl mb-6 text-xl group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2 text-left">Student Login</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed text-left">
                        View menu schedules, submit leaves, and track daily meal history records.
                    </p>
                    <a href="auth/login.php" class="block w-full bg-slate-900 text-white font-semibold py-3.5 rounded-xl hover:bg-emerald-600 transition-colors shadow-sm">
                        Open Student Portal
                    </a>
                </article>

                <article class="smooth-card bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md shadow-slate-100 hover:shadow-xl hover:border-indigo-600 group">
                    <div class="bg-indigo-50 text-indigo-600 w-14 h-14 flex items-center justify-center rounded-2xl mb-6 text-xl group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300 shadow-sm">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2 text-left">Admin Panel</h3>
                    <p class="text-slate-500 text-sm mb-8 leading-relaxed text-left">
                        Manage user accounts, check off days, update menu items, and view reports.
                    </p>
                    <a href="admin/login.php" class="block w-full bg-slate-900 text-white font-semibold py-3.5 rounded-xl hover:bg-indigo-600 transition-colors shadow-sm">
                        Open Admin Console
                    </a>
                </article>
                
            </div>
        </div>
    </main>

    <footer class="w-full text-center py-6 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-t border-slate-200/60 bg-white/50 backdrop-blur-sm">
        &copy; 2026 Hostel Mess Management System
    </footer>

</body>
</html>