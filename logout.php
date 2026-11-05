<?php
session_start();

// ==========================================
// 1. DEREGISTER FROM ACTIVE SESSIONS REGISTRY
// ==========================================
$userEmail = $_SESSION['Email'] ?? $_SESSION['Email1'] ?? $_SESSION['Email2'] ?? null;

if ($userEmail) {
    $registryFile = __DIR__ . '/active_sessions.json';
    if (file_exists($registryFile)) {
        $content = file_get_contents($registryFile);
        $sessions = json_decode($content, true) ?: [];

        if (isset($sessions[$userEmail])) {
            unset($sessions[$userEmail]);
            file_put_contents($registryFile, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
        }
    }
}

// ==========================================
// 2. UNSET & DESTROY SESSION
// ==========================================
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