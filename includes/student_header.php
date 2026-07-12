<?php
/**
 * 🛡️ Enterprise Student Portal Global Layout Header Shield
 * Controls secure boarder session verification context, ensures role access controls,
 * and builds responsive navigation items for all phase modules.
 */

// Initialize runtime secure session state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Map Global Database Connections & Configuration Constants
require_once __DIR__ . '/../config/db_connect.php';

// Helper fallback utility function to prevent fatal compilation crashes if missing globally
if (!function_exists('escape_output')) {
    function escape_output($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// 2. Identity & Access Guard (RBAC - Student Access Validation)
if (!isset($_SESSION['student_roll'])) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$student_page = basename($_SERVER['PHP_SELF']);

// 3. Extract Session Context Metadata for Dynamic Profile Presentation
$student_name = $_SESSION['student_name'] ?? 'Student Boarder';
$student_avatar = !empty($_SESSION['student_photo']) ? '../uploads/students/' . $_SESSION['student_photo'] : '../uploads/students/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Student Portal | Hostel Mess Ecosystem</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/tables.css">
    <link rel="stylesheet" href="../css/responsive.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b'
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
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        
        /* Smooth Dropdown Transitions */
        .dropdown-menu {
            opacity: 0;
            transform: scale(0.95) translateY(-10px);
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dropdown-menu.active {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }
    </style>
</head>
<body class="h-full bg-[#F8FAFC] flex flex-col text-slate-900 selection:bg-emerald-500 selection:text-white">

    <!-- Top Animated Interactive Loading Indicator Line -->
    <div id="student-progress-bar" class="fixed top-0 left-0 right-0 h-1 bg-emerald-600 z-50 transition-all duration-500 w-0"></div>

    <!-- Sticky Premium Header -->
    <header class="bg-white/90 border-b border-slate-200/80 sticky top-0 z-40 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <!-- Left Side: Professional Branding Identity Logo -->
            <a href="dashboard.php" class="flex items-center gap-3 group transition-transform active:scale-95">
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white w-10 h-10 flex items-center justify-center rounded-xl shadow-md shadow-emerald-600/20 group-hover:rotate-3 transition-transform">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider leading-none">BOARDER HUB</h1>
                    <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-widest mt-1 block">Dining Panel</span>
                </div>
            </a>

            <!-- Center View: Standard Navigation System -->
            <nav class="hidden md:flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200/40">
                <a href="dashboard.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo $student_page == 'dashboard.php' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">Home</a>
                
                <a href="meal.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo in_array($student_page, ['meal.php', 'save_meal.php']) ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">Bookings</a>
                
                <a href="generate_qr.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo $student_page == 'generate_qr.php' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">QR Token</a>
                
                <a href="leave_manager.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo $student_page == 'leave_manager.php' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">Leaves</a>
                
                <a href="submit_feedback.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo $student_page == 'submit_feedback.php' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">Feedback</a>
                
                <a href="bill.php" class="px-4 py-2 text-xs font-semibold tracking-wide rounded-lg transition-all <?php echo in_array($student_page, ['bill.php', 'view_bill.php', 'print_bill.php', 'graph.php']) ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-white/50'; ?>">Ledger</a>
            </nav>

            <!-- Right Side Actions Area -->
            <div class="flex items-center gap-2 sm:gap-4">
                
                <!-- Notification Bell Core Icon Layout Component -->
                <div class="relative">
                    <button id="notification-bell-btn" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all focus:outline-none relative">
                        <i class="fa-solid fa-bell text-sm"></i>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-emerald-500 rounded-full border border-white"></span>
                    </button>
                    <!-- Simple Interactive Notification Context Panel View Tray -->
                    <div id="notification-panel-tray" class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-slate-200/80 shadow-xl py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100 flex justify-between items-center">
                            <span class="font-bold text-xs text-slate-800">Recent Notices</span>
                            <span class="text-[10px] text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded-full">New</span>
                        </div>
                        <div class="p-2 max-h-64 overflow-y-auto space-y-1">
                            <div class="p-2.5 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer text-left">
                                <p class="text-xs font-semibold text-slate-800">Dinner Timing Changed</p>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Today • Mess Committee</span>
                            </div>
                            <div class="p-2.5 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer text-left">
                                <p class="text-xs font-semibold text-slate-800">Leave Reimbursement Processed</p>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Yesterday • Accounts</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Profile Dropdown Panel Trigger Module -->
                <div class="relative">
                    <button id="profile-dropdown-btn" class="flex items-center gap-2 p-1.5 pr-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-all focus:outline-none">
                        <img src="<?php echo $student_avatar; ?>" alt="Avatar" class="w-7 h-7 rounded-lg object-cover border border-slate-200/40 shadow-inner">
                        <span class="text-xs font-bold text-slate-700 max-w-[90px] truncate hidden sm:inline-block"><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1 transition-transform duration-200" id="profile-chevron"></i>
                    </button>
                    
                    <!-- Dropdown Interface Menu Panel -->
                    <div id="profile-dropdown-menu" class="dropdown-menu absolute right-0 mt-2 w-52 bg-white rounded-2xl border border-slate-200/80 shadow-xl py-1.5 z-50">
                        <div class="px-4 py-2.5 border-b border-slate-100 text-left">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5"><?php echo $_SESSION['student_roll'] ?? 'Roll Record'; ?></p>
                        </div>
                        <div class="p-1.5 space-y-0.5">
                            <a href="dashboard.php" class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-600 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <i class="fa-solid fa-chart-pie text-slate-400 w-4"></i> Overview Dashboard
                            </a>
                            <a href="logout.php" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-power-off text-red-500 w-4"></i> Secure Sign Out
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Hamburg Menu Interface Anchor Icon -->
                <button id="mobile-menu-hamburger-btn" class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 hover:text-slate-900 bg-slate-50 border border-slate-200 transition-all focus:outline-none">
                    <i id="mobile-menu-hamburger-icon" class="fa-solid fa-bars text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Mobile View Navigation Drawer Structure Configuration Layout -->
        <div id="mobile-navigation-tray" class="hidden md:hidden border-t border-slate-200/80 bg-white/95 px-4 py-3 space-y-1 shadow-inner backdrop-blur-md">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'dashboard.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-house w-4 text-center"></i> Home
            </a>
            <a href="meal.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'meal.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-utensils w-4 text-center"></i> Bookings
            </a>
            <a href="generate_qr.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'generate_qr.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-qrcode w-4 text-center"></i> QR Token
            </a>
            <a href="leave_manager.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'leave_manager.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-calendar-minus w-4 text-center"></i> Leaves
            </a>
            <a href="submit_feedback.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'submit_feedback.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-comment-dots w-4 text-center"></i> Feedback
            </a>
            <a href="bill.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?php echo $student_page == 'bill.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50'; ?>">
                <i class="fa-solid fa-wallet w-4 text-center"></i> Account Statements
            </a>
        </div>
    </header>

    <!-- Standard Client Side Interaction Engine Interfacing -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Simulated Smooth Client Load Status Progress Indicator Accent Line
            const progress = document.getElementById('student-progress-bar');
            if (progress) {
                progress.style.width = '30%';
                setTimeout(() => { progress.style.width = '70%'; }, 150);
                setTimeout(() => { progress.style.width = '100%'; }, 350);
                setTimeout(() => { progress.style.opacity = '0'; }, 650);
            }

            // Dropdown Menu & Tray DOM Elements Selection Definition Setup Matrix
            const profileBtn = document.getElementById('profile-dropdown-btn');
            const profileMenu = document.getElementById('profile-dropdown-menu');
            const profileChevron = document.getElementById('profile-chevron');
            
            const bellBtn = document.getElementById('notification-bell-btn');
            const bellTray = document.getElementById('notification-panel-tray');
            
            const mobileBtn = document.getElementById('mobile-menu-hamburger-btn');
            const mobileTray = document.getElementById('mobile-navigation-tray');
            const mobileIcon = document.getElementById('mobile-menu-hamburger-icon');

            // Generic Event Delegation Layer Toggle Function Mechanism
            function closeAllMenus() {
                if(profileMenu) { profileMenu.classList.remove('active'); if(profileChevron) profileChevron.style.transform = 'rotate(0deg)'; }
                if(bellTray) bellTray.classList.remove('active');
            }

            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isActive = profileMenu.classList.contains('active');
                    closeAllMenus();
                    if (!isActive) {
                        profileMenu.classList.add('active');
                        if(profileChevron) profileChevron.style.transform = 'rotate(180deg)';
                    }
                });
            }

            if (bellBtn && bellTray) {
                bellBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isActive = bellTray.classList.contains('active');
                    closeAllMenus();
                    if (!isActive) {
                        bellTray.classList.add('active');
                    }
                });
            }

            if (mobileBtn && mobileTray) {
                mobileBtn.addEventListener('click', () => {
                    mobileTray.classList.toggle('hidden');
                    if (mobileTray.classList.contains('hidden')) {
                        mobileIcon.className = 'fa-solid fa-bars text-sm';
                    } else {
                        mobileIcon.className = 'fa-solid fa-xmark text-sm';
                    }
                });
            }

            // Global Click Away Event Handler Core Masking Context Function
            document.addEventListener('click', () => { closeAllMenus(); });
        });
    </script>

    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 transition-all duration-300">