<?php
session_start();
date_default_timezone_set('Africa/Nairobi');
require_once 'dbconnection.inc.php';

// ==========================================
// 0. AUTO-ENSURE DB COLUMNS STORE EXACT TIME
// ==========================================
function ensureTimestampColumnsDonor($conn) {
    $conn->query("ALTER TABLE `commodity` MODIFY `Date_Added` VARCHAR(40)");
    $conn->query("ALTER TABLE `goods_donated` MODIFY `Date_Donated` VARCHAR(40)");
    $columns = ['Pledged_At', 'Collected_At', 'Delivered_At'];
    foreach ($columns as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `goods_donated` LIKE '$col'");
        if ($check and $check->num_rows === 0) {
            $conn->query("ALTER TABLE `goods_donated` ADD COLUMN `$col` VARCHAR(40) DEFAULT NULL");
        }
    }
}
ensureTimestampColumnsDonor($conn);

// Format database date/datetime strings cleanly with date + time
function formatDateTimeDisplay($raw_val) {
    if (empty($raw_val)) return 'N/A';
    $ts = strtotime($raw_val);
    if ($ts === false) return htmlspecialchars($raw_val);
    if (strlen(trim($raw_val)) > 10 and date('H:i:s', $ts) !== '00:00:00') {
        return date('M d, Y', $ts) . " <span class='text-success fw-semibold ms-1'><i class='bi bi-clock me-1'></i>" . date('H:i:s', $ts) . "</span>";
    }
    return date('M d, Y', $ts);
}

// ==========================================
// 1. STRICT SESSION & CONCURRENT LOGIN GUARD
// ==========================================

// Inactivity Timeout (15 minutes = 900 seconds)
if (isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: login_page.html?error=timeout");
    exit();
}
$_SESSION['last_activity'] = time();

// Verify Donor authentication & role
$email = $_SESSION['Email2'] ?? $_SESSION['Email'] ?? null;
if (!$email or !isset($_SESSION['user_role']) or $_SESSION['user_role'] !== 'Donor' or !isset($_SESSION['session_token'])) {
    session_unset();
    session_destroy();
    header("Location: login_page.html?error=unauthorized");
    exit();
}

// File-based Single Active Session Token Verification
$registryFile = __DIR__ . '/active_sessions.json';
if (file_exists($registryFile)) {
    $sessions = json_decode(file_get_contents($registryFile), true) ?: [];
    if (!isset($sessions[$email]) or $sessions[$email]['token'] !== $_SESSION['session_token']) {
        session_unset();
        session_destroy();
        header("Location: login_page.html?error=concurrent");
        exit();
    }
}

// FETCH DONOR DATA
$stmt = $conn->prepare("SELECT Fullname, Donor_ID, Phone_Number FROM `donors` WHERE `Email_Address` = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$query_result = $stmt->get_result();
$row = $query_result->fetch_assoc();
$stmt->close();

if ($row) {
    $donor_fullname = $row['Fullname'];
    $donor_id       = $row['Donor_ID'];
    $donor_phone    = $row['Phone_Number'] ?? '';
} else {
    session_unset();
    session_destroy();
    header("Location: login_page.html");
    exit();
}

// ==========================================
// 1.5 SELF-SERVICE PROFILE UPDATE HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $up_name  = trim($_POST['fullname'] ?? '');
    $up_phone = trim($_POST['phone'] ?? '');
    $up_pass  = $_POST['new_password'] ?? '';
    
    if (!empty($up_name)) {
        if (!empty($up_pass)) {
            $new_hash = password_hash($up_pass, PASSWORD_BCRYPT);
            $stmt_upd = $conn->prepare("UPDATE `donors` SET `Fullname` = ?, `Phone_Number` = ?, `Password` = ? WHERE `Donor_ID` = ?");
            $stmt_upd->bind_param("sssi", $up_name, $up_phone, $new_hash, $donor_id);
        } else {
            $stmt_upd = $conn->prepare("UPDATE `donors` SET `Fullname` = ?, `Phone_Number` = ? WHERE `Donor_ID` = ?");
            $stmt_upd->bind_param("ssi", $up_name, $up_phone, $donor_id);
        }
        
        if ($stmt_upd->execute()) {
            $_SESSION['flash_success'] = "Your profile settings were updated successfully.";
            $donor_fullname = $up_name; 
            $donor_phone = $up_phone;
        } else {
            $_SESSION['flash_error'] = "Failed to update profile due to a system error.";
        }
        $stmt_upd->close();
        header("Location: index2.php");
        exit();
    }
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ==========================================
// 2. FETCH UNREAD SYSTEM NOTIFICATIONS
// ==========================================
$notifFile = __DIR__ . '/notifications.json';
$my_notifications = [];
$unread_count = 0;

if (file_exists($notifFile)) {
    $all_notifs = json_decode(file_get_contents($notifFile), true) ?: [];
    foreach ($all_notifs as $n) {
        if (($n['recipient_role'] ?? '') === 'Donor' and intval($n['recipient_id'] ?? 0) === intval($donor_id)) {
            $my_notifications[] = $n;
            if (empty($n['is_read'])) $unread_count++;
        }
    }
}

// Donor Personal Impact Telemetry
$stmt_imp1 = $conn->prepare("SELECT COUNT(*) AS total FROM `goods_donated` WHERE `Donor_ID` = ?");
$stmt_imp1->bind_param("i", $donor_id);
$stmt_imp1->execute();
$donor_total_pledges = $stmt_imp1->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_imp1->close();

$stmt_imp2 = $conn->prepare("SELECT COUNT(*) AS total FROM `goods_donated` WHERE `Donor_ID` = ? AND `Status` = 'Delivered'");
$stmt_imp2->bind_param("i", $donor_id);
$stmt_imp2->execute();
$donor_delivered_pledges = $stmt_imp2->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_imp2->close();

$donor_active_pledges = max(0, $donor_total_pledges - $donor_delivered_pledges);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Donor Portal - Food Aid Traceability System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- QR Code Generator CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #dcfce7;
            --dark: #0f172a;
            --gray-body: #64748b;
            --bg-light: #f8fafc;
            --card-border: #e2e8f0;
            --bg-card: #ffffff;
        }

        /* Dark Mode Overrides */
        body.dark-mode { 
            --dark: #f8fafc; 
            --bg-light: #0f172a; 
            --card-border: #334155; 
            --gray-body: #94a3b8; 
            --bg-card: #1e293b; 
            color: #f8fafc; 
        }
        body.dark-mode .navbar, 
        body.dark-mode .section-card, 
        body.dark-mode .stat-widget, 
        body.dark-mode .modal-content, 
        body.dark-mode .dropdown-menu { background-color: var(--bg-card); border-color: var(--card-border); color: #f8fafc; }
        
        body.dark-mode .table { --bs-table-bg: transparent; color: #f8fafc; border-color: var(--card-border); }
        body.dark-mode .table thead th { background-color: #0f172a !important; color: #94a3b8 !important; border-color: var(--card-border); }
        body.dark-mode .table tbody td { background-color: var(--bg-card) !important; color: #f8fafc !important; border-color: var(--card-border); }
        body.dark-mode .table-hover tbody tr:hover td { background-color: #334155 !important; color: #f8fafc !important; }
        
        body.dark-mode input.form-control, 
        body.dark-mode .input-group-text, 
        body.dark-mode .search-input-box input { background-color: #0f172a; color: #f8fafc; border-color: var(--card-border); }
        body.dark-mode .btn-print, body.dark-mode .btn-outline-secondary { background-color: transparent; border-color: var(--card-border); color: #94a3b8; }
        body.dark-mode .btn-print:hover, body.dark-mode .btn-outline-secondary:hover { background-color: #334155; color: #f8fafc; }
        body.dark-mode .notif-item:hover { background-color: #334155; }
        body.dark-mode .badge.bg-light { background-color: #334155 !important; color: #f8fafc !important; }
        body.dark-mode .dropdown-item { color: #f8fafc; }
        body.dark-mode .dropdown-item:hover { background-color: #334155; color: #ffffff; }

        /* HIDE GOOGLE TRANSLATE UGLY ELEMENTS COMPLETELY */
        body { top: 0 !important; }
        .skiptranslate { display: none !important; }
        #google_translate_element { display: none !important; }
        .goog-te-banner-frame { display: none !important; }
        .goog-tooltip { display: none !important; }
        .goog-tooltip:hover { display: none !important; }
        .goog-text-highlight { background-color: transparent !important; border: none !important; box-shadow: none !important; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--dark);
            min-height: 100vh;
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--card-border);
            transition: background-color 0.3s;
        }
        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        .brand-title span { color: var(--dark); }

        .hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #064e3b 100%);
            color: #ffffff;
            padding: 3.25rem 0;
            margin-bottom: 2.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .section-card {
            background: var(--bg-card);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04);
            padding: 2rem;
            margin-bottom: 2.25rem;
            transition: background-color 0.3s;
        }

        /* Impact Metric Widgets */
        .stat-widget {
            background: var(--bg-card);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.5rem 1.25rem;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.3s;
            height: 100%;
        }
        .stat-widget:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.07);
        }
        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: var(--primary-subtle);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 0.85rem;
        }
        .stat-widget h6 {
            font-size: 0.8rem;
            color: var(--gray-body);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .stat-widget .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0;
        }

        /* AMAZON-STYLE TRACKING CARDS CSS */
        .tracking-card {
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: var(--bg-card);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .tracking-header {
            background-color: rgba(0,0,0,0.02);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 1.25rem;
            border-radius: 12px 12px 0 0;
        }
        body.dark-mode .tracking-header { background-color: rgba(255,255,255,0.02); }
        .tracking-timeline {
            position: relative;
            padding: 1.5rem 1.5rem 1.5rem 2.5rem;
            flex-grow: 1;
        }
        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 28px; 
            top: 2rem;
            bottom: 2rem;
            width: 2px;
            background-color: var(--card-border);
        }
        .timeline-step {
            position: relative;
            margin-bottom: 1.75rem;
        }
        .timeline-step:last-child { margin-bottom: 0; }
        .timeline-icon {
            position: absolute;
            left: -2rem;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: var(--card-border);
            border: 4px solid var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            color: var(--gray-body);
            font-size: 0.85rem;
        }
        /* Steps Styling */
        .timeline-step.completed .timeline-icon { background-color: #16a34a; color: white; border-color: var(--bg-card); }
        .timeline-step.active .timeline-icon { background-color: #0ea5e9; color: white; border-color: var(--bg-card); animation: pulse-blue 2s infinite; }
        
        @keyframes pulse-blue {
            0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(14, 165, 233, 0); }
            100% { box-shadow: 0 0 0 0 rgba(14, 165, 233, 0); }
        }
        
        .timeline-title { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.1rem; color: var(--dark); }
        .timeline-step.completed .timeline-title { color: #16a34a; }
        .timeline-step.active .timeline-title { color: #0ea5e9; }
        .timeline-text { font-size: 0.85rem; color: var(--gray-body); margin-bottom: 0; line-height: 1.3; }
        .timeline-date { font-size: 0.75rem; color: #94a3b8; font-weight: 500; display: block; margin-top: 0.15rem; }

        .tracking-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--card-border);
            background-color: var(--bg-card);
            border-radius: 0 0 12px 12px;
        }


        .table thead th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 0.825rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--card-border);
            padding: 0.85rem 1rem;
        }
        .table tbody td {
            vertical-align: middle;
            font-size: 0.9rem;
            padding: 0.85rem 1rem;
            color: var(--dark);
        }

        .btn-primary-custom {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.55rem 1.25rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-print {
            background-color: var(--bg-card);
            border: 1px solid var(--card-border);
            color: var(--gray-body);
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.55rem 1.25rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-print:hover {
            background-color: #f1f5f9;
            color: var(--dark);
        }
        
        .timestamp-small {
            display: block;
            font-size: 0.75rem;
            color: var(--gray-body);
            margin-top: 0.25rem;
            font-weight: normal;
        }

        /* Stepper in QR Modal */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            position: relative;
        }
        .stepper-wrapper::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 20px;
            right: 20px;
            height: 2px;
            background-color: var(--card-border);
            z-index: 1;
        }
        .stepper-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
        }
        .stepper-item .step-counter {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.35rem;
            color: #64748b;
        }
        .stepper-item.completed .step-counter {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }
        .stepper-item.active .step-counter {
            border-color: var(--primary);
            color: var(--primary);
        }
        .step-time {
            font-size: 0.65rem;
            font-weight: 400;
            color: #64748b;
            margin-top: 2px;
        }

        .search-input-box {
            position: relative;
        }
        .search-input-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-body);
            pointer-events: none;
        }
        .search-input-box input {
            padding-left: 36px;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            font-size: 0.875rem;
        }
        
        /* Notification Dropdown */
        .notif-bell {
            position: relative;
            cursor: pointer;
            color: var(--gray-body);
            font-size: 1.25rem;
            transition: color 0.2s;
        }
        .notif-bell:hover { color: var(--dark); }
        .notif-badge {
            position: absolute;
            top: -2px;
            right: -4px;
            background-color: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 50px;
            padding: 0.15rem 0.35rem;
            border: 2px solid #ffffff;
        }
        .dropdown-menu-notif {
            width: 340px;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
            border: 1px solid var(--card-border);
            padding: 0;
            background-color: var(--bg-card);
        }
        .notif-item {
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            transition: background 0.2s;
        }
        .notif-item:hover { background-color: #f8fafc; }
        .notif-item:last-child { border-bottom: none; }
        .notif-title { font-size: 0.85rem; font-weight: 600; color: var(--dark); margin-bottom: 0.25rem; }
        .notif-time { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; }
        .notif-msg { font-size: 0.8rem; color: var(--gray-body); margin-bottom: 0; line-height: 1.4; }

        .site-footer {
            background-color: #0b1120;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 3rem 0 1.5rem 0;
            margin-top: 5rem;
        }

        /* ACCESSIBILITY: Low-Vision Text Resizer Classes */
        html.a11y-large { font-size: 115% !important; }
        html.a11y-xlarge { font-size: 130% !important; }
    </style>
    
    <!-- Instant Theme & Accessibility Applicator (Prevents white flash on load) -->
    <script>
        // Apply Dark Mode
        if (localStorage.getItem('foodtrace_theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.addEventListener("DOMContentLoaded", function() {
                document.body.classList.add('dark-mode');
            });
        }
        // Apply Accessibility Text Size
        const savedTextSize = localStorage.getItem('foodtrace_text_size');
        if (savedTextSize) {
            document.documentElement.classList.add(savedTextSize);
        }
    </script>
</head>

<body>
    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" style="display:none;"></div>

    <!-- Cleaned Up Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="index2.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-semibold ms-2">Donor</span></a>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="small text-muted d-none d-md-inline me-2"><i class="bi bi-person-circle text-success me-1"></i><?php echo htmlspecialchars($donor_fullname); ?></span>
                
                <!-- Settings Modal Trigger (Now handles preferences too) -->
                <button class="btn btn-outline-secondary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#profileModal" title="Account & Preferences">
                    <i class="bi bi-gear-fill"></i>
                </button>

                <!-- Notification Bell -->
                <div class="dropdown me-2">
                    <div class="notif-bell" data-bs-toggle="dropdown" aria-expanded="false" onclick="markNotificationsRead()">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-badge" id="notifBadgeCount"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-notif" aria-live="polite" aria-atomic="true">
                        <li class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: var(--bg-light);">
                            <h6 class="mb-0 fw-bold">Live Delivery Notifications</h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo count($my_notifications); ?></span>
                        </li>
                        <?php if (empty($my_notifications)): ?>
                            <li class="p-4 text-center text-muted small">No new activity reported yet.</li>
                        <?php else: ?>
                            <?php foreach (array_slice($my_notifications, 0, 12) as $notif): ?>
                                <li class="notif-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="notif-title"><i class="bi bi-check2-circle text-success me-1"></i><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div class="notif-time"><i class="bi bi-clock me-1"></i><?php echo date('M d, H:i:s', strtotime($notif['timestamp'])); ?></div>
                                    </div>
                                    <p class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right d-none d-md-inline me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-success mb-2 px-3 py-1 fw-semibold">Aid Dispatch Portal</span>
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($donor_fullname); ?></h2>
                    <p class="text-white-50 mb-0">Select declared deficits across sub-counties, commit aid pledges, and monitor real-time tracking delivery milestones.</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        
        <!-- Flash Message for successful donation -->
        <?php if (isset($_GET['donation']) and $_GET['donation'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                Thank you! Your food aid pledge and timestamp were recorded successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- PRG Flash Messages for Profile Update -->
        <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                <?php echo $flash_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                <?php echo $flash_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Donor Impact Telemetry -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-heart-pulse-fill"></i></div>
                    <h6>Total Aid Pledges</h6>
                    <p class="stat-number" id="stat-pledges"><?php echo htmlspecialchars($donor_total_pledges); ?></p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-info" style="background:#e0f2fe;"><i class="bi bi-truck"></i></div>
                    <h6>Active / In-Depot</h6>
                    <p class="stat-number" id="stat-active"><?php echo htmlspecialchars($donor_active_pledges); ?></p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-success" style="background:#dcfce7;"><i class="bi bi-check2-circle"></i></div>
                    <h6>Delivered to Beneficiaries</h6>
                    <p class="stat-number" id="stat-delivered"><?php echo htmlspecialchars($donor_delivered_pledges); ?></p>
                </div>
            </div>
        </div>

        <!-- Section 1: Community Deficits (Declared Needs) -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Declared Community Needs</h4>
                    <p class="text-muted small mb-0">Real-time supply deficits published by regional Area Administrators</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-input-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="needsSearchInput" class="form-control form-control-sm" placeholder="Search item or hub...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="needsTable">
                    <thead>
                        <tr>
                            <th>Commodity ID</th>
                            <th>Area Administrator</th>
                            <th>Location</th>
                            <th>Commodity</th>
                            <th>Quantity Needed</th>
                            <th>Date & Time Added</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="needsTableBody">
                        <?php
                        $sql_needs = "
                            SELECT c.Commodity_ID, a.Fullname AS Admin_Name, a.Location, c.Commodity, c.Quantity, c.Date_Added
                            FROM `commodity` c
                            JOIN `admin` a ON c.Area_Administrator = a.Administrator_ID
                            ORDER BY c.Commodity_ID DESC
                        ";
                        $res_needs = $conn->query($sql_needs);
                        $has_active_needs = false;

                        if ($res_needs and $res_needs->num_rows > 0) {
                            while ($row_n = $res_needs->fetch_assoc()) {
                                // Extract numeric value from Quantity string (e.g. "0 kg" -> 0)
                                $numeric_qty = (float) filter_var($row_n["Quantity"], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                
                                // FIX: Do not display fulfilled or negative needs
                                if ($numeric_qty <= 0) {
                                    continue;
                                }
                                
                                $has_active_needs = true;
                                echo "<tr>";
                                echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($row_n["Commodity_ID"]) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row_n["Admin_Name"]) . "</td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($row_n["Location"]) . "</td>";
                                echo "<td class='fw-semibold filter-cell'>" . htmlspecialchars($row_n["Commodity"]) . "</td>";
                                echo "<td><span class='badge bg-secondary-subtle text-secondary-emphasis'>" . htmlspecialchars($row_n["Quantity"]) . "</span></td>";
                                echo "<td class='text-muted small'>" . formatDateTimeDisplay($row_n["Date_Added"]) . "</td>";
                                echo "<td class='text-center'>
                                        <button class='btn btn-primary-custom btn-sm' onclick='openPledgeModal(" . json_encode($row_n) . ")'>
                                            Pledge Aid
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } 
                        
                        if (!$has_active_needs) {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No current deficit requests listed.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Consignment History & Amazon-Style Timelines -->
        <div class="section-card" id="myHistorySection">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">My Logistics Tracking & Passes</h4>
                    <p class="text-muted small mb-0">Track real-time supply chain milestones and generate verifiable QR handover tokens</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-input-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="historySearchInput" class="form-control form-control-sm" placeholder="Search my history...">
                    </div>
                </div>
            </div>

            <div class="row g-4" id="historyTimelineContainer">
                <?php
                $sql_history = "
                    SELECT gd.Dontation_ID, c.Commodity, gd.Quantity AS DonatedQty, c.Quantity AS CommQtyStr, 
                           gd.Date_Donated, gd.Location, gd.Status,
                           gd.Pledged_At, gd.Collected_At, gd.Delivered_At
                    FROM `goods_donated` gd
                    JOIN `commodity` c ON gd.Commodity_ID = c.Commodity_ID
                    WHERE gd.Donor_ID = ?
                    ORDER BY gd.Dontation_ID DESC
                ";
                
                $stmt_h = $conn->prepare($sql_history);
                $stmt_h->bind_param("i", $donor_id);
                $stmt_h->execute();
                $res_h = $stmt_h->get_result();

                if ($res_h and $res_h->num_rows > 0) {
                    while ($row_h = $res_h->fetch_assoc()) {
                        $unit = '';
                        if (preg_match('/^\d+(\.\d+)?\s*([a-zA-Z]+)?$/', $row_h['CommQtyStr'], $matches)) {
                            $unit = $matches[2] ?? '';
                        }

                        $status = $row_h["Status"] ?? 'Pending Pickup';
                        $pledge_time = !empty($row_h['Pledged_At']) ? $row_h['Pledged_At'] : $row_h['Date_Donated'];
                        $collect_time = !empty($row_h['Collected_At']) ? $row_h['Collected_At'] : null;
                        $deliver_time = !empty($row_h['Delivered_At']) ? $row_h['Delivered_At'] : null;

                        // Calculate Amazon Style Step CSS Classes
                        $step1_class = 'completed'; // Pledging is always completed if it's in this list
                        
                        $step2_class = '';
                        if ($status === 'Collected') $step2_class = 'active';
                        if ($status === 'Delivered') $step2_class = 'completed';

                        $step3_class = '';
                        if ($status === 'Delivered') $step3_class = 'completed';
                        ?>

                        <div class="col-lg-6 col-xl-4 tracking-card-wrapper filter-target">
                            <div class="tracking-card">
                                <div class="tracking-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark search-field"><?php echo htmlspecialchars($row_h['DonatedQty'] . ' ' . $unit . ' of ' . $row_h['Commodity']); ?></h6>
                                        <small class="text-muted search-field">Consignment #<?php echo $row_h['Dontation_ID']; ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark border search-field"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo htmlspecialchars($row_h['Location']); ?></span>
                                </div>

                                <div class="tracking-timeline">
                                    <!-- Step 1: Pledged -->
                                    <div class="timeline-step <?php echo $step1_class; ?>">
                                        <div class="timeline-icon"><i class="bi bi-box2-heart-fill"></i></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Pledge Confirmed</h6>
                                            <p class="timeline-text">Donation successfully logged in the central system.</p>
                                            <span class="timeline-date"><?php echo date('M d, Y - h:i A', strtotime($pledge_time)); ?></span>
                                        </div>
                                    </div>

                                    <!-- Step 2: Collected -->
                                    <div class="timeline-step <?php echo $step2_class; ?>">
                                        <div class="timeline-icon"><i class="bi bi-truck"></i></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title"><?php echo ($status === 'Pending Pickup') ? 'Awaiting Hub Intake' : 'Intake Verified'; ?></h6>
                                            <p class="timeline-text">
                                                <?php echo ($status === 'Pending Pickup') 
                                                    ? "Waiting for Area Admin to physically scan and receive items." 
                                                    : "Physically scanned and secured at the " . htmlspecialchars($row_h['Location']) . " depot."; ?>
                                            </p>
                                            <?php if ($collect_time): ?>
                                                <span class="timeline-date"><?php echo date('M d, Y - h:i A', strtotime($collect_time)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Step 3: Delivered -->
                                    <div class="timeline-step <?php echo $step3_class; ?>">
                                        <div class="timeline-icon"><i class="bi bi-house-heart-fill"></i></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title"><?php echo ($status === 'Delivered') ? 'Delivered to Beneficiaries' : 'Pending Final Handover'; ?></h6>
                                            <p class="timeline-text">
                                                <?php echo ($status === 'Delivered') 
                                                    ? "Successfully distributed to the community. Thank you!" 
                                                    : "Awaiting final dispatch and beneficiary handover."; ?>
                                            </p>
                                            <?php if ($deliver_time): ?>
                                                <span class="timeline-date"><?php echo date('M d, Y - h:i A', strtotime($deliver_time)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tracking-footer">
                                    <button class="btn btn-outline-success btn-sm w-100" onclick='openQrModal(<?php echo json_encode($row_h); ?>, "<?php echo htmlspecialchars($donor_fullname); ?>")'>
                                        <i class="bi bi-qr-code me-2"></i> View Verifiable Handover Pass
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                } else {
                    echo "<div class='col-12'><div class='alert alert-light border text-center p-5'><i class='bi bi-box2 text-muted fs-1 d-block mb-3'></i><h5 class='text-muted'>No donations yet.</h5><p class='text-muted small mb-0'>Your pledged consignments and tracking timelines will appear here.</p></div></div>";
                }
                $stmt_h->close();
                ?>
            </div>
        </div>

    </div>

    <!-- Account & Preferences Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-gear-fill text-success me-2"></i>Account & Preferences</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="index2.php">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address (Read Only)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled style="background-color: var(--card-border);">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($donor_fullname); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($donor_phone); ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Change Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new secure password">
                        </div>

                        <!-- Display & Accessibility Section -->
                        <hr class="my-4 border-secondary">
                        <h6 class="fw-bold mb-3"><i class="bi bi-display me-2 text-primary"></i>Display & Accessibility</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">App Theme</label><br>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" onclick="toggleTheme()">
                                    <i class="bi bi-moon-stars-fill me-2" id="themeIcon"></i> Toggle Dark/Light
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Language</label><br>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" id="btn-lang-en" class="btn btn-outline-secondary btn-sm lang-btn" onclick="changeLanguage('en')">English</button>
                                    <button type="button" id="btn-lang-sw" class="btn btn-outline-secondary btn-sm lang-btn" onclick="changeLanguage('sw')">Kiswahili</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Text Size</label><br>
                                <div class="btn-group w-100" role="group" aria-label="Text Size Accessibility controls">
                                    <button type="button" id="btn-text-default" class="btn btn-outline-secondary btn-sm fw-bold text-size-btn" onclick="changeTextSize('default')">A (Default)</button>
                                    <button type="button" id="btn-text-a11y-large" class="btn btn-outline-secondary btn-sm fw-bold text-size-btn" onclick="changeTextSize('a11y-large')">A+ (Large)</button>
                                    <button type="button" id="btn-text-a11y-xlarge" class="btn btn-outline-secondary btn-sm fw-bold text-size-btn" onclick="changeTextSize('a11y-xlarge')">A++ (Extra Large)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pledge Aid Modal WITH RULE-BASED PREDICTIVE ENGINE -->
    <div class="modal fade" id="pledgeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box2-heart-fill text-success me-2"></i>Pledge Aid Contribution</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="insertion.inc.php">
                    <input type="hidden" name="add_donation" value="1">
                    <input type="hidden" name="donor_id" value="<?php echo htmlspecialchars($donor_id); ?>">
                    <input type="hidden" name="commodity_id" id="pledgeCommId">
                    <input type="hidden" name="location" id="pledgeLocation">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Commodity</label>
                            <input type="text" id="pledgeCommName" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Sub-County Hub</label>
                            <input type="text" id="pledgeHubDisplay" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Quantity Pledged</label>
                            <div class="input-group">
                                <!-- FIX: Added max limit handled by JavaScript -->
                                <input type="number" step="any" min="1" required name="quantity" id="pledgeQtyInput" class="form-control" placeholder="Enter amount to donate" oninput="calculatePredictiveImpact()">
                                <span class="input-group-text bg-light" id="pledgeUnitDisplay">units</span>
                            </div>
                        </div>
                        
                        <!-- NEW: Rule-Based Predictive Engine Widget -->
                        <div class="p-3 mb-3 rounded-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0;">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-cpu text-success me-2 fs-5"></i>
                                <h6 class="fw-bold text-success mb-0">Rule-Based Predictive Engine</h6>
                            </div>
                            <p class="small text-muted mb-0" id="aiEstimateText">
                                Enter a quantity above to see the estimated algorithmic impact of your donation.
                            </p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pledge Date & Time (Server Timestamp)</label>
                            <input type="text" name="date" class="form-control bg-light" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Confirm & Generate QR Token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR Pass Modal with Visual 3-Stage Progress Stepper & Timestamps -->
    <div class="modal fade" id="qrPassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg text-center p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-success px-3 py-1">FoodTrace Verified</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <h4 class="fw-bold mb-1" id="qrModalTitle">Consignment #</h4>
                <p class="text-muted small mb-3">Verifiable Handover Token for Field Distribution</p>

                <!-- Visual 3-Stage Lifecycle Stepper -->
                <div class="stepper-wrapper mb-4">
                    <div class="stepper-item completed" id="step1">
                        <div class="step-counter"><i class="bi bi-check"></i></div>
                        <span>Pledged</span>
                        <span class="step-time" id="step1Time"></span>
                    </div>
                    <div class="stepper-item" id="step2">
                        <div class="step-counter">2</div>
                        <span>Collected</span>
                        <span class="step-time" id="step2Time"></span>
                    </div>
                    <div class="stepper-item" id="step3">
                        <div class="step-counter">3</div>
                        <span>Delivered</span>
                        <span class="step-time" id="step3Time"></span>
                    </div>
                </div>

                <!-- QR Container -->
                <div class="p-3 rounded-4 d-inline-block mx-auto mb-3 border" style="background-color: #ffffff;">
                    <div id="qrCodeContainer"></div>
                </div>

                <div class="text-start p-3 rounded-3 small mb-3 border" style="background-color: var(--card-border); color: var(--dark);">
                    <div class="row g-2">
                        <div class="col-6"><strong>Commodity:</strong> <span id="qrCommName"></span></div>
                        <div class="col-6"><strong>Quantity:</strong> <span id="qrQuantity"></span></div>
                        <div class="col-6"><strong>Donor:</strong> <span id="qrDonorName"></span></div>
                        <div class="col-6"><strong>Target Hub:</strong> <span id="qrHubName"></span></div>
                        <div class="col-12 mt-2"><strong>Status:</strong> <span id="qrStatusBadge" class="status-badge"></span></div>
                        <div class="col-12 border-top pt-2 mt-2" style="border-color: #cbd5e1 !important;">
                            <div><strong>Pledged At:</strong> <span id="qrPledgedTime" class="opacity-75"></span></div>
                            <div><strong>Collected At:</strong> <span id="qrCollectedTime" class="opacity-75"></span></div>
                            <div><strong>Delivered At:</strong> <span id="qrDeliveredTime" class="opacity-75"></span></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary-custom w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Pass</button>
                    <button class="btn btn-success w-100 text-white" onclick="downloadQR()"><i class="bi bi-download me-1"></i> Save Image</button>
                    <button class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer text-center">
        <div class="container">
            <p class="mb-0 small text-white-50">&copy; Food Aid Traceability System. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Native Swahili/English Translation Script -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({ pageLanguage: 'en', autoDisplay: false }, 'google_translate_element');
        }

        // Custom Toggle to bypass ugly Google UI
        function changeLanguage(lang) {
            const domain = window.location.hostname;
            if (lang === 'sw') {
                document.cookie = `googtrans=/en/sw; path=/`;
                if(domain !== 'localhost' && domain !== '127.0.0.1') document.cookie = `googtrans=/en/sw; domain=${domain}; path=/`;
            } else {
                document.cookie = `googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
                if(domain !== 'localhost' && domain !== '127.0.0.1') document.cookie = `googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=${domain}; path=/`;
            }
            window.location.reload();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark Mode Theme Logic
        const savedTheme = localStorage.getItem('foodtrace_theme');
        const themeIcon = document.getElementById('themeIcon');
        
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            if(themeIcon) themeIcon.className = 'bi bi-sun-fill me-2 text-warning';
        }

        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('foodtrace_theme', isDark ? 'dark' : 'light');
            
            const icon = document.getElementById('themeIcon');
            if(icon) {
                icon.className = isDark ? 'bi bi-sun-fill me-2 text-warning' : 'bi bi-moon-stars-fill me-2';
            }
        }

        // Dismiss notification badge on click
        function markNotificationsRead() {
            const badge = document.getElementById('notifBadgeCount');
            if (badge) badge.style.display = 'none';
        }

        // ==========================================
        // INITIALIZATION ON LOAD (Active States & Filters)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            
            // Highlight Active Language Button based on Cookie
            const langCookie = document.cookie.split('; ').find(row => row.startsWith('googtrans='));
            const btnEn = document.getElementById('btn-lang-en');
            const btnSw = document.getElementById('btn-lang-sw');
            
            if (langCookie && langCookie.includes('/en/sw')) {
                if(btnSw) { btnSw.classList.remove('btn-outline-secondary'); btnSw.classList.add('btn-primary-custom'); }
            } else {
                if(btnEn) { btnEn.classList.remove('btn-outline-secondary'); btnEn.classList.add('btn-primary-custom'); }
            }

            // Highlight Active Text Size Button based on LocalStorage
            const savedSize = localStorage.getItem('foodtrace_text_size') || 'default';
            updateTextSizeButtons(savedSize);

            // Instant Live Search Filters for Needs Table
            const needsSearch = document.getElementById('needsSearchInput');
            if (needsSearch) {
                needsSearch.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#needsTableBody tr');
                    rows.forEach(r => {
                        r.style.display = r.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }

            // Instant Live Search Filters for Tracking Cards
            const histSearch = document.getElementById('historySearchInput');
            if (histSearch) {
                histSearch.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('.tracking-card-wrapper');
                    cards.forEach(card => {
                        const text = Array.from(card.querySelectorAll('.search-field')).map(el => el.textContent).join(' ').toLowerCase();
                        card.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }
        });

        // ==========================================
        // SILENT AJAX POLLING - LIVE DATA SYNC
        // ==========================================
        setInterval(() => {
            fetch(window.location.href)
                .then(res => res.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ['stat-pledges', 'stat-active', 'stat-delivered'].forEach(id => {
                        const newEl = doc.getElementById(id);
                        const oldEl = document.getElementById(id);
                        if (newEl && oldEl) oldEl.textContent = newEl.textContent;
                    });

                    if (document.activeElement.id !== 'needsSearchInput') {
                        const newNeeds = doc.getElementById('needsTableBody');
                        const oldNeeds = document.getElementById('needsTableBody');
                        if (newNeeds && oldNeeds) oldNeeds.innerHTML = newNeeds.innerHTML;
                    }

                    if (document.activeElement.id !== 'historySearchInput') {
                        const newHistory = doc.getElementById('historyTimelineContainer');
                        const oldHistory = document.getElementById('historyTimelineContainer');
                        if (newHistory && oldHistory) oldHistory.innerHTML = newHistory.innerHTML;
                    }

                    const newDropdown = doc.querySelector('.dropdown-menu-notif');
                    if (newDropdown) {
                        document.querySelector('.dropdown-menu-notif').innerHTML = newDropdown.innerHTML;
                    }
                    
                    const oldBadge = document.getElementById('notifBadgeCount');
                    const newBadge = doc.getElementById('notifBadgeCount');
                    if (newBadge) {
                        if (oldBadge) {
                            oldBadge.outerHTML = newBadge.outerHTML;
                        } else {
                            document.querySelector('.notif-bell').insertAdjacentHTML('beforeend', newBadge.outerHTML);
                        }
                    } else if (oldBadge) {
                        oldBadge.remove();
                    }
                })
                .catch(err => console.log('Live sync paused'));
        }, 4000); 

        // Open Pledge Modal
        function openPledgeModal(comm) {
            document.getElementById('pledgeCommId').value = comm.Commodity_ID;
            document.getElementById('pledgeLocation').value = comm.Location;
            document.getElementById('pledgeCommName').value = comm.Commodity;
            document.getElementById('pledgeHubDisplay').value = comm.Location;

            const parts = comm.Quantity.split(' ');
            const unit = parts.length > 1 ? parts[parts.length - 1] : 'units';
            document.getElementById('pledgeUnitDisplay').textContent = unit;
            
            // FIX: Restrict input to the maximum required quantity
            const numericMax = parseFloat(comm.Quantity);
            const qtyInput = document.getElementById('pledgeQtyInput');
            qtyInput.max = numericMax;
            
            // Reset the Predictive Engine Widget and set placeholder on open
            qtyInput.value = '';
            qtyInput.placeholder = `Max required: ${numericMax}`;
            document.getElementById('aiEstimateText').innerHTML = "Enter a quantity above to see the estimated algorithmic impact of your donation.";

            new bootstrap.Modal(document.getElementById('pledgeModal')).show();
        }

        // Open QR Pass Modal & Update 3-Stage Progress Stepper
        function openQrModal(item, donorName) {
            document.getElementById('qrModalTitle').textContent = `Consignment #${item.Dontation_ID}`;
            document.getElementById('qrCommName').textContent = item.Commodity;
            document.getElementById('qrQuantity').textContent = item.DonatedQty;
            document.getElementById('qrDonorName').textContent = donorName;
            document.getElementById('qrHubName').textContent = item.Location;

            const badge = document.getElementById('qrStatusBadge');
            badge.textContent = item.Status;
            badge.className = 'status-badge ' + (item.Status === 'Delivered' ? 'status-delivered' : (item.Status === 'Collected' ? 'status-collected' : 'status-pending'));

            const pledgedVal = item.Pledged_At || item.Date_Donated || 'Recorded';
            const collectedVal = item.Collected_At || 'Pending Depot Scan';
            const deliveredVal = item.Delivered_At || 'Pending Final Scan';

            document.getElementById('qrPledgedTime').textContent = pledgedVal;
            document.getElementById('qrCollectedTime').textContent = collectedVal;
            document.getElementById('qrDeliveredTime').textContent = deliveredVal;

            document.getElementById('step1Time').textContent = item.Pledged_At ? item.Pledged_At.split(' ')[1] : '';
            document.getElementById('step2Time').textContent = item.Collected_At ? item.Collected_At.split(' ')[1] : '';
            document.getElementById('step3Time').textContent = item.Delivered_At ? item.Delivered_At.split(' ')[1] : '';

            const s2 = document.getElementById('step2');
            const s3 = document.getElementById('step3');

            s2.className = 'stepper-item';
            s2.querySelector('.step-counter').innerHTML = '2';
            s3.className = 'stepper-item';
            s3.querySelector('.step-counter').innerHTML = '3';

            if (item.Status === 'Collected') {
                s2.className = 'stepper-item completed';
                s2.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
            } else if (item.Status === 'Delivered') {
                s2.className = 'stepper-item completed';
                s2.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
                s3.className = 'stepper-item completed';
                s3.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
            }

            const qrContainer = document.getElementById('qrCodeContainer');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: String(item.Dontation_ID),
                width: 180,
                height: 180,
                colorDark: "#0f172a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            new bootstrap.Modal(document.getElementById('qrPassModal')).show();
        }

        function downloadQR() {
            const qrContainer = document.getElementById('qrCodeContainer');
            const img = qrContainer.querySelector('img');
            const title = document.getElementById('qrModalTitle').textContent.replace('#', '');
            const filename = 'FoodTrace_QR_Pass_' + title + '.png';
            
            if (img && img.src) {
                const link = document.createElement('a');
                link.href = img.src;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                const canvas = qrContainer.querySelector('canvas');
                if (canvas) {
                    const link = document.createElement('a');
                    link.href = canvas.toDataURL("image/png");
                    link.download = filename;
                    link.click();
                }
            }
        }

        // ==========================================
        // RULE-BASED PREDICTIVE ENGINE (ALGORITHM)
        // ==========================================
        function calculatePredictiveImpact() {
            const qtyInput = parseFloat(document.getElementById('pledgeQtyInput').value);
            const commName = document.getElementById('pledgeCommName').value.toLowerCase();
            const estimateText = document.getElementById('aiEstimateText');

            if (!qtyInput || isNaN(qtyInput)) {
                estimateText.innerHTML = "Enter a quantity above to see the estimated algorithmic impact of your donation.";
                return;
            }

            let households = 0;
            let duration = "1 week";
            let unit = document.getElementById('pledgeUnitDisplay').textContent.toLowerCase();

            // Rule-Based Logistic Scaling Algorithm
            // Assumes standard WFP ASAL ration: ~2.5kg of cereals per household per week.
            if (commName.includes('maize') || commName.includes('rice') || commName.includes('beans')) {
                if (unit.includes('kg') || unit.includes('kgs')) {
                    households = Math.floor(qtyInput / 2.5);
                } else if (unit.includes('bag') || unit.includes('bags')) {
                    // Assuming a standard 90kg bag
                    households = Math.floor((qtyInput * 90) / 2.5); 
                }
            } else if (commName.includes('oil')) {
                if (unit.includes('liter') || unit.includes('litre')) {
                    // Approx 0.5L of oil per household per week
                    households = Math.floor(qtyInput / 0.5);
                }
            } else {
                // Generic fallback for unclassified items
                households = Math.floor(qtyInput * 1.5);
                duration = "immediate relief";
            }

            if (households > 0) {
                estimateText.innerHTML = `<strong>Data Insight:</strong> Based on historical distribution metrics, this pledge will sustain approximately <strong class='text-dark'>${households} beneficiary households</strong> for ${duration} in the target sub-county.`;
            } else {
                estimateText.innerHTML = "Quantity is too low for an accurate household scale estimation.";
            }
        }

        // ==========================================
        // ACCESSIBILITY: TEXT RESIZER LOGIC
        // ==========================================
        function changeTextSize(sizeClass) {
            document.documentElement.classList.remove('a11y-large', 'a11y-xlarge');
            if (sizeClass !== 'default') {
                document.documentElement.classList.add(sizeClass);
                localStorage.setItem('foodtrace_text_size', sizeClass);
            } else {
                localStorage.removeItem('foodtrace_text_size');
            }
            updateTextSizeButtons(sizeClass);
        }

        function updateTextSizeButtons(activeSize) {
            const btns = document.querySelectorAll('.text-size-btn');
            btns.forEach(btn => {
                btn.classList.remove('btn-primary-custom');
                btn.classList.add('btn-outline-secondary');
            });
            const activeBtn = document.getElementById('btn-text-' + activeSize);
            if (activeBtn) {
                activeBtn.classList.remove('btn-outline-secondary');
                activeBtn.classList.add('btn-primary-custom');
            }
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>