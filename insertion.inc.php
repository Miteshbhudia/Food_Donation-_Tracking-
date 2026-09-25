<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.inc.php';

// --- Donor Registration ---
if (isset($_POST["reg"]) || (isset($_POST["fname"]) && isset($_POST["email"]))) {
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

        // Cleaned prepared statement without legacy recovery columns
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

// --- Admin Addition ---
if (isset($_POST['adda'])) {
    $fname     = trim($_POST['fname'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $location  = trim($_POST['location'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  =$_POST['password'] ?? '';
    $cpassword =$_POST['cpassword'] ?? '';

    if ($password ===$cpassword) {
        $hashedpassword = password_hash($password, PASSWORD_BCRYPT);

        // Cleaned prepared statement without legacy recovery columns
        $stmt =$conn->prepare("INSERT INTO `admin`(`Fullname`, `Email_Address`, `Position`, `Location`, `Password`) VALUES (?, ?, 'Area Administrator', ?, ?)");
        $stmt->bind_param("ssss", $fname,$email, $location,$hashedpassword);

        if ($stmt->execute()) {
            $stmt->close();$conn->close();
            header("Location: index.php");
            exit();
        } else {
            die("Admin registration failed: " . $stmt->error);
        }
    } else {
        die("Error: Passwords do not match.");
    }
}

// --- Commodity Addition ---
if (isset($_POST['addc'])) {
    $adr   = intval($_POST['adr'] ?? 0);
    $com   = trim($_POST['com'] ?? '');
    $num   = trim($_POST['number'] ?? '');
    $date1 = trim($_POST['date1'] ?? '');

    $stmt =$conn->prepare("INSERT INTO `commodity`(`Area_Administrator`, `Commodity`, `Quantity`, `Date_Added`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $adr,$com, $num,$date1);

    if ($stmt->execute()) {
        $stmt->close();$conn->close();
        header("Location: index.php?commodity=success");
        exit();
    } else {
        die("Error adding commodity: " . $stmt->error);
    }
}

// --- Donation Submission ---
if (isset($_POST['add_donation'])) {
    $commodity_id             = intval($_POST['commodity_id'] ?? 0);
    $donor_id                 = intval($_POST['donor_id'] ?? 0);
    $quantity_donated_numeric = trim($_POST['quantity'] ?? '0');
    $location                 = trim($_POST['location'] ?? '');
    $date_donated_raw         =$_POST['date'] ?? '';

    $date_obj = DateTime::createFromFormat('m/d/Y',$date_donated_raw);
    $date_donated_formatted =$date_obj ? $date_obj->format('Y-m-d') : date('Y-m-d');$status = "Pending Pickup";

    $stmt_insert =$conn->prepare("INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`, `Status`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("iissss", $commodity_id,$donor_id, $quantity_donated_numeric,$date_donated_formatted, $location,$status);

    if ($stmt_insert->execute()) {
        $stmt_get_current_qty =$conn->prepare("SELECT Quantity FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt_get_current_qty->bind_param("i", $commodity_id);
        $stmt_get_current_qty->execute();$res = $stmt_get_current_qty->get_result()->fetch_assoc();$current_full_quantity_str = $res['Quantity'] ?? '';$stmt_get_current_qty->close();

        $current_numeric_qty = 0;
        $unit_from_commodity = '';
        if (preg_match('/^(\d+(\.\d+)?)\s*([a-zA-Z]+)?$/', $current_full_quantity_str,$matches)) {
            $current_numeric_qty = (float)$matches[1];
            $unit_from_commodity =$matches[3] ?? '';
        }

        $new_numeric_qty = max(0, $current_numeric_qty - (float)$quantity_donated_numeric);
        $new_full_quantity_str =$new_numeric_qty . ($unit_from_commodity ? ' ' . $unit_from_commodity : '');

        $stmt_update =$conn->prepare("UPDATE `commodity` SET `Quantity` = ? WHERE `Commodity_ID` = ?");
        $stmt_update->bind_param("si", $new_full_quantity_str,$commodity_id);
        $stmt_update->execute();$stmt_update->close();

        $stmt_insert->close();$conn->close();
        header("Location: index.php?donation=success");
        exit();
    } else {
        die("Donation insertion failed: " . $stmt_insert->error);
    }
}

// Fallback
header("Location: homepage.html");
exit();
?>