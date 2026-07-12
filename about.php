<?php
/**
 * 🌐 Hostel Mess Management System - About Page
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
    <meta name="description" content="About our Campus Residency Dining Framework">
    
    <title>About Us | Hostel Mess Portal</title>
    
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

    <!-- 1. STICKY NAVBAR -->
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
                <a href="#introduction" class="hover:text-univ-900 transition-colors">Introduction</a>
                <a href="#mission-vision" class="hover:text-univ-900 transition-colors">Mission & Vision</a>
                <a href="#warden" class="hover:text-univ-900 transition-colors">Warden Message</a>
                <a href="#facilities" class="hover:text-univ-900 transition-colors">Facilities</a>
                <a href="#rules" class="hover:text-univ-900 transition-colors">Rules</a>
                <a href="#timeline" class="hover:text-univ-900 transition-colors">Timeline</a>
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
            <a href="#introduction" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Introduction</a>
            <a href="#mission-vision" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Mission & Vision</a>
            <a href="#warden" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Warden Message</a>
            <a href="#facilities" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Facilities</a>
            <a href="#rules" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Rules</a>
            <a href="#timeline" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Timeline</a>
        </div>
    </header>

    <!-- HEADER TITLE BANNER -->
    <section class="relative bg-gradient-to-b from-univ-900 to-slate-900 text-white py-16 overflow-hidden">
        <div class="absolute inset-0 hero-pattern opacity-10"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-3 relative z-10">
            <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">Institutional Profile & Directives</h2>
            <p class="text-slate-300 font-light text-sm max-w-xl mx-auto">Discover the foundational pillars, structural protocols, and administrative parameters governing our student dining experience.</p>
        </div>
    </section>

    <!-- 2. HOSTEL INTRODUCTION -->
    <section class="py-16 bg-white" id="introduction">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-7 space-y-6">
                    <span class="text-xs text-univ-600 font-bold uppercase tracking-widest bg-univ-50 px-3 py-1 rounded-full">Overview</span>
                    <h3 class="text-3xl font-bold text-univ-900 tracking-tight">Hostel Infrastructure Profile</h3>
                    <p class="text-slate-600 leading-relaxed font-light text-sm">
                        Established to bridge modern residential utility with scholarly life, our university living quarters accommodate diverse student cohorts within an organized layout. Built upon principles of wellness, equity, and computational system access, we guarantee high institutional service metrics.
                    </p>
                    <p class="text-slate-600 leading-relaxed font-light text-sm">
                        Our integrated central dining halls manage resource logs efficiently, utilizing smart automated workflows to balance ingredient usage metrics, control clean serving conditions, and scale allocations perfectly.
                    </p>
                </div>
                <!-- HOSTEL IMAGE CONTAINER -->
                <div class="lg:col-span-5 h-64 rounded-2xl border border-slate-200 overflow-hidden shadow-md bg-slate-100">
                    <img src="images/hostel_building.jpeg" alt="Hostel Infrastructure" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </section>

    <!-- 3. MISSION, VISION & OBJECTIVES -->
    <section class="py-16 bg-slate-50 border-y border-slate-200" id="mission-vision">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Mission -->
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="w-12 h-12 bg-univ-50 text-univ-900 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-bullseye"></i></div>
                    <h4 class="font-bold text-lg text-univ-900">Our Mission</h4>
                    <p class="text-xs text-slate-500 leading-relaxed font-light">To supply balanced nutritional frameworks consistently while enabling effortless automated adjustments to foster absolute campus wellness parameters.</p>
                </div>
                <!-- Vision -->
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-eye"></i></div>
                    <h4 class="font-bold text-lg text-univ-900">Our Vision</h4>
                    <p class="text-xs text-slate-500 leading-relaxed font-light">To lead university residential living configurations by optimizing data management patterns, maximizing operational sustainability, and eliminating resource waste parameters entirely.</p>
                </div>
                <!-- Objectives -->
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl"><i class="fa-solid fa-list-check"></i></div>
                    <h4 class="font-bold text-lg text-univ-900">Core Objectives</h4>
                    <ul class="text-xs text-slate-500 space-y-2 font-light">
                        <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i> Maintain optimal hygienic standards.</li>
                        <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i> Deliver full programmatic menu transparency.</li>
                        <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i> Eliminate paper billing processing lines.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. HOSTEL WARDEN MESSAGE -->
    <section class="py-16 bg-white" id="warden">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 bg-slate-900 text-white rounded-3xl overflow-hidden relative shadow-xl">
            <div class="absolute inset-0 hero-pattern opacity-5"></div>
            <div class="p-8 md:p-12 grid md:grid-cols-12 gap-8 items-center relative z-10">
                <div class="md:col-span-4 text-center space-y-3">
                    <!-- CHIEF WARDEN IMAGE CONTAINER -->
                    <div class="w-32 h-32 rounded-full mx-auto border-4 border-slate-800 overflow-hidden shadow-inner bg-slate-700">
                        <img src="images/chief_warden.jpeg" alt="Sanjay Kumar Dehury" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h5 class="font-bold text-sm">Prof. Sanjay Kumar Dehury</h5>
                        <p class="text-[10px] text-gold-500 font-medium uppercase tracking-wider">Chief Warden</p>
                    </div>
                </div>
                <div class="md:col-span-8 space-y-4">
                    <i class="fa-solid fa-quote-left text-4xl text-white/10 block"></i>
                    <p class="text-xs md:text-sm text-slate-300 italic leading-relaxed font-light">
                        "Enabling streamlined digital tracking protocols allows our team to focus directly on absolute raw ingredient metrics, dynamic sanitation checking loops, and student service feedback loops. We welcome all residents to engage responsibly with this digital infrastructure to establish an transparent environment."
                    </p>
                    <div class="h-px bg-white/10 w-24"></div>
                    <span class="block text-[10px] text-slate-400 font-mono tracking-wider">OFFICE OF RESIDENTIAL WELFARE</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. FACILITIES SECTION -->
    <section class="py-16 bg-slate-50 border-t border-slate-200" id="facilities">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12 space-y-2">
                <h3 class="text-2xl font-bold text-univ-900 tracking-tight">Available Campus Facilities</h3>
                <p class="text-slate-500 font-light text-xs">Premium logistical services formulated to make your residency optimized, comfortable, and healthy.</p>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="text-univ-900 text-xl bg-univ-50 w-10 h-10 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-wind"></i></div>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Climatized Halls</h5>
                        <p class="text-[11px] text-slate-400">Optimal ventilation loops.</p>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="text-emerald-600 text-xl bg-emerald-50 w-10 h-10 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-filter"></i></div>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">RO Water Plants</h5>
                        <p class="text-[11px] text-slate-400">Continuous purification filters.</p>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="text-amber-600 text-xl bg-amber-50 w-10 h-10 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-wifi"></i></div>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Gigabit WiFi Coverage</h5>
                        <p class="text-[11px] text-slate-400">High bandwidth connectivity.</p>
                    </div>
                </div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="text-purple-600 text-xl bg-purple-50 w-10 h-10 rounded-lg flex items-center justify-center shrink-0"><i class="fa-solid fa-kit-medical"></i></div>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Medical Center</h5>
                        <p class="text-[11px] text-slate-400">24/7 emergency response squads.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. HOSTEL RULES -->
    <section class="py-16 bg-white border-t border-slate-200" id="rules">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 space-y-2">
                <h3 class="text-2xl font-bold text-univ-900 tracking-tight">Regulatory Rules & Guidelines</h3>
                <p class="text-slate-500 font-light text-xs">Adherence to all protocol guidelines guarantees standard compliance and continuous security conditions.</p>
            </div>
            
            <div class="bg-slate-50 rounded-2xl border border-slate-200 divide-y divide-slate-200 text-xs">
                <div class="p-4 flex gap-4 items-start">
                    <span class="bg-univ-900 text-white w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0">01</span>
                    <p class="text-slate-600 font-light leading-relaxed"><strong class="text-univ-900 font-semibold">Timetable Compliance:</strong> Food queue scanning terminals shut down exactly at designated window bounds. Late arrivals will not be computed.</p>
                </div>
                <div class="p-4 flex gap-4 items-start">
                    <span class="bg-univ-900 text-white w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0">02</span>
                    <p class="text-slate-600 font-light leading-relaxed"><strong class="text-univ-900 font-semibold">Token Security:</strong> Generating entry QR codes requires matching logged credentials. Distribution of screenshot tokens across peers is strictly prohibited.</p>
                </div>
                <div class="p-4 flex gap-4 items-start">
                    <span class="bg-univ-900 text-white w-6 h-6 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0">03</span>
                    <p class="text-slate-600 font-light leading-relaxed"><strong class="text-univ-900 font-semibold">Leave Policies:</strong> Formal leave reports tracking structural credit rollbacks must be logged minimum 24 hours prior via dashboard panels.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. TIMELINE HISTORY -->
    <section class="py-16 bg-slate-50 border-t border-slate-200" id="timeline">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 space-y-2">
                <h3 class="text-2xl font-bold text-univ-900 tracking-tight">Institutional Evolution Timeline</h3>
                <p class="text-slate-500 font-light text-xs">Tracing the structural steps that shaped our dynamic digital infrastructure ecosystem.</p>
            </div>

            <div class="relative border-l-2 border-slate-200 ml-4 space-y-8">
                <!-- Node 1 -->
                <div class="relative pl-6">
                    <div class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-univ-900 border-2 border-white"></div>
                    <span class="text-[10px] font-bold text-univ-600 uppercase tracking-wide">2016 &mdash; Initial Inception</span>
                    <h5 class="font-bold text-xs text-slate-900 mt-0.5">Foundational Dining Facility Setup</h5>
                    <p class="text-slate-500 text-[11px] mt-1 font-light leading-relaxed">Centralized dining facilities structurally established, establishing regular standard meal logistics configurations.</p>
                </div>
                <!-- Node 2 -->
                <div class="relative pl-6">
                    <div class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></div>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">2021 &mdash; System Automation</span>
                    <h5 class="font-bold text-xs text-slate-900 mt-0.5">Computational Infrastructure Integration</h5>
                    <p class="text-slate-500 text-[11px] mt-1 font-light leading-relaxed">Introduction of traditional handwritten ledger logs .</p>
                </div>
                <!-- Node 3 -->
                <div class="relative pl-6">
                    <div class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-gold-500 border-2 border-white"></div>
                    <span class="text-[10px] font-bold text-gold-600 uppercase tracking-wide">2026 &mdash; Cloud Scaling</span>
                    <h5 class="font-bold text-xs text-slate-900 mt-0.5">Complete Smart Mess Ecosystem V3.0</h5>
                    <p class="text-slate-500 text-[11px] mt-1 font-light leading-relaxed">Deployment of absolute multi-role security configurations, data dashboards, and real-time leave budget calculators.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. FOOTER -->
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