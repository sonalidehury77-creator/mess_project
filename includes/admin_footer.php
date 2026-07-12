</main> <!-- Close main dynamic context component block payload node area -->

            <!-- ==========================================================================
                ENTERPRISE ADMINISTRATIVE GLOBAL LAYOUT SYSTEM FOOTER
                ========================================================================== -->
            <footer class="bg-white border-t border-slate-200/80 py-4 px-4 sm:px-6 lg:px-8 mt-auto shadow-sm relative z-10">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
                    
                    <!-- Left Context Element: Corporate Domain Copyright Node -->
                    <div class="flex items-center gap-2.5 text-center md:text-left">
                        <div class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></div>
                        <p class="text-xs font-semibold text-slate-500">
                            &copy; <?php echo date('Y'); ?> <span class="text-slate-800 font-bold">Boarder Hub</span> Enterprise Core. System Administration Panel.
                        </p>
                    </div>

                    <!-- Right Context Element: Professional Metadata Infrastructure Specs -->
                    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-[11px] text-slate-400 font-medium tracking-wide uppercase">
                        <span class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-code-branch text-blue-500"></i> v<?php echo escape_output(defined('SYSTEM_VERSION') ? SYSTEM_VERSION : '2.0.0'); ?>
                        </span>
                        <span class="hidden sm:inline text-slate-200">|</span>
                        <span class="flex items-center gap-1.5 bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-microchip text-slate-400"></i> Platform: Enterprise Engine
                        </span>
                        <span class="hidden sm:inline text-slate-200">|</span>
                        <a href="mailto:admin-support@hostelmess.edu" class="flex items-center gap-1.5 hover:text-blue-600 transition-colors bg-slate-50 hover:bg-blue-50/50 border border-slate-200/60 hover:border-blue-200 px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-headset text-slate-400"></i> Infrastructure Support
                        </a>
                    </div>

                </div>
            </footer>

        </div> <!-- Close right side primary screen framework wrapper -->
    </div> <!-- Close admin master layout container grid -->

    <!-- ==========================================================================
        ENTERPRISE CLIENT-SIDE OPERATIONAL MODALS & TRANSACTION TOAST ALERTS
        ========================================================================== -->
    <?php if (isset($_SESSION['system_toast'])): ?>
        <div id="toast-notification-dock" class="fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 transition-all duration-300 ease-out">
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
                <button onclick="document.getElementById('toast-notification-dock').remove()" class="ml-4 text-slate-500 hover:text-white transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const dock = document.getElementById('toast-notification-dock');
                if (dock) {
                    setTimeout(() => {
                        dock.classList.remove('translate-y-10', 'opacity-0');
                    }, 100);
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
    <div id="global-submit-loader" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-all duration-200">
        <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center gap-3 border border-slate-100">
            <div class="w-10 h-10 border-4 border-blue-600/20 border-t-blue-600 rounded-full animate-spin"></div>
            <p class="text-xs font-bold text-slate-700 tracking-wider uppercase">Processing Request...</p>
        </div>
    </div>

    <!-- ==========================================================================
        CORE PLATFORM RUNTIMES & UTILITY HOOKS
        ========================================================================== -->
    <script src="../js/admin.js"></script>
    <script>
        // System Loader Integration Closure Control Anchor
        window.addEventListener('load', () => {
            const bar = document.getElementById('system-loading-bar');
            if (bar) {
                bar.style.width = '100%';
                setTimeout(() => bar.style.opacity = '0', 350);
            }
        });

        // Enforce form submission spinner protections context engine layers
        document.querySelectorAll('form').forEach(form => {
            if (!form.classList.contains('no-loader')) {
                form.addEventListener('submit', () => {
                    const loader = document.getElementById('global-submit-loader');
                    if (loader) loader.classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>