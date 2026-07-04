</main> <!-- Close main .flex-1 dynamic wrapper payload node area link -->

    <!-- ==========================================================================
       GLOBAL LAYOUT SYSTEM FOOTER
       ========================================================================== -->
    <footer class="bg-white border-t border-slate-200/80 py-5 mt-auto text-xs font-semibold text-slate-500 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <p>&copy; <?= date('Y'); ?> Student Hostel Dining Automation Matrix. All Rights Reserved.</p>
            </div>
            <div class="flex items-center gap-4 text-[11px] text-slate-400 uppercase tracking-wider">
                <span>v<?= escape_output(defined('SYSTEM_VERSION') ? SYSTEM_VERSION : '2.0.0'); ?></span>
                <span class="text-slate-200">|</span>
                <span>System Integrity Operational Security Assured</span>
            </div>
        </div>
    </footer>

    <!-- ==========================================================================
       ENTERPRISE CLIENT-SIDE OPERATIONAL MODALS & TRANSACTION TOAST ALERTS
       ========================================================================== -->
    <?php if (isset($_SESSION['system_toast'])): ?>
        <div id="student-toast-dock" class="fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 transition-all duration-300 ease-out">
            <div class="flex items-center gap-3 bg-slate-900 text-white px-5 py-3.5 rounded-xl shadow-2xl border border-slate-800 text-xs font-bold tracking-wide">
                <?php 
                    $toast_type = $_SESSION['system_toast']['type'] ?? 'info';
                    $toast_icon = match($toast_type) {
                        'success' => 'fa-circle-check text-emerald-400',
                        'error'   => 'fa-circle-exclamation text-red-400',
                        'warning' => 'fa-triangle-exclamation text-amber-400',
                        default   => 'fa-circle-info text-blue-400'
                    };
                ?>
                <i class="fa-solid <?= $toast_icon; ?> text-sm"></i>
                <span><?= escape_output($_SESSION['system_toast']['message']); ?></span>
                <button onclick="document.getElementById('student-toast-dock').remove()" class="ml-4 text-slate-500 hover:text-white transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const dock = document.getElementById('student-toast-dock');
                if (dock) {
                    setTimeout(() => dock.classList.remove('translate-y-10', 'opacity-0'), 100);
                    setTimeout(() => {
                        dock.classList.add('translate-y-10', 'opacity-0');
                        setTimeout(() => dock.remove(), 300);
                    }, 4000);
                }
            });
        </script>
        <?php unset($_SESSION['system_toast']); ?>
    <?php endif; ?>

    <!-- ==========================================================================
       ANTI-DOUBLE-SUBMIT FORM PROCESSING LOADER MODAL
       ========================================================================== -->
    <div id="student-form-loader" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-all duration-200">
        <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 border-4 border-emerald-600/20 border-t-emerald-600 rounded-full animate-spin"></div>
            <p class="text-xs font-bold text-slate-700 tracking-wider uppercase">Syncing Transaction Matrix...</p>
        </div>
    </div>

    <!-- ==========================================================================
       CORE PLATFORM RUNTIMES & UTILITY HOOKS
       ========================================================================== -->
    <script src="../js/student.js"></script>
    <script>
        // Smooth system top micro progress loader animator close layout anchor
        window.addEventListener('load', () => {
            const bar = document.getElementById('student-progress-bar');
            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => bar.style.opacity = '0', 300);
            }
        });

        // Toggle Responsive Burger Navigation Sub-Matrix Tray
        function toggleMobileNavigationMenu() {
            const tray = document.getElementById('mobile-navigation-tray');
            const icon = document.getElementById('mobile-menu-hamburger-icon');
            if (tray && icon) {
                tray.classList.toggle('hidden');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-xmark');
            }
        }

        // Global intercept to trigger loading overlay indicators on processing tasks
        document.querySelectorAll('form').forEach(form => {
            if (!form.classList.contains('no-loader')) {
                form.addEventListener('submit', () => {
                    document.getElementById('student-form-loader').classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>