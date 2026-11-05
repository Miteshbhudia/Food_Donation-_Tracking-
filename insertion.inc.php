<?php
ob_start();
session_start();
date_default_timezone_set('Africa/Nairobi');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.inc.php';

// =========================================================================
// HELPER 1: AUTO-UPGRADE MYSQL COLUMNS TO STORE EXACT DATE & TIME
// =========================================================================
function ensureTimestampColumns($conn) {
    // Upgrade existing date columns to VARCHAR(40) so they store both legacy dates and full 'YYYY-MM-DD HH:MM:SS' timestamps safely
    $conn->query("ALTER TABLE `commodity` MODIFY `Date_Added` VARCHAR(40)");
    $conn->query("ALTER TABLE `goods_donated` MODIFY `Date_Donated` VARCHAR(40)");

    // Ensure milestone timestamp columns exist in `goods_donated`
    $columns = ['Pledged_At', 'Collected_At', 'Delivered_At'];
    foreach ($columns as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `goods_donated` LIKE '$col'");
        if ($check and $check->num_rows === 0) {
            $conn->query("ALTER TABLE `goods_donated` ADD COLUMN `$col` VARCHAR(40) DEFAULT NULL");
        }
    }
}
ensureTimestampColumns($conn);

// =========================================================================
// HELPER 2: APPEND REAL-TIME EVENT TO `notifications.json`
// =========================================================================
function addSystemNotification($recipientRole, $recipientId, $location, $title, $message, $timestamp, $stage) {
    $notifFile = __DIR__ . '/notifications.json';
    $notifications = [];

    if (file_exists($notifFile)) {
        $content = file_get_contents($notifFile);
        $notifications = json_decode($content, true) ?: [];
    }

    array_unshift($notifications, [
        'id'             => uniqid('notif_', true),
        'recipient_role' => $recipientRole, // 'Donor' or 'Area Admin'
        'recipient_id'   => intval($recipientId),
        'location'       => $location,
        'title'          => $title,
        'message'        => $message,
        'timestamp'      => $timestamp,
        'stage'          => $stage,
        'is_read'        => false
    ]);

    // Keep the most recent 200 notifications
    $notifications = array_slice($notifications, 0, 200);
    file_put_contents($notifFile, json_encode($notifications, JSON_PRETTY_PRINT), LOCK_EX);
}

// =========================================================================
// 1. PUBLIC DONOR SELF-REGISTRATION (Strictly triggers via 'reg' button)
// =========================================================================
if (isset($_POST["reg"])) {
    $fname       = trim($_POST['fname'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $passconfirm = $_POST['cpassword'] ?? '';

    if (empty($password)) {
        die("Error: Please provide a password.");
    } elseif ($password !== $passconfirm) {
        die("Error: Passwords do not match.");
    } else {
        $hashedpassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO `donors`(`Fullname`, `Email_Address`, `Phone_Number`, `Password`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fname, $email, $phone, $hashedpassword);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: login_page.html?signup=success");
            exit();
        } else {
            die("Registration failed: " . $stmt->error);
        }
    }
}

// =========================================================================
// 2. SYSTEM ADMIN USER PROVISIONING (Area Admin, Donor, or System Admin)
// =========================================================================
if (isset($_POST['admin_create_user']) or isset($_POST['adda'])) {
    $role        = trim($_POST['role'] ?? 'Area Administrator');
    $fname       = trim($_POST['fname'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $cpassword   = $_POST['cpassword'] ?? '';

    if (empty($fname) or empty($email) or empty($password)) {
        die("Error: Please fill in all required fields.");
    }

    if ($password !== $cpassword) {
        die("Error: Passwords do not match.");
    }

    $hashedpassword = password_hash($password, PASSWORD_BCRYPT);

    if ($role === 'Donor') {
        // Provision Donor account
        $stmt = $conn->prepare("INSERT INTO `donors`(`Fullname`, `Email_Address`, `Phone_Number`, `Password`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fname, $email, $phone, $hashedpassword);
    } elseif ($role === 'System Administrator') {
        // Provision fellow System Administrator
        $hub_loc = !empty($location) ? $location : 'HQ Administration';
        $stmt = $conn->prepare("INSERT INTO `admin`(`Fullname`, `Email_Address`, `Position`, `Location`, `Password`) VALUES (?, ?, 'System Administrator', ?, ?)");
        $stmt->bind_param("ssss", $fname, $email, $hub_loc, $hashedpassword);
    } else {
        // Default: Provision Area Administrator
        $hub_loc = !empty($location) ? $location : 'Unassigned Hub';
        $stmt = $conn->prepare("INSERT INTO `admin`(`Fullname`, `Email_Address`, `Position`, `Location`, `Password`) VALUES (?, ?, 'Area Administrator', ?, ?)");
        $stmt->bind_param("ssss", $fname, $email, $hub_loc, $hashedpassword);
    }

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redirect back to System Admin Console while keeping session active
        header("Location: system_admin.php?user_added=" . urlencode($role));
        exit();
    } else {
        die("Account provisioning failed: " . $stmt->error);
    }
}

// =========================================================================
// 3. COMMODITY ADDITION (AREA ADMIN - STORES EXACT DATE & TIME IN DB)
// =========================================================================
if (isset($_POST['addc'])) {
    $adr   = intval($_POST['adr'] ?? 0);
    $com   = trim($_POST['com'] ?? '');
    $date1 = date('Y-m-d H:i:s'); // Exact server date & time

    if (!empty($_POST['number'])) {
        $num = trim($_POST['number']);
    } elseif (isset($_POST['quantity_num']) and isset($_POST['unit'])) {
        $num = trim($_POST['quantity_num']) . ' ' . trim($_POST['unit']);
    } elseif (isset($_POST['quantity_value']) and isset($_POST['quantity_unit'])) {
        $num = trim($_POST['quantity_value']) . ' ' . trim($_POST['quantity_unit']);
    } else {
        $num = '0 kg';
    }

    if (empty($adr) or empty($com) or empty($num)) {
        die("Error: Missing required fields for commodity.");
    }

    $stmt = $conn->prepare("INSERT INTO `commodity`(`Area_Administrator`, `Commodity`, `Quantity`, `Date_Added`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $adr, $com, $num, $date1);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: index1.php?commodity=success");
        exit();
    } else {
        die("Error adding commodity: " . $stmt->error);
    }
}

// =========================================================================
// 4. DONATION SUBMISSION (DONOR PLEDGE - STORES EXACT DATE & TIME IN DB)
// =========================================================================
if (isset($_POST['add_donation'])) {
    $commodity_id             = intval($_POST['commodity_id'] ?? 0);
    $donor_id                 = intval($_POST['donor_id'] ?? 0);
    $quantity_donated_numeric = trim($_POST['quantity'] ?? '0');
    $location                 = trim($_POST['location'] ?? '');

    // Record exact server date & time in both Date_Donated and Pledged_At
    $exact_timestamp = date('Y-m-d H:i:s');
    $status          = "Pending Pickup";

    $stmt_insert = $conn->prepare("INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`, `Status`, `Pledged_At`) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iisssss", $commodity_id, $donor_id, $quantity_donated_numeric, $exact_timestamp, $location, $status, $exact_timestamp);

    if ($stmt_insert->execute()) {
        $new_consignment_id = $stmt_insert->insert_id;

        $stmt_get = $conn->prepare("SELECT `Commodity`, `Quantity` FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt_get->bind_param("i", $commodity_id);
        $stmt_get->execute();
        $res = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        $commodity_name   = $res['Commodity'] ?? 'Food Aid';
        $current_full_str = $res['Quantity'] ?? '';
        $current_numeric  = floatval($current_full_str);

        $parts = explode(' ', trim($current_full_str));
        $unit  = (count($parts) > 1 and !is_numeric(end($parts))) ? end($parts) : '';

        $new_qty_num  = max(0, $current_numeric - floatval($quantity_donated_numeric));
        $new_full_str = trim($new_qty_num . ' ' . $unit);

        $stmt_upd = $conn->prepare("UPDATE `commodity` SET `Quantity` = ? WHERE `Commodity_ID` = ?");
        $stmt_upd->bind_param("si", $new_full_str, $commodity_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // Fetch Donor Name for Area Admin notification
        $donor_name = $_SESSION['donor_name'] ?? 'Registered Donor';
        $stmt_d = $conn->prepare("SELECT `Fullname` FROM `donors` WHERE `Donor_ID` = ?");
        $stmt_d->bind_param("i", $donor_id);
        $stmt_d->execute();
        if ($d_row = $stmt_d->get_result()->fetch_assoc()) {
            $donor_name = $d_row['Fullname'];
        }
        $stmt_d->close();

        // 1. Notify Area Administrator of the new incoming donation pledge & timestamp
        addSystemNotification(
            'Area Admin',
            0,
            $location,
            "New Aid Pledge — Consignment #{$new_consignment_id}",
            "{$donor_name} pledged {$quantity_donated_numeric} {$unit} of {$commodity_name} for {$location} Hub.",
            $exact_timestamp,
            'Pending Pickup'
        );

        // 2. Notify Donor that Stage 1 (Pledged) is recorded with timestamp
        addSystemNotification(
            'Donor',
            $donor_id,
            $location,
            "Pledge Confirmed — Consignment #{$new_consignment_id}",
            "Your pledge of {$quantity_donated_numeric} {$unit} of {$commodity_name} to {$location} Hub is logged and awaiting pickup.",
            $exact_timestamp,
            'Pending Pickup'
        );

        $stmt_insert->close();
        $conn->close();

        header("Location: index2.php?donation=success");
        exit();
    } else {
        die("Donation insertion failed: " . $stmt_insert->error);
    }
}

// Fallback
header("Location: homepage.html");
exit();
?>