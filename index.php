<?php

/**
 * 🌐 Hostel Mess Management System - Main Entry Point
 * Features:
 * - Session Hijacking Prevention (Secure configuration)
 * - Security Headers (X-Content-Type, X-Frame-Options)
 * - Smart Session Routing based on user role
 */

// 1. STANDARD SECURITY HEADERS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// 2. SECURE SESSION INITIALIZATION
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // Automatically activates if SSL is live
        'use_strict_mode' => true,
    ]);
}

// 3. SMART DASHBOARD ROUTING MATCH
if (isset($_SESSION['admin'])) {
    header("Location: admin/dashboard.php");
    exit();
} elseif (isset($_SESSION['hostel_roll'])) {
    header("Location: student/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hostel Mess Campus Dining Management Portal">

    <title>Hostel Mess Portal | Welcome</title>

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

        .smooth-hover {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .smooth-hover:hover {
            transform: translateY(-6px);
        }

        .hero-pattern {
            background-image: radial-gradient(rgba(30, 58, 138, 0.1) 1px, transparent 0);
            background-size: 24px 24px;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col antialiased text-slate-800 bg-slate-50 selection:bg-univ-100">

    <!-- 1. STICKY NAVBAR -->
    <header class="sticky top-0 z-50 w-full glass-nav border-b border-slate-200/80 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
            <a href="#" class="flex items-center gap-3 group">
                <div class="bg-univ-900 text-white w-11 h-11 flex items-center justify-center rounded-xl shadow-md transition-transform group-hover:scale-105">
                    <i class="fa-solid fa-utensils text-xl"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold tracking-tight text-univ-900 leading-none">Ravenshaw University</h1>
                    <span class="text-[10px] text-gold-600 font-bold uppercase tracking-[0.15em]">Smart Mess System</span>
                </div>
            </a>

            <!-- Navigation Menu Links -->
            <nav class="hidden lg:flex items-center gap-6 text-xs font-bold uppercase tracking-wider text-slate-600">
                <a href="index.php" class="text-univ-900 transition-colors">Home</a>
                <a href="about.php" class="hover:text-univ-900 transition-colors">About Us</a>
                <a href="#features" class="hover:text-univ-900 transition-colors">Features</a>
                <a href="#stats" class="hover:text-univ-900 transition-colors">Stats</a>
                <a href="#gallery" class="hover:text-univ-900 transition-colors">Gallery</a>
                <a href="#announcements" class="hover:text-univ-900 transition-colors">Notices</a>
                <a href="#testimonials" class="hover:text-univ-900 transition-colors">Reviews</a>
                <a href="contact.php" class="hover:text-univ-900 transition-colors">Contact</a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-2 text-[10px] font-bold text-slate-500 bg-white px-3.5 py-1.5 rounded-full border border-slate-200 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    SYSTEM LIVE v3.0
                </div>
                <a href="#portals" class="bg-univ-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md hover:bg-univ-800 transition-all uppercase tracking-wider">
                    Access Portals
                </a>
                <button id="mobile-menu-btn" class="lg:hidden text-slate-700 hover:text-univ-900 text-xl focus:outline-none" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown Section -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-6 space-y-2 shadow-inner">
            <a href="index.php" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Home</a>
            <a href="about.php" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">About Us</a>
            <a href="#features" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Features</a>
            <a href="#stats" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Stats</a>
            <a href="#gallery" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Gallery</a>
            <a href="#announcements" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Notices</a>
            <a href="#testimonials" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Reviews</a>
            <a href="contact.php" class="block py-2.5 px-4 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100">Contact</a>
        </div>
    </header>

    <!-- 2. HERO BANNER & MAIN DUAL PORTALS -->
    <section class="relative bg-gradient-to-b from-univ-900 to-slate-900 text-white overflow-hidden py-20 lg:py-28" id="portals">
        <div class="absolute inset-0 hero-pattern opacity-10"></div>
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-univ-700 rounded-full blur-3xl opacity-30"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gold-600 rounded-full blur-3xl opacity-20"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-12 items-center">

                <div class="lg:col-span-5 text-center lg:text-left space-y-6">
                    <span class="inline-flex items-center gap-2 bg-white/10 text-gold-500 font-semibold px-4 py-1.5 rounded-full text-xs uppercase tracking-wider backdrop-blur-sm">
                        <i class="fa-solid fa-graduation-cap"></i> University Girl's hostel mess Portal
                    </span>
                    <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                        Hostel Mess Portal <br class="hidden lg:inline"><span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">Mahanadi Hostel</span>
                    </h2>
                    <p class="text-slate-300 text-base md:text-lg max-w-xl mx-auto lg:mx-0 font-light leading-relaxed">
                        Welcome to your hostel food portal. View daily menus, manage meal leaves, and check your mess account easily.
                    </p>
                    <div class="pt-4 flex flex-wrap justify-center lg:justify-start gap-4">
                        <a href="about.php" class="bg-white text-univ-900 px-6 py-3 rounded-xl font-bold text-sm shadow-md hover:bg-slate-100 transition-all">About Us</a>
                        <a href="contact.php" class="border border-white/30 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-white/10 transition-all">Contact Support</a>
                    </div>
                </div>

                <div class="lg:col-span-7 grid md:grid-cols-2 gap-6 w-full max-w-3xl mx-auto">
                    <!-- Student Card -->
                    <article class="smooth-hover bg-white p-8 rounded-3xl border border-slate-200/50 shadow-2xl text-slate-800 group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-bl-full transition-all group-hover:scale-110"></div>
                        <div class="bg-emerald-50 text-emerald-600 w-14 h-14 flex items-center justify-center rounded-2xl mb-6 text-2xl group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 shadow-sm">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <h3 class="text-xl font-bold text-univ-900 mb-2">Student Dashboard</h3>
                        <p class="text-slate-500 text-sm mb-8 leading-relaxed">
                            Check daily food menus, submit leave applications, and view your mess attendance history instantly.
                        </p>
                        <a href="auth/login.php" class="block w-full bg-slate-900 text-white text-center font-semibold py-3.5 rounded-xl hover:bg-emerald-600 transition-colors shadow-sm text-sm tracking-wide">
                            Open Student Portal <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </article>

                    <!-- Admin Card -->
                    <article class="smooth-hover bg-white p-8 rounded-3xl border border-slate-200/50 shadow-2xl text-slate-800 group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-univ-600/5 rounded-bl-full transition-all group-hover:scale-110"></div>
                        <div class="bg-univ-50 text-univ-600 w-14 h-14 flex items-center justify-center rounded-2xl mb-6 text-2xl group-hover:bg-univ-600 group-hover:text-white transition-all duration-300 shadow-sm">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h3 class="text-xl font-bold text-univ-900 mb-2">Admin Console</h3>
                        <p class="text-slate-500 text-sm mb-8 leading-relaxed">
                            Manage student rosters, update weekly food schedules, track overall attendance, and generate reports.
                        </p>
                        <a href="admin/login.php" class="block w-full bg-slate-900 text-white text-center font-semibold py-3.5 rounded-xl hover:bg-univ-600 transition-colors shadow-sm text-sm tracking-wide">
                            Open Management Panel <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                        </a>
                    </article>
                </div>

            </div>
        </div>
    </section>

    <!-- 3. HOSTEL INTRODUCTION -->
    <section class="py-20 bg-white" id="about-preview">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-72 h-72 bg-univ-100 rounded-3xl -z-10 opacity-60"></div>
                    <div class="rounded-3xl overflow-hidden shadow-lg h-96 border border-slate-100 relative">
                        <img src="images/campus_living.webp" alt="RU Campus Living" class="w-full h-full object-cover">
                        <span class="absolute bottom-4 left-4 bg-univ-900 text-white text-xs px-3 py-1.5 font-semibold rounded-lg">Ravenshaw University,Cuttack</span>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="w-12 h-1 bg-gold-600 rounded"></div>
                    <h3 class="text-3xl font-bold text-univ-900 tracking-tight">RU Campus Living</h3>
                    <p class="text-slate-600 leading-relaxed font-light">
                        Our university hostel is proud to provide premium living spaces integrated with digital tools. We aim to ensure exceptional living standards, inclusive values, and a fully balanced nutritional environment for all students.
                    </p>
                    <p class="text-slate-600 leading-relaxed font-light">
                        The fully automated mess portal manages daily food operations, ensuring cleanliness, zero waste, and high-quality meals.
                    </p>
                    <div class="pt-2">
                        <a href="about.php" class="text-univ-700 font-bold text-sm inline-flex items-center gap-2 hover:gap-3 transition-all">
                            Read More About Us <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. FEATURES SECTION -->
    <section class="py-20 bg-slate-50 border-y border-slate-200" id="features">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs text-univ-600 font-bold uppercase tracking-widest bg-univ-50 px-3 py-1 rounded-full">Features</span>
                <h3 class="text-3xl font-bold text-univ-900 tracking-tight">System Features & Tools</h3>
                <p class="text-slate-500 font-light">Designed to eliminate long waiting lines, reduce food waste, and improve your daily campus dining experience.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-univ-50 text-univ-900 flex items-center justify-center text-lg font-bold"><i class="fa-solid fa-calendar-week"></i></div>
                    <h4 class="font-bold text-base text-univ-900">Dynamic Menu</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Instantly view weekly meal plans, ingredient details, and daily changes updated live by managers.</p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold"><i class="fa-solid fa-qrcode"></i></div>
                    <h4 class="font-bold text-base text-univ-900">QR Token System</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Access secure digital QR passes directly on your phone to completely replace paper log sheets.</p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold"><i class="fa-solid fa-plane-departure"></i></div>
                    <h4 class="font-bold text-base text-univ-900">Easy Leave System</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Going out of town? Submit easy meal leaves to stop your food orders and adjust your balance dynamically.</p>
                </div>
                <!-- Feature 4 -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all space-y-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold"><i class="fa-solid fa-chart-pie"></i></div>
                    <h4 class="font-bold text-base text-univ-900">Analytics & Reports</h4>
                    <p class="text-xs text-slate-500 leading-relaxed">Provides clear dashboards for management to monitor food consumption, user reviews, and accounts.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. HOSTEL STATISTICS -->
    <section class="py-16 bg-univ-900 text-white relative overflow-hidden" id="stats">
        <div class="absolute inset-0 hero-pattern opacity-5"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-extrabold text-gold-500">700+</div>
                    <div class="text-xs font-medium tracking-wide text-slate-300 uppercase">Active Residents</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-extrabold text-emerald-400">1,800+</div>
                    <div class="text-xs font-medium tracking-wide text-slate-300 uppercase">Daily Servings</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-extrabold text-blue-400">99.8%</div>
                    <div class="text-xs font-medium tracking-wide text-slate-300 uppercase">System Uptime</div>
                </div>
                <div class="space-y-2">
                    <div class="text-3xl md:text-4xl font-extrabold text-purple-400">Zero</div>
                    <div class="text-xs font-medium tracking-wide text-slate-300 uppercase">Paper Waste</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. GALLERY PREVIEW -->
    <section class="py-20 bg-white" id="gallery">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs text-univ-600 font-bold uppercase tracking-widest bg-univ-50 px-3 py-1 rounded-full">Gallery</span>
                <h3 class="text-3xl font-bold text-univ-900 tracking-tight">Gallery Preview</h3>
                <p class="text-slate-500 font-light">A brief look at our dining hall layouts and modern resident facilities.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Gallery Item 1: Hostel Building -->
                <div class="rounded-2xl h-64 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <img src="images/hostel_building.jpeg" alt="Mahanadi Hostel Building" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent absolute inset-0 p-6 flex flex-col justify-end text-white">
                        <h5 class="font-bold text-sm">Hostel Building</h5>
                        <p class="text-[11px] text-slate-300">Mahanadi Hostel infrastructure providing secure and modern accommodation.</p>
                    </div>
                </div>

                <!-- Gallery Item 2: Smart Kitchen -->
                <div class="rounded-2xl h-64 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <img src="images/smart_kitchen.jpeg" alt="Smart Kitchen" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent absolute inset-0 p-6 flex flex-col justify-end text-white">
                        <h5 class="font-bold text-sm">Smart Kitchen</h5>
                        <p class="text-[11px] text-slate-300">Modern and hygienic food preparation equipment.</p>
                    </div>
                </div>

                <!-- Gallery Item 3: Mess Dining Area -->
                <div class="rounded-2xl h-64 border border-slate-200 shadow-sm relative overflow-hidden group">
                    <img src="images/dining_area.jpeg" alt="Mess Dining Area" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent absolute inset-0 p-6 flex flex-col justify-end text-white">
                        <h5 class="font-bold text-sm">Mess Dining Area</h5>
                        <p class="text-[11px] text-slate-300">Spacious dining setup equipped with clean, premium table and chair arrangements.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. LATEST ANNOUNCEMENTS -->
    <section class="py-20 bg-slate-50 border-t border-slate-200" id="announcements">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-12">
                <div class="space-y-4">
                    <span class="text-xs text-gold-600 font-bold uppercase tracking-wider">Updates</span>
                    <h3 class="text-3xl font-bold text-univ-900 tracking-tight">Latest Announcements</h3>
                    <p class="text-slate-500 font-light text-sm leading-relaxed">Stay updated with current notices, festive schedule changes, and important messages directly from the hostel warden.</p>
                </div>
                <div class="lg:col-span-2 space-y-4">
                    <!-- Notice 1 -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4">
                        <div class="bg-univ-50 text-univ-900 px-3 py-2 rounded-lg text-center min-w-[64px]">
                            <span class="block text-sm font-bold">12</span>
                            <span class="text-[10px] font-semibold uppercase text-slate-500">Jul</span>
                        </div>
                        <div>
                            <span class="inline-block bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded mb-1">Menu Update</span>
                            <h4 class="font-bold text-sm text-univ-900">Holiday Menu Announced</h4>
                            <p class="text-xs text-slate-500 mt-1">Special traditional meals will be served during the upcoming university holiday breaks.</p>
                        </div>
                    </div>
                    <!-- Notice 2 -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-start gap-4">
                        <div class="bg-emerald-50 text-emerald-600 px-3 py-2 rounded-lg text-center min-w-[64px]">
                            <span class="block text-sm font-bold">08</span>
                            <span class="text-[10px] font-semibold uppercase text-slate-500">Jul</span>
                        </div>
                        <div>
                            <span class="inline-block bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded mb-1">System Notice</span>
                            <h4 class="font-bold text-sm text-univ-900">Automatic Leave Credits Balance Live</h4>
                            <p class="text-xs text-slate-500 mt-1">The automated calculation algorithms now apply to active meal leaves lasting longer than 48 hours.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. TESTIMONIALS SECTION -->
    <section class="py-20 bg-white border-t border-slate-200" id="testimonials">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
                <span class="text-xs text-univ-600 font-bold uppercase tracking-widest bg-univ-50 px-3 py-1 rounded-full">Reviews</span>
                <h3 class="text-3xl font-bold text-univ-900 tracking-tight">Student Feedback</h3>
                <p class="text-slate-500 font-light">See how our automated system makes campus dining easy for residents.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4 relative">
                    <i class="fa-solid fa-quote-left text-3xl text-univ-100 absolute top-6 right-6"></i>
                    <div class="text-amber-500 text-xs"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    <p class="text-xs text-slate-600 italic leading-relaxed">"Applying for leaves used to mean printing paper forms and waiting for approvals. Now I can do it right from my phone in a few clicks."</p>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Sonali Dehury</h5>
                        <span class="text-[10px] text-slate-400 font-medium">B.Sc Computer Science, Student</span>
                    </div>
                </div>
                <!-- Testimonial 2 -->
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4 relative">
                    <i class="fa-solid fa-quote-left text-3xl text-univ-100 absolute top-6 right-6"></i>
                    <div class="text-amber-500 text-xs"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    <p class="text-xs text-slate-600 italic leading-relaxed">"The QR entry scanner works smoothly. I just show my mobile token pass and walk straight to the food counters."</p>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Ratna Manjari Tripathy</h5>
                        <span class="text-[10px] text-slate-400 font-medium">B.Sc Chemistry, Student</span>
                    </div>
                </div>
                <!-- Testimonial 3 -->
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4 relative">
                    <i class="fa-solid fa-quote-left text-3xl text-univ-100 absolute top-6 right-6"></i>
                    <div class="text-amber-500 text-xs"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                    <p class="text-xs text-slate-600 italic leading-relaxed">"Updating weekly menus or tracking daily attendance records across large batches of residents has become hassle-free."</p>
                    <div>
                        <h5 class="font-bold text-xs text-univ-900">Dr. Rashmi rani Sahu</h5>
                        <span class="text-[10px] text-slate-400 font-medium">Chief Hostel Warden</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. CONTACT PREVIEW -->
    <section class="py-20 bg-slate-50 border-t border-slate-200" id="contact-preview">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5 space-y-4">
                    <span class="text-xs text-univ-600 font-bold uppercase tracking-wider">Help Desk</span>
                    <h3 class="text-3xl font-bold text-univ-900 tracking-tight">Contact the Mess Council</h3>
                    <p class="text-slate-500 font-light text-sm leading-relaxed">Facing problem with your login credentials, balances, or want to share menu suggestions? Reach out to our team.</p>
                    <div class="space-y-3 pt-2 text-xs text-slate-600">
                        <div class="flex items-center gap-3"><i class="fa-solid fa-envelope text-univ-600 w-4"></i> mahanadi_hostel@university.edu</div>
                        <div class="flex items-center gap-3"><i class="fa-solid fa-phone text-univ-600 w-4"></i> +91 9832657724</div>
                        <div class="flex items-center gap-3"><i class="fa-solid fa-location-dot text-univ-600 w-4"></i> Admin Wing, Central Dining Blocks</div>
                    </div>
                    <div class="pt-2">
                        <a href="contact.php" class="text-univ-700 font-bold text-sm inline-flex items-center gap-2 hover:gap-3 transition-all">
                            Open Contact Page <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                    <?php
                    // INLINE DATABASE CONTROLLER FOR FORM ROUTING
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        require_once __DIR__ . "/config/db_connect.php";

                        // Fallback mechanism to scan matching active connection instances
                        if (!isset($conn)) {
                            $conn = $pdo ?? $db ?? $con ?? null;
                        }

                        if (!$conn) {
                            echo '<div class="mb-6 p-4 text-xs font-bold bg-red-50 text-red-700 border border-red-200 rounded-xl">❌ Configuration Error: Database connection engine instance unavailable.</div>';
                        } else {
                            $form_name = isset($_POST['ticket_name']) ? trim($_POST['ticket_name']) : '';
                            $form_roll = isset($_POST['ticket_roll']) ? trim($_POST['ticket_roll']) : '';
                            $form_msg  = isset($_POST['ticket_message']) ? trim($_POST['ticket_message']) : '';

                            if (empty($form_name) || empty($form_roll) || empty($form_msg)) {
                                echo '<div class="mb-6 p-4 text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 rounded-xl">⚠️ Input Notice: Please fill out all required fields before dispatching tickets.</div>';
                            } else {
                                try {
                                    $ins_stmt = $conn->prepare("INSERT INTO contact_tickets (name, hostel_roll, message) VALUES (:name, :roll, :msg)");
                                    $ins_stmt->execute([
                                        ':name' => $form_name,
                                        ':roll' => $form_roll,
                                        ':msg'  => $form_msg
                                    ]);
                                    echo '<div class="mb-6 p-4 text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl">✅ Success! Your help desk inquiry has been filed. The admin council will review your record soon.</div>';
                                } catch (PDOException $ex) {
                                    error_log("Ticket Save Exception Raised: " . $ex->getMessage());
                                    echo '<div class="mb-6 p-4 text-xs font-bold bg-red-50 text-red-700 border border-red-200 rounded-xl">❌ System Error: Ticket could not be securely saved. Please try again later.</div>';
                                }
                            }
                        }
                    }
                    ?>

                    <form action="#contact-preview" method="POST" class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-slate-500 uppercase">Your Name</label>
                            <input type="text" name="ticket_name" placeholder="Sonali Dehury" class="w-full text-xs px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-univ-600/20 transition-all" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-slate-500 uppercase">University Roll No</label>
                            <input type="text" name="ticket_roll" placeholder="24DCS042" class="w-full text-xs px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-univ-600/20 transition-all" required>
                        </div>
                        <div class="sm:col-span-2 space-y-1">
                            <label class="text-[11px] font-bold text-slate-500 uppercase">Message</label>
                            <textarea rows="3" name="ticket_message" placeholder="Write your query or feedback here..." class="w-full text-xs px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-univ-600/20 transition-all" required></textarea>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" class="w-full bg-univ-900 text-white text-xs font-bold py-3.5 rounded-lg shadow hover:bg-univ-800 transition-colors uppercase tracking-wider">Send Message Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>>

    <!-- 10. FOOTER -->
    <footer class="w-full bg-slate-900 text-slate-400 text-xs border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-white">
                    <div class="bg-white/10 text-white w-8 h-8 flex items-center justify-center rounded-lg text-sm"><i class="fa-solid fa-utensils"></i></div>
                    <span class="font-bold tracking-tight text-sm">MAHANADI HOSTEL</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed font-light">
                    Optimizing student hostel dining operations with secure, fast, and simple digital tools.
                </p>
            </div>
            <div>
                <h5 class="text-white font-bold text-xs uppercase tracking-wider mb-4">Portal Entries</h5>
                <ul class="space-y-2 text-[11px]">
                    <li><a href="auth/login.php" class="hover:text-white transition-colors">Student Login Gate</a></li>
                    <li><a href="admin/login.php" class="hover:text-white transition-colors">Admin Console Access</a></li>
                    <li><a href="about.php" class="hover:text-white transition-colors">Core Policies</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-white font-bold text-xs uppercase tracking-wider mb-4">Quick Links</h5>
                <ul class="space-y-2 text-[11px]">
                    <li><a href="about.php" class="hover:text-white transition-colors">About Us</a></li>
                    <li><a href="contact.php" class="hover:text-white transition-colors">Contact Support</a></li>
                    <li><a href="#features" class="hover:text-white transition-colors">System Features</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-white font-bold text-xs uppercase tracking-wider mb-4">System Context</h5>
                <p class="text-[11px] text-slate-400 leading-relaxed font-light mb-2">Encryption secure HTTP headers active. Clean layout version 3.0.</p>
                <div class="text-emerald-400 font-bold text-[10px] uppercase tracking-widest"><i class="fa-solid fa-circle text-[8px] mr-1.5 animate-pulse"></i> Servers Online</div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 border-t border-slate-800 text-center text-[11px] font-bold text-slate-500 uppercase tracking-widest">
            &copy; 2026 Hostel Mess Management System. All Rights Reserved.
        </div>
    </footer>

    <!-- Interactive JS Functionality for Mobile Menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            if (menuBtn && mobileMenu) {
                menuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                    const icon = menuBtn.querySelector('i');
                    if (mobileMenu.classList.contains('hidden')) {
                        icon.className = 'fa-solid fa-bars';
                    } else {
                        icon.className = 'fa-solid fa-xmark';
                    }
                });

                // Auto-closing mobile menu on link click
                const links = mobileMenu.querySelectorAll('a');
                links.forEach(link => {
                    link.addEventListener('click', function() {
                        mobileMenu.classList.add('hidden');
                        menuBtn.querySelector('i').className = 'fa-solid fa-bars';
                    });
                });
            }
        });
    </script>
</body>

</html>