<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.inc.php';

// ==========================================
// 1. DONOR REGISTRATION
// ==========================================
if (isset($_POST["reg"]) or (isset($_POST["fname"]) and isset($_POST["email"]))) {
    $fname       = trim($_POST['fname'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    =$_POST['password'] ?? '';
    $passconfirm =$_POST['cpassword'] ?? '';

    if (empty($password)) {
        die("Error: Please provide a password.");
    } elseif ($password !==$passconfirm) {
        die("Error: Passwords do not match.");
    } else {
        $hashedpassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt =$conn->prepare("INSERT INTO `donors`(`Fullname`, `Email_Address`, `Phone_Number`, `Password`) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Database statement error: " . $conn->error);
        }

        $stmt->bind_param("ssss", $fname,$email, $phone,$hashedpassword);

        if ($stmt->execute()) {
            $stmt->close();$conn->close();
            header("Location: login_page.html?signup=success");
            exit();
        } else {
            die("Registration failed: " . $stmt->error);
        }
    }
}

// ==========================================
// 2. ADMIN REGISTRATION
// ==========================================
if (isset($_POST['adda'])) {
    $fname     = trim($_POST['fname'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $location  = trim($_POST['location'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  =$_POST['password'] ?? '';
    $cpassword =$_POST['cpassword'] ?? '';

    if ($password ===$cpassword) {
        $hashedpassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt =$conn->prepare("INSERT INTO `admin`(`Fullname`, `Email_Address`, `Position`, `Location`, `Password`) VALUES (?, ?, 'Area Administrator', ?, ?)");
        $stmt->bind_param("ssss", $fname,$email, $location,$hashedpassword);

        if ($stmt->execute()) {
            $stmt->close();$conn->close();
            header("Location: system_admin.php?admin_added=success");
            exit();
        } else {
            die("Admin registration failed: " . $stmt->error);
        }
    } else {
        die("Error: Passwords do not match.");
    }
}

// ==========================================
// 3. COMMODITY ADDITION (AREA ADMIN)
// ==========================================
if (isset($_POST['addc'])) {
    $adr   = intval($_POST['adr'] ?? 0);
    $com   = trim($_POST['com'] ?? '');
    $date1 = trim($_POST['date1'] ?? date('Y-m-d'));

    // Handle combined string or separate numeric + unit inputs
    if (!empty($_POST['number'])) {
        $num = trim($_POST['number']);
    } elseif (isset($_POST['quantity_num']) and isset($_POST['unit'])) {$num = trim($_POST['quantity_num']) . ' ' . trim($_POST['unit']);
    } elseif (isset($_POST['quantity_value']) and isset($_POST['quantity_unit'])) {$num = trim($_POST['quantity_value']) . ' ' . trim($_POST['quantity_unit']);
    } else {
        $num = '0 kg';
    }

    if (empty($adr) or empty($com) or empty($num)) {
        die("Error: Missing required fields. Please ensure commodity name, quantity, and unit are supplied.");
    }

    $stmt =$conn->prepare("INSERT INTO `commodity`(`Area_Administrator`, `Commodity`, `Quantity`, `Date_Added`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $adr,$com, $num,$date1);

    if ($stmt->execute()) {
        $stmt->close();$conn->close();
        header("Location: index1.php?commodity=success");
        exit();
    } else {
        die("Error adding commodity: " . $stmt->error);
    }
}

// ==========================================
// 4. DONATION SUBMISSION (DONOR PLEDGE)
// ==========================================
if (isset($_POST['add_donation'])) {
    $commodity_id             = intval($_POST['commodity_id'] ?? 0);
    $donor_id                 = intval($_POST['donor_id'] ?? 0);
    $quantity_donated_numeric = trim($_POST['quantity'] ?? '0');
    $location                 = trim($_POST['location'] ?? '');
    $date_donated_raw         =$_POST['date'] ?? '';

    $date_obj = DateTime::createFromFormat('m/d/Y',$date_donated_raw);
    $date_donated_formatted = ($date_obj !== false) ? $date_obj->format('Y-m-d') : date('Y-m-d');$status = "Pending Pickup";

    $stmt_insert =$conn->prepare("INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`, `Status`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iissss", $commodity_id,$donor_id, $quantity_donated_numeric,$date_donated_formatted, $location,$status);

    if ($stmt_insert->execute()) {
        // Fetch remaining quantity
        $stmt_get =$conn->prepare("SELECT Quantity FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt_get->bind_param("i", $commodity_id);
        $stmt_get->execute();$res = $stmt_get->get_result()->fetch_assoc();$stmt_get->close();

        $current_full_str =$res['Quantity'] ?? '';
        $current_numeric  = floatval($current_full_str);

        // Extract unit suffix safely
        $parts = explode(' ', trim($current_full_str));
        $unit  = (count($parts) > 1 and !is_numeric(end($parts))) ? end($parts) : '';

        // Decrement quantity without going below zero
        $new_qty_num  = max(0,$current_numeric - floatval($quantity_donated_numeric));$new_full_str = trim($new_qty_num . ' ' .$unit);

        // Update commodity balance
        $stmt_upd =$conn->prepare("UPDATE `commodity` SET `Quantity` = ? WHERE `Commodity_ID` = ?");
        $stmt_upd->bind_param("si", $new_full_str,$commodity_id);
        $stmt_upd->execute();$stmt_upd->close();

        $stmt_insert->close();$conn->close();

        header("Location: index2.php?donation=success");
        exit();
    } else {
        die("Donation insertion failed: " . $stmt_insert->error);
    }
}

// Fallback if accessed directly without POST
header("Location: homepage.html");
exit();
?>