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

// 2. Identity & Access Guard (RBAC - Student Access Validation)
// Checks for active registration or session markers; bounces to login if not authenticated
if (!isset($_SESSION['student_roll']) && !isset($_SESSION['student'])) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$student_page = basename($_SERVER['PHP_SELF']);

// 3. Extract Session Context Metadata for Dynamic Profile Presentation
$student_name = $_SESSION['student_name'] ?? 'Student Boarder';
$student_avatar = !empty($_SESSION['student_photo']) ? '../uploads/students/' . $_SESSION['student_photo'] : PATH_DEFAULT_AVATAR;
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
        .nav-link-active { color: #059669 !important; background-color: #ecfdf5; border-radius: 0.75rem; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
    </style>
</head>
<body class="h-full bg-[#F8FAFC] flex flex-col text-slate-900 selection:bg-emerald-500 selection:text-white">

    <div id="student-progress-bar" class="fixed top-0 left-0 right-0 h-1 bg-emerald-600 z-50 transition-all duration-300 w-0"></div>

    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            
            <div class="flex items-center gap-3">
                <div class="bg-emerald-600 text-white w-9 h-9 flex items-center justify-center rounded-xl shadow-md shadow-emerald-600/20">
                    <i class="fa-solid fa-graduation-cap text-base"></i>
                </div>
                <div>
                    <h1 class="text-xs font-black text-slate-900 uppercase tracking-wider leading-none">BOARDER HUB</h1>
                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5 block">Dining Panel</span>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-1">
                <a href="dashboard.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo $student_page == 'dashboard.php' ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">Home</a>
                <a href="meal.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo in_array($student_page, ['meal.php', 'save_meal.php']) ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">Bookings</a>
                <a href="generate_qr.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo $student_page == 'generate_qr.php' ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">QR Token</a>
                <a href="leave_manager.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo $student_page == 'leave_manager.php' ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">Leaves</a>
                <a href="submit_feedback.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo $student_page == 'submit_feedback.php' ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">Feedback & Polls</a>
                <a href="bill.php" class="px-3.5 py-2 text-[11px] font-bold tracking-wider uppercase transition-all <?php echo in_array($student_page, ['bill.php', 'view_bill.php', 'print_bill.php', 'graph.php']) ? 'nav-link-active text-emerald-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl'; ?>">Ledger</a>
            </nav>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 border-r border-slate-200 pr-3 hidden sm:flex">
                    <img src="<?php echo $student_avatar; ?>" alt="Avatar" class="w-7 h-7 rounded-lg object-cover border border-slate-100 shadow-sm">
                    <span class="text-xs font-bold text-slate-700 max-w-[100px] truncate"><?php echo escape_output($student_name); ?></span>
                </div>
                
                <button onclick="toggleMobileNavigationMenu()" class="md:hidden p-2 text-slate-500 hover:text-slate-900 focus:outline-none">
                    <i id="mobile-menu-hamburger-icon" class="fa-solid fa-bars text-lg"></i>
                </button>

                <a href="logout.php" class="text-slate-500 hover:text-red-600 text-xs font-bold uppercase tracking-wider flex items-center gap-2 px-3 py-2 border border-slate-200/80 hover:border-red-200 hover:bg-red-50 rounded-xl transition-all">
                    <i class="fa-solid fa-power-off text-xs"></i> <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>

        <div id="mobile-navigation-tray" class="hidden md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1 shadow-inner">
            <a href="dashboard.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'dashboard.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">Home</a>
            <a href="meal.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'meal.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">Bookings</a>
            <a href="generate_qr.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'generate_qr.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">QR Token</a>
            <a href="leave_manager.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'leave_manager.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">Leaves</a>
            <a href="submit_feedback.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'submit_feedback.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">Feedback</a>
            <a href="bill.php" class="block px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider <?php echo $student_page == 'bill.php' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600'; ?>">Account Statements</a>
        </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto p-4 sm:p-6 transition-all duration-300">