<?php

/**
 * 🔒 Professional Admin Session Termination Gate
 * Handles secure session data removal, cookie cleanup, 
 * browser cache invalidation, and secure login redirection.
 */

// Start output buffering to prevent header errors
if (ob_get_level() === 0) {
   ob_start();
}

// Safely initialize session context if it does not actively exist
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

/* ==========================================================================
   1. CLEAR SESSİON DATA FROM MEMORY
   ========================================================================== */
$_SESSION = [];

/* ==========================================================================
   2. EXPIRE AND REMOVE SESSION COOKIE FROM BROWSER
   ========================================================================== */
if (ini_get("session.use_cookies")) {
   $params = session_get_cookie_params();
   setcookie(
      session_name(),
      '',
      time() - 42000, // Forces immediate client-side deletion
      $params["path"],
      $params["domain"],
      $params["secure"] ?? false,
      $params["httponly"] ?? true
   );
}

/* ==========================================================================
   3. DESTROY THE ACTIVE SESSION INSTANCE
   ========================================================================== */
if (session_status() === PHP_SESSION_ACTIVE) {
   session_destroy();
}

/* ==========================================================================
   4. BROWSER SECURITY HEADERS & CACHE REMOVAL
   ========================================================================== */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Old date to guarantee immediate expiration

// Core Web Security Enhancements
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

/* ==========================================================================
   5. REDIRECT WITH STATUS PARAMETER
   ========================================================================== */
// The '?logout=success' flag allows the login form to detect the state 
// and elegantly show a "You have successfully logged out" notification banner.
header("Location: login.php?logout=success");
exit();