<?php
session_start();
require_once 'dbconnection.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_page.html");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password =$_POST['password'] ?? '';

if (empty($email) or empty($password)) {
    header("Location: login_page.html?error=empty");
    exit();
}

// =========================================================================
// SINGLE ACTIVE SESSION REGISTRY HELPER (No Database Alterations Required)
// =========================================================================
function registerActiveSession($userEmail,$userRole) {
    $registryFile = __DIR__ . '/active_sessions.json';$sessions = [];

    if (file_exists($registryFile)) {
        $content = file_get_contents($registryFile);
        $sessions = json_decode($content, true) ?: [];
    }

    // Generate a cryptographically secure 64-character token
    $token = bin2hex(random_bytes(32));
    
    // Bind token to user email and role
    $sessions[$userEmail] = [
        'token'     => $token,
        'role'      => $userRole,
        'last_seen' => time()
    ];

    file_put_contents($registryFile, json_encode($sessions, JSON_PRETTY_PRINT), LOCK_EX);
    return $token;
}

// =========================================================================
// 1. CHECK ADMINISTRATOR DIRECTORY (System Admin & Area Admin)
// =========================================================================
$stmt_admin =$conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location`, `Password` FROM `admin` WHERE `Email_Address` = ?");
$stmt_admin->bind_param("s", $email);$stmt_admin->execute();
$res_admin =$stmt_admin->get_result();

if ($row_admin = $res_admin->fetch_assoc()) {$db_pass = $row_admin['Password'];$is_valid = false;

    // Verify modern bcrypt hash or fallback to legacy plaintext
    if (password_verify($password, $db_pass) or$password === $db_pass) {$is_valid = true;

        // Automatically upgrade legacy plaintext to bcrypt
        if ($password ===$db_pass) {
            $new_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt_upd =$conn->prepare("UPDATE `admin` SET `Password` = ? WHERE `Administrator_ID` = ?");
            $stmt_upd->bind_param("si", $new_hash,$row_admin['Administrator_ID']);
            $stmt_upd->execute();$stmt_upd->close();
        }
    }

    if ($is_valid) {
        session_regenerate_id(true);

        // A. Route System Administrator
        if ($row_admin['Position'] === 'System Administrator') {
            $session_token = registerActiveSession($row_admin['Email_Address'], 'System Admin');

            $_SESSION['session_token'] =$session_token;
            $_SESSION['user_role']     = 'System Admin';$_SESSION['last_activity'] = time();
            $_SESSION['Email']         =$row_admin['Email_Address'];
            $_SESSION['adminname']     =$row_admin['Fullname'];
            $_SESSION['admin_id']      =$row_admin['Administrator_ID'];

            $stmt_admin->close();$conn->close();
            header("Location: system_admin.php");
            exit();
        }

        // B. Route Area Administrator
        if ($row_admin['Position'] === 'Area Administrator') {
            $session_token = registerActiveSession($row_admin['Email_Address'], 'Area Admin');

            $_SESSION['session_token']  =$session_token;
            $_SESSION['user_role']      = 'Area Admin';$_SESSION['last_activity']  = time();
            $_SESSION['Email']          =$row_admin['Email_Address'];
            $_SESSION['Email1']         =$row_admin['Email_Address'];
            $_SESSION['admin_id']       =$row_admin['Administrator_ID'];
            $_SESSION['admin_name']     =$row_admin['Fullname'];
            $_SESSION['admin_location'] =$row_admin['Location'];

            $stmt_admin->close();$conn->close();
            header("Location: index1.php");
            exit();
        }
    }
}
$stmt_admin->close();

// =========================================================================
// 2. CHECK DONOR DIRECTORY
// =========================================================================
$stmt_donor =$conn->prepare("SELECT `Donor_ID`, `Fullname`, `Email_Address`, `Password` FROM `donors` WHERE `Email_Address` = ?");
$stmt_donor->bind_param("s", $email);$stmt_donor->execute();
$res_donor =$stmt_donor->get_result();

if ($row_donor = $res_donor->fetch_assoc()) {$db_pass = $row_donor['Password'];$is_valid = false;

    // Verify modern bcrypt hash or fallback to legacy plaintext
    if (password_verify($password, $db_pass) or$password === $db_pass) {$is_valid = true;

        // Automatically upgrade legacy plaintext to bcrypt
        if ($password ===$db_pass) {
            $new_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt_upd =$conn->prepare("UPDATE `donors` SET `Password` = ? WHERE `Donor_ID` = ?");
            $stmt_upd->bind_param("si", $new_hash,$row_donor['Donor_ID']);
            $stmt_upd->execute();$stmt_upd->close();
        }
    }

    if ($is_valid) {
        session_regenerate_id(true);

        // Register single active session token
        $session_token = registerActiveSession($row_donor['Email_Address'], 'Donor');

        $_SESSION['session_token'] =$session_token;
        $_SESSION['user_role']     = 'Donor';$_SESSION['last_activity'] = time();
        $_SESSION['Email']         =$row_donor['Email_Address'];
        $_SESSION['Email2']        =$row_donor['Email_Address'];
        $_SESSION['donor_id']      =$row_donor['Donor_ID'];
        $_SESSION['donor_name']    =$row_donor['Fullname'];

        $stmt_donor->close();$conn->close();
        header("Location: index2.php");
        exit();
    }
}
$stmt_donor->close();

// =========================================================================
// 3. INVALID CREDENTIALS FALLBACK
// =========================================================================
$conn->close();
header("Location: login_page.html?error=invalid");
exit();
?>