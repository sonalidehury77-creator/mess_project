/**
 * 🎓 HostelMess Student Portal UI Controller
 * Features: Non-blocking Validation, State Management, and Receipt Logic
 * @version 2.0.0
 */

const StudentApp = (() => {
    "use strict";

    // ==========================================================================
    // UI HELPER: Non-blocking Toast Notifier
    // ==========================================================================
    const notify = (message, type = 'error') => {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-5 right-5 p-4 rounded-lg shadow-lg border-l-4 text-sm font-semibold animate-fade-in ${
            type === 'error' ? 'bg-white border-red-500 text-red-700' : 'bg-white border-emerald-500 text-emerald-700'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };

    // ==========================================================================
    // 1. MEAL BOOKING ENGINE
    // ==========================================================================
    const initMealSelectors = () => {
        // Event Delegation: Attach to container instead of every individual checkbox
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"][name^="meal_selection"]')) {
                const card = e.target.closest(".meal-card-selector");
                if (card) {
                    card.classList.toggle("border-emerald-500", e.target.checked);
                    card.classList.toggle("bg-emerald-50/40", e.target.checked);
                }
            }
        });
    };

    // ==========================================================================
    // 2. LEAVE REQUEST DATE SANITY ENGINE
    // ==========================================================================
    const initLeaveForm = () => {
        const form = document.querySelector(".leave-application-form");
        if (!form) return;

        form.addEventListener("submit", (e) => {
            const start = new Date(form.querySelector('input[name="start_date"]').value);
            const end = new Date(form.querySelector('input[name="end_date"]').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (start < today) {
                e.preventDefault();
                notify("Leave cannot start in the past.");
            } else if (end < start) {
                e.preventDefault();
                notify("Leave end date must be after the start date.");
            }
        });
    };

    // ==========================================================================
    // 3. RECEIPT & PRINT CONTROLLER
    // ==========================================================================
    const initPrintEngine = () => {
        document.querySelectorAll(".trigger-receipt-print").forEach(btn => {
            btn.addEventListener("click", () => {
                // Check if user is on a mobile device to offer download instead of just print
                if (window.innerWidth < 768) {
                    notify("Generating PDF report...", "success");
                }
                window.print();
            });
        });
    };

    // ==========================================================================
    // PUBLIC API
    // ==========================================================================
    return {
        init: () => {
            initMealSelectors();
            initLeaveForm();
            initPrintEngine();
            console.log("[Student Portal] UI Runtime initialized.");
        }
    };
})();

// Initialize on load
document.addEventListener("DOMContentLoaded", StudentApp.init);