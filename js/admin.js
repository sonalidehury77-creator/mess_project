/**
 * 🏢 Enterprise HostelMess Administrative App Controller
 * Handles Global UI Bindings, Async Form Intercepts, and DOM Manipulations
 * @version 2.0.0
 */

const AdminApp = (() => {
    "use strict";

    // ==========================================================================
    // CONFIGURATION & STATE
    // ==========================================================================
    const CONFIG = {
        toastDuration: 4500, // 4.5 seconds
        selectors: {
            toast: '.toast-banner, #console-log, .alert-banner',
            actionForm: '.inline-action-form, form:not(.no-loader)',
            submenuTrigger: '.sidebar-dropdown-trigger',
            submitBtn: 'button[type="submit"]'
        }
    };

    // ==========================================================================
    // 1. TOAST NOTIFICATION MANAGER (With pause-on-hover & DOM cleanup)
    // ==========================================================================
    const initToasts = () => {
        const toasts = document.querySelectorAll(CONFIG.selectors.toast);
        
        toasts.forEach(toast => {
            let timeoutId;

            const dismiss = () => {
                toast.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.2, 1)";
                toast.style.opacity = "0";
                toast.style.transform = "translateY(-10px)";
                // Fully remove from DOM to prevent memory leaks
                setTimeout(() => toast.remove(), 400); 
            };

            const startTimer = () => {
                timeoutId = setTimeout(dismiss, CONFIG.toastDuration);
            };

            // Pause timer if user hovers over the alert to read it
            toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
            toast.addEventListener('mouseleave', startTimer);

            // Add manual close button support (if a close button exists inside)
            const closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    clearTimeout(timeoutId);
                    dismiss();
                });
            }

            startTimer();
        });
    };

    // ==========================================================================
    // 2. TRANSACTIONAL FORM SECURITY (Anti-Double-Submit & Confirmations)
    // ==========================================================================
    const initForms = () => {
        const forms = document.querySelectorAll(CONFIG.selectors.actionForm);
        
        forms.forEach(form => {
            form.addEventListener("submit", (e) => {
                // 1. Check for lock (Prevents user from double-clicking submit)
                if (form.dataset.submitting === "true") {
                    e.preventDefault();
                    return;
                }

                // 2. Intercept destructive actions for confirmation
                if (form.classList.contains('inline-action-form') || form.hasAttribute('data-confirm')) {
                    const actionType = form.dataset.actionName || form.querySelector('input[name="action_type"]')?.value || "process";
                    const customMsg = form.dataset.confirmMsg;
                    const confirmMessage = customMsg || `Are you sure you want to ${actionType} this record? This action cannot be undone.`;
                    
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return;
                    }
                }

                // 3. Lock form and show visual feedback
                form.dataset.submitting = "true";
                const btn = form.querySelector(CONFIG.selectors.submitBtn);
                
                if (btn && !btn.classList.contains('no-loading-state')) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Executing...`;
                    btn.classList.add('opacity-75', 'cursor-wait');
                    
                    // Failsafe unlock after 10 seconds in case of a network stall
                    setTimeout(() => {
                        form.dataset.submitting = "false";
                        btn.innerHTML = originalText;
                        btn.classList.remove('opacity-75', 'cursor-wait');
                    }, 10000);
                }
            });
        });
    };

    // ==========================================================================
    // 3. INTELLIGENT SIDEBAR ACCORDION 
    // ==========================================================================
    const initSidebar = () => {
        const triggers = document.querySelectorAll(CONFIG.selectors.submenuTrigger);
        
        triggers.forEach(trigger => {
            trigger.addEventListener("click", (e) => {
                e.preventDefault();
                
                const targetMenu = trigger.nextElementSibling;
                const icon = trigger.querySelector(".chevron-icon");
                
                if (targetMenu) {
                    const isOpening = targetMenu.classList.contains("hidden");

                    // Optional: Close all other open sibling menus (Exclusive Accordion behavior)
                    if (trigger.closest('.sidebar-menu-group')) {
                        const siblings = document.querySelectorAll(CONFIG.selectors.submenuTrigger);
                        siblings.forEach(sib => {
                            if (sib !== trigger) {
                                sib.nextElementSibling?.classList.add("hidden");
                                sib.querySelector(".chevron-icon")?.classList.remove("rotate-180");
                            }
                        });
                    }

                    // Toggle clicked menu
                    targetMenu.classList.toggle("hidden", !isOpening);
                    if (icon) {
                        icon.classList.toggle("rotate-180", isOpening);
                    }
                }
            });
        });
    };

    // ==========================================================================
    // PUBLIC API / INIT
    // ==========================================================================
    return {
        init: () => {
            try {
                initToasts();
                initForms();
                initSidebar();
                console.log("[Admin System] UI runtime controllers engaged.");
            } catch (error) {
                console.error("[Admin System] Initialization error:", error);
            }
        }
    };
})();

// Boot the application engine when the DOM is fully constructed
document.addEventListener("DOMContentLoaded", AdminApp.init);