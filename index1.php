<?php
session_start();
require_once 'dbconnection.inc.php';

// Verify Area Admin authentication
$email = $_SESSION['Email1'] ?? $_SESSION['Email'] ?? null;
if (!$email) {
    header("Location: login_page.html");
    exit();
}

$stmt = $conn->prepare("SELECT Fullname, Location, Administrator_ID FROM `admin` WHERE `Email_Address` = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$query_result = $stmt->get_result();
$row = $query_result->fetch_assoc();
$stmt->close();

if ($row) {
    $admin_fullname = $row['Fullname'];
    $admin_location = $row['Location'];
    $admin_id       = $row['Administrator_ID'];
} else {
    session_unset();
    session_destroy();
    header("Location: login_page.html");
    exit();
}

// PRG Flash Messages
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Handle manual dropdown status override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $donation_id = intval($_POST['donation_id'] ?? 0);
    $new_status  = trim($_POST['status'] ?? '');

    $allowed = ['Pending Pickup', 'Collected', 'Delivered'];
    if (in_array($new_status, $allowed) && $donation_id > 0) {
        $stmt_upd = $conn->prepare("UPDATE `goods_donated` SET `Status` = ? WHERE `Dontation_ID` = ?");
        $stmt_upd->bind_param("si", $new_status, $donation_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $_SESSION['flash_success'] = "Consignment #{$donation_id} status updated to '{$new_status}' successfully.";
    } else {
        $_SESSION['flash_error'] = "Invalid status update payload.";
    }
    header("Location: index1.php");
    exit();
}

// Hub Telemetry Metrics
$stmt_m1 = $conn->prepare("SELECT COUNT(*) AS total FROM `commodity` WHERE `Area_Administrator` = ?");
$stmt_m1->bind_param("i", $admin_id);
$stmt_m1->execute();
$stat_needs = $stmt_m1->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_m1->close();

$stat_pending = $conn->query("SELECT COUNT(*) AS total FROM `goods_donated` WHERE `Location` LIKE '%$admin_location%' AND `Status` = 'Pending Pickup'")->fetch_assoc()['total'] ?? 0;
$stat_collected = $conn->query("SELECT COUNT(*) AS total FROM `goods_donated` WHERE `Location` LIKE '%$admin_location%' AND `Status` = 'Collected'")->fetch_assoc()['total'] ?? 0;
$stat_delivered = $conn->query("SELECT COUNT(*) AS total FROM `goods_donated` WHERE `Location` LIKE '%$admin_location%' AND `Status` = 'Delivered'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Area Admin Portal - Food Aid Traceability System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- QR Scanner CDN -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

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
            padding: 3.25rem 0;
            margin-bottom: 2.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .section-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04);
            padding: 2rem;
            margin-bottom: 2.25rem;
        }

        /* Telemetry Cards */
        .stat-widget {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 1.5rem 1.25rem;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
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

        /* Status Filter Chips */
        .filter-chip {
            border: 1px solid var(--card-border);
            background: #ffffff;
            color: var(--gray-body);
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-chip:hover, .filter-chip.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.775rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            display: inline-block;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-collected { background-color: #e0f2fe; color: #0369a1; }
        .status-delivered { background-color: #dcfce7; color: #166534; }

        /* Camera Viewfinder */
        #modal-qr-reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 2px dashed #cbd5e1;
            background-color: #f8fafc;
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

        .site-footer {
            background-color: #0b1120;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 3rem 0 1.5rem 0;
            margin-top: 5rem;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="index1.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-semibold ms-2">Area Administrator</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-geo-alt-fill text-success me-1"></i><?php echo htmlspecialchars($admin_location); ?> Regional Hub</span>
                <a href="homepage.html" class="btn btn-outline-secondary btn-sm">Home</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-success mb-2 px-3 py-1 fw-semibold">Regional Operations Center</span>
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($admin_fullname); ?></h2>
                    <p class="text-white-50 mb-0">Operational Zone: <strong class="text-white"><?php echo htmlspecialchars($admin_location); ?> Hub</strong>. Issue commodity supply requests and perform live optical QR intakes.</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        
        <!-- Flash Notifications -->
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

        <!-- Hub Telemetry Metric Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-card-checklist"></i></div>
                    <h6>Active Area Needs</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($stat_needs); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-warning" style="background:#fef3c7;"><i class="bi bi-hourglass-split"></i></div>
                    <h6>Pending Pickups</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($stat_pending); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-info" style="background:#e0f2fe;"><i class="bi bi-box-seam-fill"></i></div>
                    <h6>Depot Intakes (Collected)</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($stat_collected); ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-success" style="background:#dcfce7;"><i class="bi bi-check-circle-fill"></i></div>
                    <h6>Beneficiaries Reached</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($stat_delivered); ?></p>
                </div>
            </div>
        </div>

        <!-- Section 1: Declared Commodities in Hub -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Declared Commodities in Your Area</h4>
                    <p class="text-muted small mb-0">Active supply requests listed for <?php echo htmlspecialchars($admin_location); ?></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-input-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="commSearchInput" class="form-control form-control-sm" placeholder="Filter commodities...">
                    </div>
                    <a href="commodity.php" class="btn btn-primary-custom btn-sm text-nowrap"><i class="bi bi-plus-circle me-1"></i> Add Commodity</a>
                    <button onclick="printTableData('printTable1', 'Commodities in Area')" class="btn btn-print btn-sm"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="printTable1" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Commodity ID</th>
                            <th>Admin ID</th>
                            <th>Location</th>
                            <th>Commodity</th>
                            <th>Quantity Needed</th>
                            <th>Date Added</th>
                        </tr>
                    </thead>
                    <tbody id="commTableBody">
                        <?php
                        $stmt_c = $conn->prepare("
                            SELECT c.Commodity_ID, c.Area_Administrator, a.Location, c.Commodity, c.Quantity, c.Date_Added
                            FROM `commodity` c
                            JOIN `admin` a ON c.Area_Administrator = a.Administrator_ID
                            WHERE c.Area_Administrator = ?
                            ORDER BY c.Commodity_ID DESC
                        ");
                        $stmt_c->bind_param("i", $admin_id);
                        $stmt_c->execute();
                        $res_c = $stmt_c->get_result();

                        if ($res_c && $res_c->num_rows > 0) {
                            while ($row_c = $res_c->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($row_c["Commodity_ID"]) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row_c["Area_Administrator"]) . "</td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($row_c["Location"]) . "</td>";
                                echo "<td class='fw-semibold filter-cell'>" . htmlspecialchars($row_c["Commodity"]) . "</td>";
                                echo "<td><span class='badge bg-secondary-subtle text-secondary-emphasis'>" . htmlspecialchars($row_c["Quantity"]) . "</span></td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_c["Date_Added"]) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No commodities currently declared for this regional hub.</td></tr>";
                        }
                        $stmt_c->close();
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <form method="POST" action="delete.php" class="d-inline-flex flex-wrap align-items-center justify-content-center gap-2">
                    <span class="small text-muted">To remove a fulfilled commodity need, enter ID:</span>
                    <input type="number" required name="id1" class="form-control form-control-sm" placeholder="Commodity ID" style="max-width: 140px;">
                    <button type="submit" name="delc1" class="btn btn-outline-danger btn-sm">Delete Commodity</button>
                </form>
            </div>
        </div>

        <!-- Section 2: Consignment Inspection & Smart Auto-Progression -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">List of Goods Donated (Consignments)</h4>
                    <p class="text-muted small mb-0">Verify intake and final delivery automatically using live camera QR inspection</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary-custom btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#qrScanModal">
                        <i class="bi bi-camera me-1"></i> Scan Consignment QR
                    </button>
                    <button onclick="printTableData('printTable2', 'List of Goods Donated')" class="btn btn-print btn-sm">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>

            <!-- Modern Filter Controls: Search Bar + Status Filter Chips -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div class="d-flex flex-wrap gap-1">
                    <button class="filter-chip active" onclick="filterConsignments('all', this)">All</button>
                    <button class="filter-chip" onclick="filterConsignments('Pending Pickup', this)">Pending Pickup</button>
                    <button class="filter-chip" onclick="filterConsignments('Collected', this)">Collected (In Depot)</button>
                    <button class="filter-chip" onclick="filterConsignments('Delivered', this)">Delivered</button>
                </div>
                <div class="search-input-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="consignSearchInput" class="form-control form-control-sm" placeholder="Search consignments...">
                </div>
            </div>

            <div class="table-responsive">
                <table id="printTable2" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Donation ID</th>
                            <th>Commodity</th>
                            <th>Donor ID</th>
                            <th>Quantity Pledged</th>
                            <th>Pledge Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Manual Override</th>
                        </tr>
                    </thead>
                    <tbody id="consignTableBody">
                        <?php
                        $sql_donations = "
                            SELECT gd.Dontation_ID, gd.Commodity_ID, gd.Donor_ID, c.Commodity AS Commodity_Name, 
                                   gd.Quantity AS Donated_Qty, c.Quantity AS Commodity_Qty_Str, gd.Date_Donated, gd.Location, gd.Status
                            FROM `goods_donated` gd
                            JOIN `commodity` c ON gd.Commodity_ID = c.Commodity_ID
                            ORDER BY gd.Dontation_ID DESC
                        ";
                        $res_donations = $conn->query($sql_donations);

                        if ($res_donations && $res_donations->num_rows > 0) {
                            while ($row_d = $res_donations->fetch_assoc()) {
                                $unit = '';
                                if (preg_match('/^\d+(\.\d+)?\s*([a-zA-Z]+)?$/', $row_d['Commodity_Qty_Str'], $matches)) {
                                    $unit = $matches[2] ?? '';
                                }

                                $status = $row_d["Status"];
                                $badge_class = "status-pending";
                                if ($status === "Collected") $badge_class = "status-collected";
                                if ($status === "Delivered") $badge_class = "status-delivered";

                                echo "<tr id='row-donation-" . htmlspecialchars($row_d["Dontation_ID"]) . "' data-status='" . htmlspecialchars($status) . "'>";
                                echo "<td><strong>#" . htmlspecialchars($row_d["Dontation_ID"]) . "</strong></td>";
                                echo "<td class='fw-semibold consign-cell'>" . htmlspecialchars($row_d["Commodity_Name"]) . "</td>";
                                echo "<td class='consign-cell'>Donor #" . htmlspecialchars($row_d["Donor_ID"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row_d["Donated_Qty"]) . " " . htmlspecialchars($unit) . "</td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_d["Date_Donated"]) . "</td>";
                                echo "<td class='consign-cell'>" . htmlspecialchars($row_d["Location"]) . "</td>";
                                echo "<td><span class='status-badge {$badge_class}' id='badge-status-" . htmlspecialchars($row_d["Dontation_ID"]) . "'>{$status}</span></td>";
                                
                                echo "<td>
                                        <form method='POST' action='index1.php' class='d-flex align-items-center gap-1'>
                                            <input type='hidden' name='donation_id' value='" . htmlspecialchars($row_d["Dontation_ID"]) . "'>
                                            <select name='status' class='form-select form-select-sm' style='max-width: 135px;' id='select-status-" . htmlspecialchars($row_d["Dontation_ID"]) . "'>
                                                <option value='Pending Pickup'" . ($status === 'Pending Pickup' ? ' selected' : '') . ">Pending Pickup</option>
                                                <option value='Collected'" . ($status === 'Collected' ? ' selected' : '') . ">Collected</option>
                                                <option value='Delivered'" . ($status === 'Delivered' ? ' selected' : '') . ">Delivered</option>
                                            </select>
                                            <button type='submit' name='update_status' class='btn btn-outline-secondary btn-sm'>Update</button>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No donations recorded in system yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Live QR Camera Scanner Modal -->
    <div class="modal fade" id="qrScanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-camera-fill text-success me-2"></i>Consignment QR Checkpoint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopScannerModal()"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <div class="alert alert-light border small py-2 mb-3 d-flex align-items-center">
                        <i class="bi bi-cpu text-success fs-4 me-2"></i>
                        <div>
                            <strong>Smart Auto-Progression Active:</strong><br>
                            <span class="text-muted">1st scan marks <b>Collected</b> &bull; 2nd scan marks <b>Delivered</b></span>
                        </div>
                    </div>

                    <div id="modal-qr-reader" class="mb-3 p-3 text-center">
                        <i class="bi bi-camera fs-2 text-muted d-block mb-1"></i>
                        <span class="small text-muted">Click below to activate device optical sensor</span>
                    </div>

                    <div id="scanAlert" class="alert py-2 small d-none mb-3"></div>

                    <div class="d-flex gap-2">
                        <button id="btnModalStartScan" class="btn btn-primary-custom w-100 py-2"><i class="bi bi-camera-fill me-1"></i> Start Camera</button>
                        <button id="btnModalStopScan" class="btn btn-outline-secondary w-100 py-2 d-none" onclick="stopScannerModal()"><i class="bi bi-stop-circle me-1"></i> Stop Camera</button>
                    </div>

                    <!-- Manual Verification Failsafe -->
                    <div class="mt-4 pt-3 border-top">
                        <label class="small text-muted d-block mb-2">Manual Consignment Verification (Failsafe):</label>
                        <div class="input-group">
                            <input type="number" id="manualConsignmentId" class="form-control form-control-sm" placeholder="Consignment ID (e.g. 43)">
                            <button class="btn btn-dark btn-sm px-3" onclick="submitManualConsignment()">Verify ID</button>
                        </div>
                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Synthesizer Audio + Native Haptic Vibration Feedback
        function triggerScanFeedback(isSuccess) {
            // 1. Audio Chime (Web Audio API)
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                if (isSuccess) {
                    osc.type = "sine";
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.15);
                    gain.gain.setValueAtTime(0.2, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.2);
                } else {
                    osc.type = "sawtooth";
                    osc.frequency.setValueAtTime(220, ctx.currentTime);
                    gain.gain.setValueAtTime(0.2, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.3);
                }
            } catch(e) {}

            // 2. Tactile Haptic Vibration for Mobile Devices
            if (window.navigator && window.navigator.vibrate) {
                if (isSuccess) {
                    window.navigator.vibrate([100, 50, 100]); // double pulse
                } else {
                    window.navigator.vibrate(250); // long buzz
                }
            }
        }

        // Live Table Filters
        document.addEventListener('DOMContentLoaded', function() {
            // Commodities search
            const commSearch = document.getElementById('commSearchInput');
            if (commSearch) {
                commSearch.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#commTableBody tr');
                    rows.forEach(r => {
                        r.style.display = r.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }

            // Consignments search
            const consignSearch = document.getElementById('consignSearchInput');
            if (consignSearch) {
                consignSearch.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#consignTableBody tr');
                    rows.forEach(r => {
                        r.style.display = r.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }
        });

        // Filter chips for Consignment Table
        function filterConsignments(status, btnElement) {
            document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');

            const rows = document.querySelectorAll('#consignTableBody tr');
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Camera QR Scanner Modal Handling
        let html5QrScanner = null;
        let isProcessingScan = false;

        const btnStart = document.getElementById('btnModalStartScan');
        const btnStop = document.getElementById('btnModalStopScan');
        const scanAlert = document.getElementById('scanAlert');

        btnStart.addEventListener('click', startScannerModal);

        function startScannerModal() {
            html5QrScanner = new Html5Qrcode("modal-qr-reader");
            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length) {
                    const cameraId = cameras[cameras.length - 1].id;
                    html5QrScanner.start(
                        cameraId,
                        { fps: 10, qrbox: { width: 220, height: 220 } },
                        (decodedText) => handleQrDecoded(decodedText),
                        (error) => {}
                    ).then(() => {
                        btnStart.classList.add('d-none');
                        btnStop.classList.remove('d-none');
                        showScanAlert('Camera active. Hold donor QR pass steady in front of lens.', 'info');
                    });
                } else {
                    showScanAlert('No camera found on this device.', 'danger');
                }
            }).catch(err => {
                showScanAlert('Camera permission error: ' + err, 'danger');
            });
        }

        function stopScannerModal() {
            if (html5QrScanner) {
                html5QrScanner.stop().then(() => {
                    html5QrScanner.clear();
                    btnStart.classList.remove('d-none');
                    btnStop.classList.add('d-none');
                }).catch(() => {});
            }
        }

        function handleQrDecoded(decodedText) {
            if (isProcessingScan) return;
            isProcessingScan = true;
            processConsignmentCheckpoint(decodedText);
        }

        function submitManualConsignment() {
            const val = document.getElementById('manualConsignmentId').value.trim();
            if (!val) {
                alert('Please enter a valid Consignment ID.');
                return;
            }
            processConsignmentCheckpoint(val);
        }

        function processConsignmentCheckpoint(token) {
            showScanAlert('Inspecting consignment state and progressing milestone...', 'info');

            const fd = new FormData();
            fd.append('action', 'qr_scan_update');
            fd.append('qr_token', token);
            fd.append('location', <?php echo json_encode($admin_location); ?>);

            fetch('update_u.php', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    triggerScanFeedback(true);
                    showScanAlert(`Success! Consignment #${data.consignment.id} (${data.consignment.item}): ${data.consignment.old_status} &rarr; <strong>${data.consignment.new_status}</strong>`, 'success');
                    
                    const row = document.getElementById(`row-donation-${data.consignment.id}`);
                    const badge = document.getElementById(`badge-status-${data.consignment.id}`);
                    const select = document.getElementById(`select-status-${data.consignment.id}`);
                    if (row) row.setAttribute('data-status', data.consignment.new_status);
                    if (badge) {
                        badge.textContent = data.consignment.new_status;
                        badge.className = 'status-badge ' + (data.consignment.new_status === 'Delivered' ? 'status-delivered' : 'status-collected');
                    }
                    if (select) select.value = data.consignment.new_status;
                } else if (data.already_fulfilled) {
                    triggerScanFeedback(false);
                    showScanAlert(`<i class="bi bi-shield-slash-fill me-1"></i> ${data.message}`, 'warning');
                } else {
                    triggerScanFeedback(false);
                    showScanAlert(data.message, 'danger');
                }
                setTimeout(() => { isProcessingScan = false; }, 2500);
            })
            .catch(err => {
                triggerScanFeedback(false);
                showScanAlert('Network error during scan verification: ' + err, 'danger');
                isProcessingScan = false;
            });
        }

        function showScanAlert(msg, type) {
            scanAlert.className = `alert alert-${type} py-2 small mb-3`;
            scanAlert.innerHTML = msg;
            scanAlert.classList.remove('d-none');
        }

        function printTableData(tableId, title) {
            const divToPrint = document.getElementById(tableId);
            const win = window.open("", "", "height=700,width=900");
            win.document.write(`<html><head><title>${title}</title>`);
            win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            win.document.write('<style>table { width: 100%; border-collapse: collapse; margin-top: 20px; } th, td { border: 1px solid #ddd; padding: 8px; font-size: 13px; } th { background-color: #16a34a; color: white; }</style>');
            win.document.write(`</head><body><h3 style="text-align:center;margin-top:20px;">${title}</h3>`);
            win.document.write(divToPrint.outerHTML);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
            win.close();
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>