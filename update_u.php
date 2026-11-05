<?php
session_start();
date_default_timezone_set('Africa/Nairobi');
require_once 'dbconnection.inc.php';

// =========================================================================
// HELPER: APPEND REAL-TIME EVENT TO `notifications.json`
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
        'recipient_role' => $recipientRole,
        'recipient_id'   => intval($recipientId),
        'location'       => $location,
        'title'          => $title,
        'message'        => $message,
        'timestamp'      => $timestamp,
        'stage'          => $stage,
        'is_read'        => false
    ]);

    $notifications = array_slice($notifications, 0, 200);
    file_put_contents($notifFile, json_encode($notifications, JSON_PRETTY_PRINT), LOCK_EX);
}

// ==========================================
// 1. AJAX BACKEND: SMART LIFECYCLE AUTO-PROGRESSION & TIMESTAMPS
// ==========================================
if (isset($_POST['action']) and $_POST['action'] === 'qr_scan_update') {
    header('Content-Type: application/json');

    $raw_token    = trim($_POST['qr_token'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $current_time = date('Y-m-d H:i:s');

    // Extract numerical consignment ID from QR token
    $donation_id = null;
    if (preg_match('/\d+/', $raw_token, $matches)) {
        $donation_id = intval($matches[0]);
    }

    if (!$donation_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid QR Code. No numerical consignment ID detected.']);
        exit();
    }

    // Inspect current consignment state and fetch Donor details for notifications
    $stmt_check = $conn->prepare("
        SELECT g.Dontation_ID, g.Quantity, g.Status, g.Location, g.Donor_ID, d.Fullname AS Donor_Name, c.Commodity 
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
    $donor_id       = $consignment['Donor_ID'];
    $donor_name     = $consignment['Donor_Name'] ?? 'Donor';
    $commodity      = $consignment['Commodity'] ?? 'Relief Supplies';
    $quantity       = $consignment['Quantity'];
    
    $new_status    = '';
    $stage_title   = '';
    $timestamp_col = '';

    // Smart State Machine Transitions
    if ($current_status === 'Pending Pickup' or empty($current_status)) {
        $new_status    = 'Collected';
        $stage_title   = "Intake Verified: Marked as 'Collected'";
        $timestamp_col = 'Collected_At';
    } elseif ($current_status === 'Collected') {
        $new_status    = 'Delivered';
        $stage_title   = "Distribution Handover Complete: Marked as 'Delivered'";
        $timestamp_col = 'Delivered_At';
    } elseif ($current_status === 'Delivered') {
        // Guardrail: Block duplicate scans on completed aid
        echo json_encode([
            'success'           => false,
            'already_fulfilled' => true,
            'message'           => "Consignment #$donation_id is already fulfilled. Cannot duplicate or revert milestone.",
            'consignment'       => [
                'id'        => $donation_id,
                'donor'     => $donor_name,
                'item'      => $commodity,
                'quantity'  => $quantity,
                'status'    => 'Delivered',
                'location'  => $consignment['Location']
            ]
        ]);
        exit();
    } else {
        $new_status    = 'Collected';
        $stage_title   = "Status updated to 'Collected'";
        $timestamp_col = 'Collected_At';
    }

    $final_location = !empty($location) ? $location : $consignment['Location'];

    // Commit auto-progressed state to database WITH dynamic timestamp column
    $query = "UPDATE goods_donated SET Status = ?, Location = ?, {$timestamp_col} = ? WHERE Dontation_ID = ?";
    $stmt_upd = $conn->prepare($query);
    $stmt_upd->bind_param("sssi", $new_status, $final_location, $current_time, $donation_id);

    if ($stmt_upd->execute()) {
        $stmt_upd->close();

        // Dispatch live notification to the specific Donor
        if ($new_status === 'Collected') {
            $notif_title = "Consignment Collected — #{$donation_id}";
            $notif_msg   = "Your pledge of {$quantity} of {$commodity} was successfully received at {$final_location} Hub.";
        } else {
            $notif_title = "Consignment Delivered! — #{$donation_id}";
            $notif_msg   = "Your pledge of {$quantity} of {$commodity} has been distributed to beneficiaries from {$final_location} Hub. Thank you!";
        }
        
        addSystemNotification('Donor', $donor_id, $final_location, $notif_title, $notif_msg, $current_time, $new_status);

        echo json_encode([
            'success'     => true,
            'message'     => $stage_title,
            'consignment' => [
                'id'         => $donation_id,
                'donor'      => $donor_name,
                'item'       => $commodity,
                'quantity'   => $quantity,
                'old_status' => $current_status,
                'new_status' => $new_status,
                'location'   => $final_location,
                'timestamp'  => $current_time
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