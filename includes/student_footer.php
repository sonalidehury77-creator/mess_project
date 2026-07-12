</main> <!-- Close main .flex-1 dynamic wrapper payload node area link -->

    <!-- ==========================================================================
        GLOBAL LAYOUT SYSTEM FOOTER (Premium Professional Redesign)
        ========================================================================== -->
    <footer class="bg-white border-t border-slate-200/80 mt-auto shadow-inner relative z-30">
        <!-- Main Footer Directory / Metadata Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <!-- Col 1: System Identity and Operational Context -->
                <div class="md:col-span-1 space-y-3">
                    <div class="flex items-center gap-2.5">
                        <div class="bg-emerald-600 text-white w-7 h-7 flex items-center justify-center rounded-lg shadow-sm">
                            <i class="fa-solid fa-graduation-cap text-xs"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-900 tracking-wider uppercase">BOARDER HUB</span>
                    </div>
                    <p class="text-slate-500 text-xs font-normal leading-relaxed">
                        Enterprise Hostel Dining Automation Matrix providing high-integrity systemic provisioning, real-time access verification, and administrative ledger control.
                    </p>
                </div>

                <!-- Col 2: Dynamic Quick Links Directory -->
                <div class="space-y-3">
                    <h4 class="text-slate-900 font-bold text-xs tracking-wider uppercase">Quick Actions</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="dashboard.php" class="text-slate-500 hover:text-emerald-600 font-medium transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-angle-right text-[9px] text-slate-300"></i> Home Dashboard
                            </a>
                        </ul>
                </div>

                <!-- Col 3: Resource Statements Directory -->
                <div class="space-y-3">
                    <h4 class="text-slate-900 font-bold text-xs tracking-wider uppercase">Account & Leaves</h4>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <a href="leave_manager.php" class="text-slate-500 hover:text-emerald-600 font-medium transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-angle-right text-[9px] text-slate-300"></i> Leave Management
                            </a>
                        </li>
                        <li>
                            <a href="bill.php" class="text-slate-500 hover:text-emerald-600 font-medium transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-angle-right text-[9px] text-slate-300"></i> Account Ledger
                            </a>
                        </li>
                        <li>
                            <a href="submit_feedback.php" class="text-slate-500 hover:text-emerald-600 font-medium transition-colors flex items-center gap-2">
                                <i class="fa-solid fa-angle-right text-[9px] text-slate-300"></i> Feedback & Polls
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 4: Helpdesk Information & Social Handles -->
                <div class="space-y-3">
                    <h4 class="text-slate-900 font-bold text-xs tracking-wider uppercase">System Helpdesk</h4>
                    <p class="text-slate-500 text-xs font-normal leading-relaxed flex items-center gap-2">
                        <i class="fa-solid fa-headset text-slate-400"></i> Support: support@hostelmess.edu
                    </p>
                    <div class="flex items-center gap-2.5 pt-1">
                        <a href="#" class="w-7 h-7 bg-slate-50 border border-slate-200 text-slate-500 hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50 rounded-lg flex items-center justify-center transition-all text-xs" title="Institutional Portal">
                            <i class="fa-solid fa-globe"></i>
                        </a>
                        <a href="#" class="w-7 h-7 bg-slate-50 border border-slate-200 text-slate-500 hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50 rounded-lg flex items-center justify-center transition-all text-xs" title="System Documentation">
                            <i class="fa-solid fa-book"></i>
                        </a>
                        <a href="#" class="w-7 h-7 bg-slate-50 border border-slate-200 text-slate-500 hover:text-emerald-600 hover:border-emerald-200 hover:bg-emerald-50 rounded-lg flex items-center justify-center transition-all text-xs" title="GitHub Repository">
                            <i class="fa-brands fa-github"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- System Compliance Bottom Bar -->
        <div class="bg-slate-50/50 border-t border-slate-100 py-4 text-xs font-medium text-slate-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <p>&copy; <?php echo date('Y'); ?> Student Hostel Dining Automation Matrix. All Rights Reserved.</p>
                </div>
                <div class="flex items-center gap-4 text-[11px] text-slate-400 uppercase tracking-wider">
                    <span>v<?php echo escape_output(defined('SYSTEM_VERSION') ? SYSTEM_VERSION : '2.0.0'); ?></span>
                    <span class="text-slate-200">|</span>
                    <span class="flex items-center gap-1"><i class="fa-solid fa-shield-halved text-emerald-500"></i> Operational Security Assured</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Floating Quick Action Back To Top Controller Hub Component Element Node Block -->
    <button id="back-to-top-anchor" class="fixed bottom-6 left-6 z-40 w-10 h-10 bg-slate-900 text-white rounded-xl shadow-xl flex items-center justify-center transition-all duration-300 opacity-0 translate-y-10 pointer-events-none hover:bg-emerald-600 border border-slate-800 hover:border-emerald-500 focus:outline-none">
        <i class="fa-solid fa-arrow-up text-xs"></i>
    </button>

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
                <i class="fa-solid <?php echo $toast_icon; ?> text-sm"></i>
                <span><?php echo escape_output($_SESSION['system_toast']['message']); ?></span>
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
                    const loader = document.getElementById('student-form-loader');
                    if (loader) loader.classList.remove('hidden');
                });
            }
        });

        // Interactive Scrolling Back to Top Execution Handler Logic
        const backToTopBtn = document.getElementById('back-to-top-anchor');
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    backToTopBtn.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                } else {
                    backToTopBtn.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                }
            });

            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
</body>
</html>