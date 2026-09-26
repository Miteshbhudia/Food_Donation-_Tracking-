<?php
session_start();
require_once 'dbconnection.inc.php';

// ==========================================
// 1. AJAX BACKEND: SMART LIFECYCLE AUTO-PROGRESSION
// ==========================================
if (isset($_POST['action']) and $_POST['action'] === 'qr_scan_update') {
    header('Content-Type: application/json');

    $raw_token = trim($_POST['qr_token'] ?? '');
    $location  = trim($_POST['location'] ?? '');

    // Extract numerical consignment ID from QR token
    $donation_id = null;
    if (preg_match('/\d+/', $raw_token, $matches)) {
        $donation_id = intval($matches[0]);
    }

    if (!$donation_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid QR Code. No numerical consignment ID detected.']);
        exit();
    }

    // Inspect current consignment state
    $stmt_check = $conn->prepare("
        SELECT g.Dontation_ID, g.Quantity, g.Status, g.Location, d.Fullname AS Donor_Name, c.Commodity 
        FROM goods_donated g
        LEFT JOIN donors d ON g.Donor_ID = d.Donor_ID
        LEFT JOIN commodity c ON g.Commodity_ID = c.Commodity_ID
        WHERE g.Dontation_ID = ?
    ");
    $stmt_check->bind_param("i", $donation_id);
    $stmt_check->execute();
    $consignment = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$consignment) {
        echo json_encode(['success' => false, 'message' => "Consignment #$donation_id was not found in database records."]);
        exit();
    }

    $current_status = $consignment['Status'];
    $new_status = '';
    $stage_title = '';

    // Smart State Machine Transitions
    if ($current_status === 'Pending Pickup' or empty($current_status)) {
        $new_status = 'Collected';
        $stage_title = "Intake Verified: Marked as 'Collected'";
    } elseif ($current_status === 'Collected') {
        $new_status = 'Delivered';
        $stage_title = "Distribution Handover Complete: Marked as 'Delivered'";
    } elseif ($current_status === 'Delivered') {
        // Guardrail: Block duplicate scans on completed aid
        echo json_encode([
            'success'           => false,
            'already_fulfilled' => true,
            'message'           => "Consignment #$donation_id is already fulfilled. Cannot duplicate or revert milestone.",
            'consignment'       => [
                'id'        => $donation_id,
                'donor'     => $consignment['Donor_Name'] ?? 'Anonymous Donor',
                'item'      => $consignment['Commodity'] ?? 'Relief Supplies',
                'quantity'  => $consignment['Quantity'],
                'status'    => 'Delivered',
                'location'  => $consignment['Location']
            ]
        ]);
        exit();
    } else {
        $new_status = 'Collected';
        $stage_title = "Status updated to 'Collected'";
    }

    // Commit auto-progressed state to database
    if (!empty($location)) {
        $stmt_upd = $conn->prepare("UPDATE goods_donated SET Status = ?, Location = ? WHERE Dontation_ID = ?");
        $stmt_upd->bind_param("ssi", $new_status, $location, $donation_id);
    } else {
        $stmt_upd = $conn->prepare("UPDATE goods_donated SET Status = ? WHERE Dontation_ID = ?");
        $stmt_upd->bind_param("si", $new_status, $donation_id);
    }

    if ($stmt_upd->execute()) {
        $stmt_upd->close();
        echo json_encode([
            'success'     => true,
            'message'     => $stage_title,
            'consignment' => [
                'id'         => $donation_id,
                'donor'      => $consignment['Donor_Name'] ?? 'Anonymous Donor',
                'item'       => $consignment['Commodity'] ?? 'Relief Supplies',
                'quantity'   => $consignment['Quantity'],
                'old_status' => $current_status,
                'new_status' => $new_status,
                'location'   => !empty($location) ? $location : $consignment['Location']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update error: ' . $conn->error]);
    }
    exit();
}

// ==========================================
// 2. LEGACY POST HANDLERS
// ==========================================
if (isset($_POST['submit'])) {
    $id    = intval($_POST['did'] ?? 0);
    $fname = trim($_POST['fname'] ?? '');
    $phone = trim($_POST['pho'] ?? '');

    $stmt = $conn->prepare("UPDATE `donors` SET `Fullname` = ?, `Phone_Number` = ? WHERE `Donor_ID` = ?");
    $stmt->bind_param("ssi", $fname, $phone, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: index1.php?update=success");
    exit();
}

if (isset($_POST['addloc'])) {
    $lid      = intval($_POST['lid'] ?? 0);
    $location = trim($_POST['loc'] ?? '');

    $stmt = $conn->prepare("UPDATE `goods_donated` SET `Location` = ? WHERE `Dontation_ID` = ?");
    $stmt->bind_param("si", $location, $lid);
    $stmt->execute();
    $stmt->close();

    header("Location: index1.php?location=success");
    exit();
}
?>