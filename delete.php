<?php
session_start();
require_once 'dbconnection.inc.php';

// ==========================================
// 1. DELETE DONATION RECORD (goods_donated)
// ==========================================
if (isset($_POST['deld'])) {
    $id = intval($_POST['id2'] ?? 0);

    if ($id > 0) {
        $stmt =$conn->prepare("DELETE FROM `goods_donated` WHERE `Dontation_ID` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();$stmt->close();
        header("Location: system_admin.php?deleted=donation");
        exit();
    } else {
        header("Location: system_admin.php?error=invalid_id");
        exit();
    }
}

// ==========================================
// 2. DELETE ADMINISTRATOR (admin)
// ==========================================
if (isset($_POST['dela'])) {
    $id = intval($_POST['id3'] ?? 0);

    if ($id > 0) {
        $stmt =$conn->prepare("DELETE FROM `admin` WHERE `Administrator_ID` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();$stmt->close();
        header("Location: system_admin.php?deleted=admin");
        exit();
    } else {
        header("Location: system_admin.php?error=invalid_id");
        exit();
    }
}

// ==========================================
// 3. DELETE COMMODITY FROM SYSTEM ADMIN (commodity)
// ==========================================
if (isset($_POST['delc'])) {
    $id = intval($_POST['id1'] ?? 0);

    if ($id > 0) {
        $stmt =$conn->prepare("DELETE FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();$stmt->close();
        header("Location: system_admin.php?deleted=commodity");
        exit();
    } else {
        header("Location: system_admin.php?error=invalid_id");
        exit();
    }
}

// ==========================================
// 4. DELETE COMMODITY FROM AREA ADMIN (index1.php)
// ==========================================
if (isset($_POST['delc1'])) {
    $id = intval($_POST['id1'] ?? 0);

    if ($id > 0) {
        $stmt =$conn->prepare("DELETE FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();$stmt->close();
        header("Location: index1.php?commodity_deleted=success");
        exit();
    } else {
        header("Location: index1.php?error=invalid_id");
        exit();
    }
}

// ==========================================
// 5. DELETE DONOR (FIXED: points to `donors`, not `users`)
// ==========================================
if (isset($_POST['delu'])) {
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt =$conn->prepare("DELETE FROM `donors` WHERE `Donor_ID` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();$stmt->close();
        header("Location: system_admin.php?deleted=donor");
        exit();
    } else {
        header("Location: system_admin.php?error=invalid_id");
        exit();
    }
}

// Fallback redirect
header("Location: system_admin.php");
exit();
?>