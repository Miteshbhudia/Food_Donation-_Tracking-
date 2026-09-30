<?php
require_once 'dbconnection.inc.php';
session_start();

// Verify System Admin authentication
if (!isset($_SESSION['adminname']) and !isset($_SESSION['Email'])) { 
    header("Location: login_page.html");
    exit();
}

$fullname = $_SESSION['adminname'] ?? 'System Administrator';

// Read and clear flash messages (Post-Redirect-Get Pattern)
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$edit_mode        = false;
$admin_to_edit    = null;
$edit_donor_mode  = false;
$donor_to_edit    = null;

// Determine active tab from URL (default: area_admins)
$active_tab = $_GET['tab'] ?? 'area_admins';

// ==========================================
// 1. HANDLE FORM SUBMISSIONS (PRG PATTERN)
// ==========================================

// A. Handle Administrator Update
if (isset($_POST['update_admin'])) {
    $admin_id           = intval($_POST['admin_id'] ?? 0);
    $fullname_edit      = trim($_POST['fullname'] ?? '');
    $email_address_edit = trim($_POST['email_address'] ?? '');
    $location_edit      = trim($_POST['location'] ?? '');
    $target_tab         = trim($_POST['target_tab'] ?? 'area_admins');

    if (!empty($admin_id) and !empty($fullname_edit) and !empty($email_address_edit)) {
        $stmt = $conn->prepare("UPDATE `admin` SET `Fullname` = ?, `Email_Address` = ?, `Location` = ? WHERE `Administrator_ID` = ?");
        $stmt->bind_param("sssi", $fullname_edit, $email_address_edit, $location_edit, $admin_id);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Administrator profile <strong>" . htmlspecialchars($fullname_edit) . "</strong> updated successfully!";
        } else {
            $_SESSION['flash_error'] = "Error updating administrator: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['flash_error'] = "Please complete all required fields.";
    }
    header("Location: system_admin.php?tab=" . urlencode($target_tab));
    exit();
}

// B. Handle Donor Update
if (isset($_POST['update_donor'])) {
    $donor_id           = intval($_POST['donor_id'] ?? 0);
    $fullname_edit      = trim($_POST['fullname'] ?? '');
    $email_address_edit = trim($_POST['email_address'] ?? '');
    $phone_edit         = trim($_POST['phone_number'] ?? '');

    if (!empty($donor_id) and !empty($fullname_edit) and !empty($email_address_edit)) {
        $stmt = $conn->prepare("UPDATE `donors` SET `Fullname` = ?, `Email_Address` = ?, `Phone_Number` = ? WHERE `Donor_ID` = ?");
        $stmt->bind_param("sssi", $fullname_edit, $email_address_edit, $phone_edit, $donor_id);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Donor profile <strong>" . htmlspecialchars($fullname_edit) . "</strong> updated successfully!";
        } else {
            $_SESSION['flash_error'] = "Error updating donor: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['flash_error'] = "Please complete all required fields.";
    }
    header("Location: system_admin.php?tab=donors");
    exit();
}

// C. Handle Administrator Deletion
if (isset($_POST['dela'])) {
    $id_to_delete = intval($_POST['id3'] ?? 0);
    $del_tab      = trim($_POST['from_tab'] ?? 'area_admins');

    $stmt = $conn->prepare("DELETE FROM `admin` WHERE `Administrator_ID` = ?");
    $stmt->bind_param("i", $id_to_delete);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Administrator successfully removed from system.";
    } else {
        $_SESSION['flash_error'] = "Error deleting administrator: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    header("Location: system_admin.php?tab=" . urlencode($del_tab));
    exit();
}

// D. Handle Donor Deletion
if (isset($_POST['del_donor'])) {
    $donor_id_to_del = intval($_POST['donor_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM `donors` WHERE `Donor_ID` = ?");
    $stmt->bind_param("i", $donor_id_to_del);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Donor account successfully removed.";
    } else {
        $_SESSION['flash_error'] = "Error deleting donor: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    header("Location: system_admin.php?tab=donors");
    exit();
}

// Check for Administrator Edit Request
if (isset($_GET['action']) and $_GET['action'] === 'edit' and isset($_GET['id'])) {
    $admin_id_to_fetch = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location` FROM `admin` WHERE `Administrator_ID` = ?");
    $stmt->bind_param("i", $admin_id_to_fetch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin_to_edit = $result->fetch_assoc();
        $edit_mode = true;
    } else {
        $_SESSION['flash_error'] = "Administrator account not found.";
    }
    $stmt->close();
}

// Check for Donor Edit Request
if (isset($_GET['action']) and $_GET['action'] === 'edit_donor' and isset($_GET['id'])) {
    $donor_id_to_fetch = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT `Donor_ID`, `Fullname`, `Email_Address`, `Phone_Number` FROM `donors` WHERE `Donor_ID` = ?");
    $stmt->bind_param("i", $donor_id_to_fetch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $donor_to_edit = $result->fetch_assoc();
        $edit_donor_mode = true;
    } else {
        $_SESSION['flash_error'] = "Donor profile not found.";
    }
    $stmt->close();
}

// ==========================================
// 2. FETCH DASHBOARD STATISTICS
// ==========================================
$donor_res = $conn->query("SELECT COUNT(*) as total FROM `donors`");
$total_donors = $donor_res->fetch_assoc()['total'] ?? 0;

$admin_res = $conn->query("SELECT COUNT(*) as total FROM `admin` WHERE `Position` = 'Area Administrator'");
$total_admins = $admin_res->fetch_assoc()['total'] ?? 0;

$sys_admin_res = $conn->query("SELECT COUNT(*) as total FROM `admin` WHERE `Position` = 'System Administrator'");
$total_sys_admins = $sys_admin_res->fetch_assoc()['total'] ?? 0;

$donation_res = $conn->query("SELECT COUNT(*) as total FROM `goods_donated`");
$total_donations = $donation_res->fetch_assoc()['total'] ?? 0;

$commodity_res = $conn->query("SELECT COUNT(*) as total FROM `commodity`");
$total_commodities = $commodity_res->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>System Administration - Food Aid Traceability System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #dcfce7;
            --dark: #0f172a;
            --gray-body: #64748b;
            --bg-light: #f8fafc;
            --card-border: #e2e8f0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--dark);
            min-height: 100vh;
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--card-border);
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
            padding: 3.5rem 0;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .section-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04);
            padding: 2.25rem;
            margin-bottom: 2.5rem;
            scroll-margin-top: 100px;
        }

        /* Stat Cards */
        .stat-widget {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .stat-widget:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .stat-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background-color: var(--primary-subtle);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }
        .stat-widget h6 {
            font-size: 0.85rem;
            color: var(--gray-body);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 0.35rem;
        }
        .stat-widget .stat-number {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0;
        }

        /* Tabs & Modern Controls */
        .nav-tabs {
            border-bottom: 2px solid var(--card-border);
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: var(--gray-body);
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.25rem;
            transition: all 0.2s ease;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-hover);
        }
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            background: transparent;
        }

        .table thead th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--card-border);
            padding: 0.9rem 1rem;
        }
        .table tbody td {
            vertical-align: middle;
            font-size: 0.92rem;
            padding: 0.9rem 1rem;
            color: #1e293b;
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
            background-color: #ffffff;
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

        /* Status Badges */
        .status-badge {
            font-size: 0.775rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-collected { background-color: #e0f2fe; color: #0369a1; }
        .status-delivered { background-color: #dcfce7; color: #166534; }

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
        .search-input-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        .site-footer {
            background-color: #0b1120;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 3.5rem 0 1.5rem 0;
            margin-top: 5rem;
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="system_admin.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-semibold ms-2">System Admin</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-shield-lock-fill text-success me-1"></i><?php echo htmlspecialchars($fullname); ?></span>
                <a href="homepage.html" class="btn btn-outline-secondary btn-sm">Home</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <header class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-success mb-2 px-3 py-2 fw-semibold">Central Administrative Console</span>
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($fullname); ?></h2>
                    <p class="text-white-50 mb-0">High-level telemetry, regional administrator governance, and inventory auditing across all distribution zones.</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        
        <!-- Flash Messages (PRG Pattern) -->
        <?php if ($flash_success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                <?php echo $flash_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($flash_error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                <?php echo $flash_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Key Metrics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                    <h6>Total Donors</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($total_donors); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-shield-check"></i></div>
                    <h6>Area Administrators</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($total_admins); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-box-seam-fill"></i></div>
                    <h6>Total Donations</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($total_donations); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-card-checklist"></i></div>
                    <h6>Total Needs Listed</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($total_commodities); ?></p>
                </div>
            </div>
        </div>

        <!-- Edit Administrator Card (Conditional) -->
        <?php if ($edit_mode && $admin_to_edit): ?>
            <?php 
                $admin_tab_target = ($admin_to_edit['Position'] === 'System Administrator') ? 'system_admins' : 'area_admins';
            ?>
            <div class="section-card border-success shadow-lg" id="editSection">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square text-success me-2"></i>Edit <?php echo htmlspecialchars($admin_to_edit['Position']); ?>: <span class="text-success"><?php echo htmlspecialchars($admin_to_edit['Fullname']); ?></span>
                    </h4>
                    <a href="system_admin.php?tab=<?php echo urlencode($admin_tab_target); ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
                <form method="POST" action="system_admin.php">
                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin_to_edit['Administrator_ID']); ?>">
                    <input type="hidden" name="target_tab" value="<?php echo htmlspecialchars($admin_tab_target); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($admin_to_edit['Fullname']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email_address" value="<?php echo htmlspecialchars($admin_to_edit['Email_Address']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Position / Role</label>
                            <input type="text" class="form-control bg-light" name="position" value="<?php echo htmlspecialchars($admin_to_edit['Position']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Sub-County / Hub Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($admin_to_edit['Location']); ?>">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="update_admin" class="btn btn-primary-custom">Save Changes</button>
                        <a href="system_admin.php?tab=<?php echo urlencode($admin_tab_target); ?>" class="btn btn-outline-secondary">Discard</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Edit Donor Card (Conditional) -->
        <?php if ($edit_donor_mode && $donor_to_edit): ?>
            <div class="section-card border-primary shadow-lg" id="editSection">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">
                        <i class="bi bi-person-gear text-primary me-2"></i>Edit Donor Profile: <span class="text-primary"><?php echo htmlspecialchars($donor_to_edit['Fullname']); ?></span>
                    </h4>
                    <a href="system_admin.php?tab=donors" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
                <form method="POST" action="system_admin.php">
                    <input type="hidden" name="donor_id" value="<?php echo htmlspecialchars($donor_to_edit['Donor_ID']); ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($donor_to_edit['Fullname']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email_address" value="<?php echo htmlspecialchars($donor_to_edit['Email_Address']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($donor_to_edit['Phone_Number']); ?>">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="update_donor" class="btn btn-primary-custom">Save Changes</button>
                        <a href="system_admin.php?tab=donors" class="btn btn-outline-secondary">Discard</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Section 1: User & Staff Governance (Separated Tabs with Deep Linking) -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">System Accounts & User Governance</h4>
                    <p class="text-muted small mb-0">Manage field depot officers, central administration accounts, and registered donor profiles</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Instant Live Filter Input -->
                    <div class="search-input-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="accountSearchInput" class="form-control form-control-sm" placeholder="Search accounts...">
                    </div>
                    <a href="reg_area.php" class="btn btn-primary-custom btn-sm text-nowrap"><i class="bi bi-person-plus-fill me-1"></i> Provision New Account</a>
                </div>
            </div>

            <!-- Tabs Navigation with Dynamic Count Badges -->
            <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($active_tab === 'area_admins') ? 'active' : ''; ?>" id="area-admins-tab" data-bs-toggle="tab" data-bs-target="#areaAdminsPane" type="button" role="tab" onclick="syncTabState('area_admins')">
                        <i class="bi bi-geo-alt-fill text-success me-1"></i> Area Administrators 
                        <span class="badge bg-success-subtle text-success ms-1 rounded-pill"><?php echo htmlspecialchars($total_admins); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($active_tab === 'system_admins') ? 'active' : ''; ?>" id="system-admins-tab" data-bs-toggle="tab" data-bs-target="#systemAdminsPane" type="button" role="tab" onclick="syncTabState('system_admins')">
                        <i class="bi bi-shield-lock-fill text-dark me-1"></i> System Administrators 
                        <span class="badge bg-secondary-subtle text-secondary ms-1 rounded-pill"><?php echo htmlspecialchars($total_sys_admins); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($active_tab === 'donors') ? 'active' : ''; ?>" id="donors-tab" data-bs-toggle="tab" data-bs-target="#donorsPane" type="button" role="tab" onclick="syncTabState('donors')">
                        <i class="bi bi-people-fill text-primary me-1"></i> Registered Donors 
                        <span class="badge bg-primary-subtle text-primary ms-1 rounded-pill"><?php echo htmlspecialchars($total_donors); ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="userTabsContent">
                
                <!-- Tab 1: Area Administrators -->
                <div class="tab-pane fade <?php echo ($active_tab === 'area_admins') ? 'show active' : ''; ?>" id="areaAdminsPane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-filterable">
                            <thead>
                                <tr>
                                    <th>Admin ID</th>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>Assigned Hub</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $area_sql = "SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Location` FROM `admin` WHERE `Position` = 'Area Administrator' ORDER BY `Administrator_ID` ASC";
                                $area_res = $conn->query($area_sql);

                                if ($area_res &&$area_res->num_rows > 0) {
                                    while ($a_row =$area_res->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($a_row["Administrator_ID"]) . "</span></td>";
                                        echo "<td class='fw-semibold filter-target'>" . htmlspecialchars($a_row["Fullname"]) . "</td>";
                                        echo "<td class='filter-target'>" . htmlspecialchars($a_row["Email_Address"]) . "</td>";
                                        echo "<td class='filter-target'><i class='bi bi-geo-alt text-success me-1'></i>" . (!empty($a_row["Location"]) ? htmlspecialchars($a_row["Location"]) : "Unassigned") . "</td>";
                                        echo "<td class='text-center'>
                                                <div class='d-inline-flex gap-1'>
                                                    <a href='?action=edit&id=" . htmlspecialchars($a_row["Administrator_ID"]) . "&tab=area_admins#editSection' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i> Edit</a>
                                                    <form method='POST' action='system_admin.php' onsubmit='return confirm(\"Are you sure you want to remove this Area Administrator?\");' class='d-inline'>
                                                        <input type='hidden' name='id3' value='" . htmlspecialchars($a_row["Administrator_ID"]) . "'>
                                                        <input type='hidden' name='from_tab' value='area_admins'>
                                                        <button type='submit' name='dela' class='btn btn-outline-danger btn-sm'><i class='bi bi-trash'></i> Delete</button>
                                                    </form>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No Area Administrators currently registered in the database.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: System Administrators -->
                <div class="tab-pane fade <?php echo ($active_tab === 'system_admins') ? 'show active' : ''; ?>" id="systemAdminsPane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-filterable">
                            <thead>
                                <tr>
                                    <th>Admin ID</th>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>HQ Office</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sys_sql = "SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Location` FROM `admin` WHERE `Position` = 'System Administrator' ORDER BY `Administrator_ID` ASC";
                                $sys_res = $conn->query($sys_sql);

                                if ($sys_res &&$sys_res->num_rows > 0) {
                                    while ($s_row =$sys_res->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($s_row["Administrator_ID"]) . "</span></td>";
                                        echo "<td class='fw-semibold filter-target'>" . htmlspecialchars($s_row["Fullname"]) . "</td>";
                                        echo "<td class='filter-target'>" . htmlspecialchars($s_row["Email_Address"]) . "</td>";
                                        echo "<td class='filter-target'><i class='bi bi-building text-dark me-1'></i>" . (!empty($s_row["Location"]) ? htmlspecialchars($s_row["Location"]) : "HQ") . "</td>";
                                        echo "<td class='text-center'>
                                                <div class='d-inline-flex gap-1'>
                                                    <a href='?action=edit&id=" . htmlspecialchars($s_row["Administrator_ID"]) . "&tab=system_admins#editSection' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i> Edit</a>
                                                    <form method='POST' action='system_admin.php' onsubmit='return confirm(\"Are you sure you want to remove this System Administrator?\");' class='d-inline'>
                                                        <input type='hidden' name='id3' value='" . htmlspecialchars($s_row["Administrator_ID"]) . "'>
                                                        <input type='hidden' name='from_tab' value='system_admins'>
                                                        <button type='submit' name='dela' class='btn btn-outline-danger btn-sm'><i class='bi bi-trash'></i> Delete</button>
                                                    </form>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No additional System Administrators registered.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Registered Donors -->
                <div class="tab-pane fade <?php echo ($active_tab === 'donors') ? 'show active' : ''; ?>" id="donorsPane" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover table-filterable">
                            <thead>
                                <tr>
                                    <th>Donor ID</th>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>Phone Number</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $donor_list_sql = "SELECT `Donor_ID`, `Fullname`, `Email_Address`, `Phone_Number` FROM `donors` ORDER BY `Donor_ID` ASC";
                                $donor_list_res = $conn->query($donor_list_sql);

                                if ($donor_list_res &&$donor_list_res->num_rows > 0) {
                                    while ($d_row =$donor_list_res->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($d_row["Donor_ID"]) . "</span></td>";
                                        echo "<td class='fw-semibold filter-target'>" . htmlspecialchars($d_row["Fullname"]) . "</td>";
                                        echo "<td class='filter-target'>" . htmlspecialchars($d_row["Email_Address"]) . "</td>";
                                        echo "<td class='filter-target'><i class='bi bi-telephone text-muted me-1'></i>" . htmlspecialchars($d_row["Phone_Number"]) . "</td>";
                                        echo "<td class='text-center'>
                                                <div class='d-inline-flex gap-1'>
                                                    <a href='?action=edit_donor&id=" . htmlspecialchars($d_row["Donor_ID"]) . "&tab=donors#editSection' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i> Edit</a>
                                                    <form method='POST' action='system_admin.php' onsubmit='return confirm(\"Are you sure you want to remove this donor account?\");' class='d-inline'>
                                                        <input type='hidden' name='donor_id' value='" . htmlspecialchars($d_row["Donor_ID"]) . "'>
                                                        <button type='submit' name='del_donor' class='btn btn-outline-danger btn-sm'><i class='bi bi-trash'></i> Delete</button>
                                                    </form>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No donors currently registered in the database.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Section 2: Global Consignment & Audit Telemetry Table -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h4 class="fw-bold mb-1">Global Consignment Audit & Traceability Feed</h4>
                    <p class="text-muted small mb-0">System-wide monitoring of aid consignments across all sub-counties and distribution hubs</p>
                </div>
                <button onclick="printAuditReport()" class="btn btn-print btn-sm"><i class="bi bi-printer me-1"></i> Export Audit Report</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="auditTable">
                    <thead>
                        <tr>
                            <th>Consignment ID</th>
                            <th>Donor Name</th>
                            <th>Commodity</th>
                            <th>Quantity Pledged</th>
                            <th>Pledge Date</th>
                            <th>Target Hub</th>
                            <th>Current Lifecycle Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $audit_sql = "
                            SELECT gd.Dontation_ID, d.Fullname AS DonorName, c.Commodity, gd.Quantity AS DonatedQty, 
                                   c.Quantity AS CommQtyStr, gd.Date_Donated, gd.Location, gd.Status
                            FROM goods_donated gd
                            LEFT JOIN donors d ON gd.Donor_ID = d.Donor_ID
                            LEFT JOIN commodity c ON gd.Commodity_ID = c.Commodity_ID
                            ORDER BY gd.Dontation_ID DESC
                        ";
                        $audit_res = $conn->query($audit_sql);

                        if ($audit_res &&$audit_res->num_rows > 0) {
                            while ($a_row = $audit_res->fetch_assoc()) {$unit = '';
                                if (preg_match('/^\d+(\.\d+)?\s*([a-zA-Z]+)?$/', $a_row['CommQtyStr'] ?? '',$matches)) {
                                    $unit =$matches[2] ?? '';
                                }

                                $status = $a_row['Status'] ?? 'Pending Pickup';$badge_class = 'status-pending';
                                if ($status === 'Collected')$badge_class = 'status-collected';
                                if ($status === 'Delivered')$badge_class = 'status-delivered';

                                echo "<tr>";
                                echo "<td><strong>#" . htmlspecialchars($a_row['Dontation_ID']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($a_row['DonorName'] ?? 'Anonymous') . "</td>";
                                echo "<td class='fw-semibold'>" . htmlspecialchars($a_row['Commodity'] ?? 'Relief Item') . "</td>";
                                echo "<td>" . htmlspecialchars($a_row['DonatedQty']) . " " . htmlspecialchars($unit) . "</td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($a_row['Date_Donated']) . "</td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($a_row['Location']) . "</td>";
                                echo "<td><span class='status-badge {$badge_class}'>{$status}</span></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No consignment activity recorded yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="site-footer text-center">
        <div class="container">
            <p class="mb-0 small text-white-50">&copy; Food Aid Traceability System. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update URL state without page reload when user changes tabs
        function syncTabState(tabName) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            url.searchParams.delete('action');
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url.toString());
        }

        // Automatic smooth scroll to the edit card on load
        document.addEventListener('DOMContentLoaded', function() {
            const editSection = document.getElementById('editSection');
            if (editSection) {
                editSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Real-Time Table Search Filter
            const searchInput = document.getElementById('accountSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const tables = document.querySelectorAll('.table-filterable tbody');

                    tables.forEach(tbody => {
                        const rows = tbody.querySelectorAll('tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(filter) ? '' : 'none';
                        });
                    });
                });
            }
        });

        // Print Report
        function printAuditReport() {
            const tableHtml = document.getElementById("auditTable").outerHTML;
            const printWin = window.open("", "", "height=700,width=950");
            printWin.document.write(`
                <html>
                <head>
                    <title>Food Aid Traceability - National Audit Telemetry Report</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: sans-serif; padding: 30px; }
                        h2, p { text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin-top: 25px; font-size: 13px; }
                        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
                        th { background-color: #16a34a; color: white; }
                    </style>
                </head>
                <body>
                    <h2>Food Aid Traceability System</h2>
                    <p class="text-muted">Consolidated Consignment Audit & Milestone Log &bull; Generated on: ${new Date().toLocaleString()}</p>
                    ${tableHtml}
                </body>
                </html>
            `);
            printWin.document.close();
            printWin.print();
            printWin.close();
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>