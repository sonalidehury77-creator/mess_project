</div> </div> <?php if (isset($_SESSION['system_toast'])): ?>
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
            <button onclick="document.getElementById('toast-notification-dock').remove()" class="ml-4 text-slate-500 hover:text-white transition-colors">
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

<div id="global-submit-loader" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 hidden flex items-center justify-center transition-all duration-200">
    <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center gap-3 border border-slate-100 animate-fade-in">
        <div class="w-10 h-10 border-4 border-blue-600/20 border-t-blue-600 rounded-full animate-spin"></div>
        <p class="text-xs font-bold text-slate-700 tracking-wider uppercase">Processing Request...</p>
    </div>
</div>

<script src="../js/admin.js"></script>
<script>
    // System Loader Integration
    window.addEventListener('load', () => {
        const bar = document.getElementById('system-loading-bar');
        if (bar) {
            bar.style.width = '100%';
            setTimeout(() => bar.style.opacity = '0', 350);
        }
    });

    // Enforce form submission spinner protections
    document.querySelectorAll('form').forEach(form => {
        if (!form.classList.contains('no-loader')) {
            form.addEventListener('submit', () => {
                document.getElementById('global-submit-loader').classList.remove('hidden');
            });
        }
    });
</script>
</body>
</html>