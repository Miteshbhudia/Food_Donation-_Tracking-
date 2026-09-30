<?php
session_start();
require_once 'dbconnection.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_page.html");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password =$_POST['password'] ?? '';
$role     = trim($_POST['role'] ?? 'Donor');

if (empty($email) or empty($password)) {
    header("Location: login_page.html?error=empty");
    exit();
}

// =========================================================================
// 1. DONOR AUTHENTICATION
// =========================================================================
if ($role === 'Donor') {
    $stmt =$conn->prepare("SELECT `Donor_ID`, `Fullname`, `Email_Address`, `Password` FROM `donors` WHERE `Email_Address` = ?");
    $stmt->bind_param("s", $email);$stmt->execute();
    $result =$stmt->get_result();

    if ($row = $result->fetch_assoc()) {$db_pass = $row['Password'];$is_valid = false;

        // Verify modern bcrypt hash or fallback to legacy plaintext
        if (password_verify($password, $db_pass) or$password === $db_pass) {$is_valid = true;

            // Automatically upgrade legacy plaintext to bcrypt
            if ($password ===$db_pass) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_upd =$conn->prepare("UPDATE `donors` SET `Password` = ? WHERE `Donor_ID` = ?");
                $stmt_upd->bind_param("si", $new_hash,$row['Donor_ID']);
                $stmt_upd->execute();$stmt_upd->close();
            }
        }

        if ($is_valid) {
            session_regenerate_id(true);
            $_SESSION['Email']      =$row['Email_Address'];
            $_SESSION['Email2']     =$row['Email_Address'];
            $_SESSION['donor_id']   =$row['Donor_ID'];
            $_SESSION['donor_name'] =$row['Fullname'];

            $stmt->close();$conn->close();
            header("Location: index2.php");
            exit();
        }
    }
    $stmt->close();$conn->close();
    header("Location: login_page.html?error=invalid");
    exit();
}

// =========================================================================
// 2. AREA ADMINISTRATOR AUTHENTICATION
// =========================================================================
if ($role === 'Area Admin') {
    $stmt =$conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location`, `Password` FROM `admin` WHERE `Email_Address` = ? AND `Position` = 'Area Administrator'");
    $stmt->bind_param("s", $email);$stmt->execute();
    $result =$stmt->get_result();

    if ($row = $result->fetch_assoc()) {$db_pass = $row['Password'];$is_valid = false;

        if (password_verify($password, $db_pass) or$password === $db_pass) {$is_valid = true;

            if ($password ===$db_pass) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_upd =$conn->prepare("UPDATE `admin` SET `Password` = ? WHERE `Administrator_ID` = ?");
                $stmt_upd->bind_param("si", $new_hash,$row['Administrator_ID']);
                $stmt_upd->execute();$stmt_upd->close();
            }
        }

        if ($is_valid) {
            session_regenerate_id(true);
            $_SESSION['Email']          =$row['Email_Address'];
            $_SESSION['Email1']         =$row['Email_Address'];
            $_SESSION['admin_id']       =$row['Administrator_ID'];
            $_SESSION['admin_name']     =$row['Fullname'];
            $_SESSION['admin_location'] =$row['Location'];

            $stmt->close();$conn->close();
            header("Location: index1.php");
            exit();
        }
    }
    $stmt->close();$conn->close();
    header("Location: login_page.html?error=invalid");
    exit();
}

// =========================================================================
// 3. SYSTEM ADMINISTRATOR AUTHENTICATION
// =========================================================================
if ($role === 'System Admin') {
    $stmt =$conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Password` FROM `admin` WHERE `Email_Address` = ? AND `Position` = 'System Administrator'");
    $stmt->bind_param("s", $email);$stmt->execute();
    $result =$stmt->get_result();

    if ($row = $result->fetch_assoc()) {$db_pass = $row['Password'];$is_valid = false;

        if (password_verify($password, $db_pass) or$password === $db_pass) {$is_valid = true;

            if ($password ===$db_pass) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_upd =$conn->prepare("UPDATE `admin` SET `Password` = ? WHERE `Administrator_ID` = ?");
                $stmt_upd->bind_param("si", $new_hash,$row['Administrator_ID']);
                $stmt_upd->execute();$stmt_upd->close();
            }
        }

        if ($is_valid) {
            session_regenerate_id(true);
            $_SESSION['Email']     =$row['Email_Address'];
            $_SESSION['adminname'] =$row['Fullname'];
            $_SESSION['admin_id']  =$row['Administrator_ID'];

            $stmt->close();$conn->close();
            header("Location: system_admin.php");
            exit();
        }
    }
    $stmt->close();$conn->close();
    header("Location: login_page.html?error=invalid");
    exit();
}

$conn->close();
header("Location: login_page.html?error=invalid");
exit();
?>