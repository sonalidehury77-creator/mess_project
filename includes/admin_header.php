<?php
/**
 * 🛡️ Enterprise Administrative Global Layout Header Shield
 * Controls secure session contexts, enforces authorization checks, and configures modern interface resources.
 */

// Initialize runtime secure session state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Map Global Database Connections & Utility Configurations
require_once __DIR__ . '/../config/db_connect.php';

// 2. Identity & Access Guard (RBAC - Role Based Access Control)
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// 3. Prevent Session Hijacking (Re-verify browser profile fingerprint metadata)
if (!isset($_SESSION['admin_user_agent'])) {
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} elseif ($_SESSION['admin_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: login.php?err=session_compromised");
    exit();
}

// 4. HTTP Defensive Security Hardening Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Type: text/html; charset=UTF-8");

$admin_current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mess Management | Admin Core Console</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/tables.css">
    <link rel="stylesheet" href="../css/responsive.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        enterprise: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; -webkit-font-smoothing: antialiased; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 8px; transition: background 0.2s ease; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .dropdown-animate {
            opacity: 0;
            transform: scale(0.95) translateY(-8px);
            pointer-events: none;
            transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dropdown-animate.show {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }
    </style>
</head>
<body class="h-full overflow-x-hidden text-slate-900 selection:bg-blue-500 selection:text-white">
    
    <div id="system-loading-bar" class="fixed top-0 left-0 right-0 h-1 bg-blue-600 z-50 transition-all duration-500 w-0 opacity-100"></div>

    <!-- Admin Master Layout Container Grid -->
    <div class="flex min-h-screen relative">

        <!-- Left Side Dashboard Navigation Sidebar Template Overlay -->
        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 text-slate-300 transform -translate-x-full lg:translate-x-0 lg:static lg:flex lg:flex-col transition-transform duration-300 ease-in-out">
            <!-- Sidebar Header Branding -->
            <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-800 bg-slate-950/40">
                <div class="bg-blue-600 text-white w-9 h-9 flex items-center justify-center rounded-xl shadow-lg shadow-blue-600/20">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                </div>
                <div>
                    <h1 class="text-xs font-black text-white uppercase tracking-wider leading-none">CORE CONSOLE</h1>
                    <span class="text-[9px] text-blue-400 font-bold uppercase tracking-widest mt-0.5 block">Admin Engine</span>
                </div>
            </div>

            <!-- Main Sidebar Scrolling Interactive Directory Root -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'dashboard.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-chart-line text-sm w-5"></i> Dashboard Overview
                </a>
                <a href="students.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo in_array($admin_current_page, ['students.php', 'add_student.php']) ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-user-graduate text-sm w-5"></i> Student Boarders
                </a>
                <a href="menu_manager.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'menu_manager.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-utensils text-sm w-5"></i> Mess Menu Planner
                </a>
                <a href="attendance_qr.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'attendance_qr.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-qrcode text-sm w-5"></i> QR Token Scanner
                </a>
                <a href="leaves.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'leaves.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-calendar-check text-sm w-5"></i> Leave Registers
                </a>
                <a href="ledger.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'ledger.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-file-invoice-dollar text-sm w-5"></i> Financial Ledgers
                </a>
                <a href="feedback.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition-colors <?php echo $admin_current_page == 'feedback.php' ? 'bg-blue-600 text-white' : 'hover:bg-slate-800 hover:text-white'; ?>">
                    <i class="fa-solid fa-comments text-sm w-5"></i> Review & Analytics
                </a>
            </nav>

            <!-- Sidebar Footnote Actions Module Link -->
            <div class="p-3 border-t border-slate-800 bg-slate-950/20">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-red-400 hover:bg-red-950/30 hover:text-red-300 transition-colors">
                    <i class="fa-solid fa-power-off text-sm w-5"></i> End Admin Session
                </a>
            </div>
        </aside>

        <!-- Dynamic Sidebar Responsive Backdrop Element Context Block -->
        <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-30 hidden lg:hidden"></div>

        <!-- Right Side Primary Screen Layout Architecture Anchor Framework Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 bg-[#F8FAFC]">

            <!-- Sticky Top Premium Administrative Action Dashboard Header -->
            <header class="bg-white border-b border-slate-200/80 sticky top-0 z-20 shadow-sm backdrop-blur-md bg-white/90 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                
                <!-- Front-End Sidebar Hamburger Control and Global Context Tracker Search Assembly -->
                <div class="flex items-center gap-4 flex-1 max-w-md">
                    <button id="sidebar-toggle-trigger" class="lg:hidden p-2 text-slate-500 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl transition-all focus:outline-none">
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>

                    <!-- Real-Time Universal Administrative System Context Indexer Search Container -->
                    <form action="students.php" method="GET" class="relative w-full hidden sm:block no-loader">
                        <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </div>
                        <input type="text" name="search" placeholder="Search student records, logs or accounts..." class="w-full bg-slate-50 text-slate-800 placeholder-slate-400 text-xs font-medium pl-10 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all">
                    </form>
                </div>

                <!-- Administrative Notification center, Identity Controls and Interactive Dropdown elements -->
                <div class="flex items-center gap-3">
                    
                    <!-- Search Trigger for Mobile Framework Layout Systems -->
                    <button class="sm:hidden p-2 text-slate-500 hover:text-slate-900 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none" onclick="window.location.href='students.php'">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </button>

                    <!-- Secure Core Real-time Notifications Bell System Panel Matrix -->
                    <div class="relative">
                        <button id="admin-notif-trigger" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all focus:outline-none relative">
                            <i class="fa-solid fa-bell text-xs"></i>
                            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-blue-600 rounded-full border border-white animate-pulse"></span>
                        </button>
                        
                        <!-- Notifications Absolute Dropdown Pane Container Wrapper Panel -->
                        <div id="admin-notif-dropdown" class="dropdown-animate absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-slate-200/80 shadow-xl py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 flex justify-between items-center">
                                <span class="font-bold text-xs text-slate-800">System Activity Logs</span>
                                <span class="text-[10px] text-blue-600 font-bold bg-blue-5 dark:bg-blue-50 px-2 py-0.5 rounded-full">Realtime</span>
                            </div>
                            <div class="p-1.5 max-h-64 overflow-y-auto space-y-0.5">
                                <div class="p-2.5 rounded-xl hover:bg-slate-50 transition-colors text-left cursor-pointer">
                                    <p class="text-xs font-semibold text-slate-800">New Leave request uploaded</p>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">5 mins ago • Register Panel</span>
                                </div>
                                <div class="p-2.5 rounded-xl hover:bg-slate-50 transition-colors text-left cursor-pointer">
                                    <p class="text-xs font-semibold text-slate-800">System backup context auto-compiled</p>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">1 hour ago • Database Kernel</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin User Identity Profile Management Modular Hub Interface Layer -->
                    <div class="relative">
                        <button id="admin-profile-trigger" class="flex items-center gap-2 p-1.5 pr-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-all focus:outline-none">
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white text-xs font-bold flex items-center justify-center shadow-sm">
                                AD
                            </div>
                            <span class="text-xs font-bold text-slate-700 hidden sm:inline-block">System Admin</span>
                            <i id="admin-profile-chevron" class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-0.5 transition-transform duration-200"></i>
                        </button>

                        <!-- Admin Actions Navigation Dropdown Workspace List Component -->
                        <div id="admin-profile-dropdown" class="dropdown-animate absolute right-0 mt-2 w-48 bg-white rounded-2xl border border-slate-200/80 shadow-xl py-1.5 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 text-left">
                                <p class="text-xs font-bold text-slate-800">Root Account</p>
                                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">admin@hostelmess.edu</p>
                            </div>
                            <div class="p-1.5 space-y-0.5">
                                <a href="dashboard.php" class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-600 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                    <i class="fa-solid fa-sliders text-slate-400 w-4 text-center"></i> Console Controls
                                </a>
                                <a href="logout.php" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                    <i class="fa-solid fa-power-off text-red-500 w-4 text-center"></i> Sign Out Securely
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Standard Client Script Component Execution Handler Initialization Anchor Layer -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    // Micro Progress Loader Tracking Timeline Mock Initialization Context
                    const loadBar = document.getElementById('system-loading-bar');
                    if (loadBar) {
                        loadBar.style.width = '25%';
                        setTimeout(() => { loadBar.style.width = '65%'; }, 100);
                        setTimeout(() => { loadBar.style.width = '100%'; }, 250);
                        setTimeout(() => { loadBar.style.opacity = '0'; }, 500);
                    }

                    // Administrative Portal Layer Component DOM Selection Setup Definition
                    const sidebarTrigger = document.getElementById('sidebar-toggle-trigger');
                    const sidebar = document.getElementById('admin-sidebar');
                    const backdrop = document.getElementById('sidebar-backdrop');
                    
                    const notifTrigger = document.getElementById('admin-notif-trigger');
                    const notifDropdown = document.getElementById('admin-notif-dropdown');
                    
                    const profileTrigger = document.getElementById('admin-profile-trigger');
                    const profileDropdown = document.getElementById('admin-profile-dropdown');
                    const profileChevron = document.getElementById('admin-profile-chevron');

                    function hideActiveDropdownElements() {
                        if (notifDropdown) notifDropdown.classList.remove('show');
                        if (profileDropdown) {
                            profileDropdown.classList.remove('show');
                            if (profileChevron) profileChevron.style.transform = 'rotate(0deg)';
                        }
                    }

                    // Responsive Dynamic Sidebar Menu Open/Close Toggling Logic Context
                    if (sidebarTrigger && sidebar && backdrop) {
                        const toggleSidebar = () => {
                            sidebar.classList.toggle('-translate-x-full');
                            backdrop.classList.toggle('hidden');
                        };
                        sidebarTrigger.addEventListener('click', (e) => { e.stopPropagation(); toggleSidebar(); });
                        backdrop.addEventListener('click', toggleSidebar);
                    }

                    // Administrative Notifications Engine Trigger Layer Interaction
                    if (notifTrigger && notifDropdown) {
                        notifTrigger.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isOpen = notifDropdown.classList.contains('show');
                            hideActiveDropdownElements();
                            if (!isOpen) notifDropdown.classList.add('show');
                        });
                    }

                    // Administrator Personal Identity Dropdown Element Context Trigger
                    if (profileTrigger && profileDropdown) {
                        profileTrigger.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isOpen = profileDropdown.classList.contains('show');
                            hideActiveDropdownElements();
                            if (!isOpen) {
                                profileDropdown.classList.add('show');
                                if (profileChevron) profileChevron.style.transform = 'rotate(180deg)';
                            }
                        });
                    }

                    // Document Wide Direct Global Click Escape Dropdown Closer Context Interceptor Layer
                    document.addEventListener('click', () => { hideActiveDropdownElements(); });
                });
            </script>
            
            <!-- Global Main Dynamic Page Ingestion Body Entry Block Payload Anchor Node -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 transition-all duration-300">