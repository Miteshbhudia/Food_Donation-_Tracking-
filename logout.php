<?php
session_start();

// Unset all session variables across all roles
$_SESSION = [];

// Invalidate the session cookie
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

// Terminate the server session
session_destroy();

// Redirect back to the unified authentication page
header("Location: login_page.html?logout=success");
exit();
?>