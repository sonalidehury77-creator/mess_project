<?php
/**
 * 🛡️ Enterprise Administrative Global Layout Header Shield
 * Controls secure session contexts, enforces authorization checks, and configures modern interface resources.
 */

// Initialize runtime secure session state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Map Global Database Connections & Utility Configurations
require_once __DIR__ . '/../config/db_connect.php';

// 2. Identity & Access Guard (RBAC - Role Based Access Control)
// If the admin context isn't securely stored within active session arrays, bounce traffic instantly back to login
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    // Terminate compromised tracking elements
    session_unset();
    session_destroy();
    
    // Dispatch instant redirection protocol back to administrative gateway entry
    header("Location: login.php");
    exit();
}

// 3. Prevent Session Hijacking (Re-verify browser profile fingerprint metadata)
if (!isset($_SESSION['admin_user_agent'])) {
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} elseif ($_SESSION['admin_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: login.php?err=session_compromised");
    exit();
}

// 4. HTTP Defensive Security Hardening Headers
header("X-Frame-Options: DENY");                           // Complete Clickjacking Protection Vector Block
header("X-Content-Type-Options: nosniff");                 // Direct browser MIME-type sniffing suppression
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Mess Management | Admin Core Console</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/tables.css">
    <link rel="stylesheet" href="../css/responsive.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        enterprise: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; -webkit-font-smoothing: antialiased; }
        /* High-Performance Webkit Custom Grid Scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F5F9; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 8px; transition: background 0.2s ease; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="h-full overflow-x-hidden text-slate-900 selection:bg-blue-500 selection:text-white">
    
    <div id="system-loading-bar" class="fixed top-0 left-0 right-0 h-1 bg-blue-600 z-50 transition-all duration-500 w-0 opacity-100"></div>

    <div class="flex min-h-screen">