<?php

/* Start output buffering */
ob_start();

session_start();

/* =========================
   UNSET ALL SESSION DATA
========================= */

// Clear session array safely
$_SESSION = [];

/* =========================
   DESTROY SESSION COOKIE
========================= */

if (ini_get("session.use_cookies")) {

$params = session_get_cookie_params();

setcookie(

session_name(),

'',

time() - 42000,

$params["path"],

$params["domain"],

$params["secure"],

$params["httponly"]

);

}

/* =========================
   DESTROY SESSION
========================= */

session_destroy();

/* =========================
   SECURITY HEADERS
========================= */

// Prevent cache
header(
"Cache-Control:
no-store,
no-cache,
must-revalidate,
max-age=0"
);

header(
"Cache-Control:
post-check=0,
pre-check=0",
false
);

header("Pragma: no-cache");

header("Expires: 0");

/* Extra Security */

header("X-Frame-Options: SAMEORIGIN");

header("X-Content-Type-Options: nosniff");

header("X-XSS-Protection: 1; mode=block");

/* =========================
   REDIRECT TO LOGIN
========================= */

header("Location: admin_login.php");

exit();
?>