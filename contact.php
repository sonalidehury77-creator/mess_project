<?php
/**
 * 🌐 Hostel Mess Management System - Contact Page
 * Features:
 * - Security Headers
 * - Secure Session Configuration alignment
 */

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Campus Residency Dining Administration">
    
    <title>Contact Us | Hostel Mess Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        univ: {
                            50: '#f0f4f8',
                            100: '#dbe3ed',
                            600: '#1e3a8a',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        gold: {
                            500: '#eab308',
                            600: '#ca8a04',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f8fafc;
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .hero-pattern {
            background-image: radial-gradient(rgba(30, 58, 138, 0.1) 1px, transparent 0);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased text-slate-800 bg-slate-50 selection:bg-univ-100">

    <!-- STICKY NAVBAR -->
    <header class="sticky top-0 z-50 w-full glass-nav border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="bg-univ-900 text-white w-11 h-11 flex items-center justify-center rounded-xl shadow-md">
                    <i class="fa-solid fa-utensils text-xl"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold tracking-tight text-univ-900 leading-none">CAMPUS DINING</h1>
                    <span class="text-[10px] text-gold-600 font-bold uppercase tracking-[0.15em]">Smart Mess System</span>
                </div>
            </a>
            
            <nav class="hidden lg:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="index.php" class="hover:text-univ-900 transition-colors">Home</a>
                <a href="about.php" class="hover:text-univ-900 transition-colors">About Us</a>
                <a href="#contact-info" class="hover:text-univ-900 transition-colors">Information</a>
                <a href="#location-map" class="hover:text-univ-900 transition-colors">Find Us</a>
            </nav>

            <div class="flex items-center gap-4">
                <a href="index.php#portals" class="bg-univ-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md hover:bg-univ-800 transition-all uppercase tracking-wider">
                    Login Portals
                </a>
                <button id="mobile-menu-btn" class="lg:hidden text-slate-700 hover:text-univ-900 text-xl focus:outline-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-6 space-y-2 shadow-inner">
            <a href="index.php" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Home</a>
            <a href="about.php" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">About Us</a>
            <a href="#contact-info" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Information</a>
            <a href="#location-map" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Find Us</a>
        </div>
    </header>

    <!-- HEADER TITLE BANNER -->
    <section class="relative bg-gradient-to-b from-univ-900 to-slate-900 text-white py-16 overflow-hidden">
        <div class="absolute inset-0 hero-pattern opacity-10"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-3 relative z-10">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">Helpdesk & Communications</h2>
            <p class="text-slate-300 font-light text-sm max-w-xl mx-auto">Have questions regarding meal configurations, adjustments, or administrative logs? Reach out directly.</p>
        </div>
    </section>

    <!-- CORE COMMUNICATIONS GRID -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 flex-grow w-full">
        <div class="space-y-8" id="contact-info">
            <div>
                <span class="text-xs text-univ-600 font-bold uppercase tracking-widest bg-univ-50 px-3 py-1 rounded-full">Contact Channels</span>
                <h3 class="text-2xl font-bold text-univ-900 mt-3 tracking-tight">Administrative Offices</h3>
                <p class="text-slate-500 font-light text-xs mt-2">Get in touch with the core technical management team or residency council office lines.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Phone Contact -->
                <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex flex-col gap-4 items-start">
                    <div class="text-univ-900 text-base bg-univ-50 w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wide">Helpline Contacts</h4>
                        <p class="text-xs text-slate-600 mt-1 font-mono">+91 986534124 (Office)</p>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono">+91 8796444213 (Emergency)</p>
                    </div>
                </div>

                <!-- Email Contact -->
                <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex flex-col gap-4 items-start">
                    <div class="text-emerald-600 text-base bg-emerald-50 w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wide">Electronic Inquiries</h4>
                        <p class="text-xs text-slate-600 mt-1 font-mono">mahanadi_hostel@university.edu</p>
                        <p class="text-xs text-slate-400 mt-0.5 font-mono">housing-welfare@university.edu</p>
                    </div>
                </div>

                <!-- Working Hours -->
                <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex flex-col gap-4 items-start">
                    <div class="text-amber-600 text-base bg-amber-50 w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wide">Operational Hours</h4>
                        <p class="text-xs text-slate-600 mt-1">Mon &mdash; Fri: 08:00 AM &mdash; 05:00 PM</p>
                        <p class="text-xs text-slate-600 mt-0.5">Sat (Emergency): 09:00 AM &mdash; 01:00 PM</p>
                        <span class="inline-block mt-2 text-[10px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded uppercase tracking-wider">Closed on Sundays</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- GOOGLE MAP FIXED LOCATION SECTION -->
    <section class="w-full bg-slate-100 border-t border-slate-200 py-12" id="location-map">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-6 space-y-1">
                <h4 class="font-bold text-sm text-univ-900 uppercase tracking-wide">Campus Location Matrix</h4>
                <p class="text-slate-500 font-light text-xs">Central Logistics Complex &mdash; Ravenshaw University,Cuttack</p>
            </div>
            
            <!-- Real Interactive Embedded Google Map -->
            <div class="w-full h-96 rounded-2xl border border-slate-200 bg-slate-200 overflow-hidden shadow-md">
                <iframe 
                    class="w-full h-full border-0"
                    src="https://maps.google.com/maps?q=Ravenshaw_University,Cuttack&t=&z=15&ie=UTF8&iwloc=&output=embed" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="w-full bg-slate-900 text-slate-400 text-xs border-t border-slate-800 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-center sm:text-left">
            <div class="flex items-center gap-2 text-white">
                <div class="bg-white/10 text-white w-7 h-7 flex items-center justify-center rounded-lg text-xs"><i class="fa-solid fa-utensils"></i></div>
                <span class="font-bold tracking-tight text-xs uppercase">CAMPUS DINING</span>
            </div>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">
                &copy; 2026 Hostel Mess Management System. All Rights Reserved.
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Script Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            if(menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                    const icon = menuBtn.querySelector('i');
                    icon.className = mobileMenu.classList.contains('hidden') ? 'fa-solid fa-bars' : 'fa-solid fa-xmark';
                });
            }
        });
    </script>
</body>
</html>