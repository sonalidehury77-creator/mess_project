<?php
/**
 * 🏢 Enterprise Administrative Stateful Sidebar Navigation Rail
 * Features dynamic page state processing, active UI highlighting loops, 
 * session profile badges, and full architecture file tracking mapping.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// Safeguard default variable displays if profile context mapping arrays haven't completed
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Administrator';
$admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'Super User';
?>
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col transition-all duration-300 shadow-xl z-20 shrink-0 border-r border-slate-800">
    
    <div class="p-5 border-b border-slate-800 flex items-center gap-3 bg-slate-950/20">
        <div class="bg-blue-600 text-white p-2 rounded-xl shadow-lg shadow-blue-600/20 flex items-center justify-center">
            <i class="fa-solid fa-shield-halved text-lg"></i>
        </div>
        <div>
            <h2 class="text-white font-extrabold text-sm tracking-tight">MESS MODULE</h2>
            <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">Control Workspace</p>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto custom-scrollbar">
        
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'dashboard.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-chart-pie text-sm w-5 text-center"></i> Dashboard Central
        </a>
        
        <a href="scan_token.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'scan_token.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-qrcode text-sm w-5 text-center"></i> Entry QR Scanner
        </a>
        
        <div class="pt-4 pb-1 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 mt-2">Operations</div>

        <a href="students.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo in_array($current_page, ['students.php', 'add_student.php', 'edit_student.php']) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-users text-sm w-5 text-center"></i> Student Directory
        </a>
        
        <a href="menu.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'menu.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-egg text-sm w-5 text-center"></i> Standard Menu
        </a>

        <a href="manage_special_menu.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'manage_special_menu.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-cake-candles text-sm w-5 text-center"></i> Festival Special
        </a>

        <a href="announcements.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'announcements.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-bullhorn text-sm w-5 text-center"></i> Announcements
        </a>

        <div class="pt-4 pb-1 px-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest border-t border-slate-800/60 mt-2">Logs & Analytics</div>

        <a href="leave_analytics.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'leave_analytics.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-calendar-minus text-sm w-5 text-center"></i> Leave Requests
        </a>

        <a href="feedback_analytics.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo $current_page == 'feedback_analytics.php' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-heart-crack text-sm w-5 text-center"></i> Quality & Reviews
        </a>

        <a href="bill.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold tracking-wider uppercase transition-all <?php echo in_array($current_page, ['bill.php', 'view_bill.php', 'report.php']) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-slate-100'; ?>">
            <i class="fa-solid fa-file-invoice-dollar text-sm w-5 text-center"></i> Finance Ledger
        </a>
    </nav>

    <div class="p-4 border-t border-slate-800 bg-slate-950/50 flex flex-col gap-3">
        <div class="flex items-center gap-3 px-1.5">
            <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center border border-slate-700 font-bold text-xs text-white uppercase tracking-wider">
                <?php echo substr($admin_name, 0, 2); ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate leading-tight"><?php echo escape_output($admin_name); ?></p>
                <p class="text-[10px] font-semibold text-slate-400 truncate tracking-wide mt-0.5"><?php echo escape_output($admin_role); ?></p>
            </div>
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
        </div>

        <a href="logout.php" class="flex items-center justify-center gap-2 w-full bg-slate-800 hover:bg-red-950/40 hover:text-red-400 text-slate-300 font-bold text-xs py-2.5 px-4 rounded-xl border border-slate-700/60 transition-all group duration-200">
            <i class="fa-solid fa-power-off text-[11px] group-hover:rotate-45 transition-transform"></i> Terminate Admin Session
        </a>
    </div>
</aside>

<div class="flex-1 flex flex-col min-w-0 min-h-screen">