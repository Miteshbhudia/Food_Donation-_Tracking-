<?php
require_once 'dbconnection.inc.php';
session_start();

// Verify System Admin authentication
if (!isset($_SESSION['adminname']) and !isset($_SESSION['Email'])) { 
    header("Location: login_page.html");
    exit();
}

$fullname = $_SESSION['adminname'] ?? 'System Administrator';
$message  = '';

$edit_mode     = false;
$admin_to_edit = null;

// ==========================================
// 1. HANDLE FORM SUBMISSIONS
// ==========================================

// Handle Update action
if (isset($_POST['update_admin'])) {
    $admin_id           = intval($_POST['admin_id'] ?? 0);
    $fullname_edit      = trim($_POST['fullname'] ?? '');
    $email_address_edit = trim($_POST['email_address'] ?? '');
    $location_edit      = trim($_POST['location'] ?? '');

    if (!empty($admin_id) and !empty($fullname_edit) and !empty($email_address_edit)) {
        $stmt = $conn->prepare("UPDATE `admin` SET `Fullname` = ?, `Email_Address` = ?, `Location` = ? WHERE `Administrator_ID` = ?");
        $stmt->bind_param("sssi", $fullname_edit, $email_address_edit, $location_edit, $admin_id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='bi bi-check-circle-fill me-2'></i>Administrator <strong>" . htmlspecialchars($fullname_edit) . "</strong> updated successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>Error updating administrator: " . htmlspecialchars($stmt->error) . "
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                        <i class='bi bi-exclamation-circle-fill me-2'></i>Please complete all required fields.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

// Handle Delete action
if (isset($_POST['dela'])) {
    $id_to_delete = intval($_POST['id3'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM `admin` WHERE `Administrator_ID` = ?");
    $stmt->bind_param("i", $id_to_delete);

    if ($stmt->execute()) {$message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='bi bi-check-circle-fill me-2'></i>Area Administrator successfully removed from system.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>Error deleting administrator: " . htmlspecialchars($stmt->error) . "
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
    $stmt->close();
}

// Check for Edit Request
if (isset($_GET['action']) and $_GET['action'] === 'edit' and isset($_GET['id'])) {
    $admin_id_to_fetch = intval($_GET['id']);

    $stmt =$conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location` FROM `admin` WHERE `Administrator_ID` = ? AND `Position` = 'Area Administrator'");
    $stmt->bind_param("i", $admin_id_to_fetch);$stmt->execute();
    $result =$stmt->get_result();

    if ($result->num_rows > 0) {$admin_to_edit = $result->fetch_assoc();$edit_mode = true;
    } else {
        $message = "<div class='alert alert-danger'>Area Administrator not found or unauthorized position.</div>";
        $edit_mode = false;
    }
    $stmt->close();
}

// ==========================================
// 2. FETCH DASHBOARD STATISTICS
// ==========================================
$donor_res =$conn->query("SELECT COUNT(*) as total FROM `donors`");
$total_donors =$donor_res->fetch_assoc()['total'] ?? 0;

$admin_res =$conn->query("SELECT COUNT(*) as total FROM `admin` WHERE `Position` = 'Area Administrator'");
$total_admins =$admin_res->fetch_assoc()['total'] ?? 0;

$donation_res =$conn->query("SELECT COUNT(*) as total FROM `goods_donated`");
$total_donations =$donation_res->fetch_assoc()['total'] ?? 0;

$commodity_res =$conn->query("SELECT COUNT(*) as total FROM `commodity`");
$total_commodities =$commodity_res->fetch_assoc()['total'] ?? 0;
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
    <!-- Bootstrap Icons v1.11.3 -->
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

        /* Tables */
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
        
        <!-- Account Provisioning Alert Banner (Displays when returning from reg_area.php) -->
        <?php if (isset($_GET['user_added'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                <strong>Account Created!</strong> A new <strong><?php echo htmlspecialchars($_GET['user_added']); ?></strong> account has been successfully provisioned.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)) echo$message; ?>

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

        <!-- Edit Form Card (Conditional) -->
        <?php if ($edit_mode &&$admin_to_edit): ?>
            <div class="section-card border-success">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square text-success me-2"></i>Edit Area Administrator</h4>
                    <a href="system_admin.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
                <form method="POST" action="system_admin.php">
                    <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin_to_edit['Administrator_ID']); ?>">

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
                            <label class="form-label small fw-semibold">Position</label>
                            <input type="text" class="form-control bg-light" name="position" value="<?php echo htmlspecialchars($admin_to_edit['Position']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Sub-County / Hub Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($admin_to_edit['Location']); ?>">
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="update_admin" class="btn btn-primary-custom">Save Changes</button>
                        <a href="system_admin.php" class="btn btn-outline-secondary">Discard</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Section 1: Area Administrator Management Table -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h4 class="fw-bold mb-1">Manage Area Administrators</h4>
                    <p class="text-muted small mb-0">Authorized field officers tasked with consignment intake and delivery verification</p>
                </div>
                <a href="reg_area.php" class="btn btn-primary-custom btn-sm"><i class="bi bi-person-plus-fill me-1"></i> Add Area Administrator</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Position</th>
                            <th>Assigned Hub</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $user_sql = "SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location` FROM `admin` WHERE `Position` = 'Area Administrator' ORDER BY `Administrator_ID` ASC";
                        $user_result = $conn->query($user_sql);

                        if ($user_result &&$user_result->num_rows > 0) {
                            while ($user_row =$user_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($user_row["Administrator_ID"]) . "</span></td>";
                                echo "<td class='fw-semibold'>" . htmlspecialchars($user_row["Fullname"]) . "</td>";
                                echo "<td>" . htmlspecialchars($user_row["Email_Address"]) . "</td>";
                                echo "<td><span class='badge bg-success-subtle text-success'>" . htmlspecialchars($user_row["Position"]) . "</span></td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . (!empty($user_row["Location"]) ? htmlspecialchars($user_row["Location"]) : "Unassigned") . "</td>";
                                echo "<td class='text-center'>
                                        <div class='d-inline-flex gap-1'>
                                            <a href='?action=edit&id=" . htmlspecialchars($user_row["Administrator_ID"]) . "' class='btn btn-outline-primary btn-sm'><i class='bi bi-pencil'></i> Edit</a>
                                            <form method='POST' action='system_admin.php' onsubmit='return confirm(\"Are you sure you want to remove this administrator?\");' class='d-inline'>
                                                <input type='hidden' name='id3' value='" . htmlspecialchars($user_row["Administrator_ID"]) . "'>
                                                <button type='submit' name='dela' class='btn btn-outline-danger btn-sm'><i class='bi bi-trash'></i> Delete</button>
                                            </form>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No Area Administrators currently registered in the database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
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