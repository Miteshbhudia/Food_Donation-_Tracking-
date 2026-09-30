<?php
session_start();
require_once 'dbconnection.inc.php';

// Verify Donor authentication
$email = $_SESSION['Email2'] ?? $_SESSION['Email'] ?? null;
if (!$email) {
    header("Location: login_page.html");
    exit();
}

$stmt = $conn->prepare("SELECT Fullname, Donor_ID FROM `donors` WHERE `Email_Address` = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$query_result = $stmt->get_result();
$row = $query_result->fetch_assoc();
$stmt->close();

if ($row) {
    $donor_fullname = $row['Fullname'];
    $donor_id       = $row['Donor_ID'];
} else {
    session_unset();
    session_destroy();
    header("Location: login_page.html");
    exit();
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

        /* Impact Metric Widgets */
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
            background-color: #e2e8f0;
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
            background: #ffffff;
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
            <a href="index2.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-semibold ms-2">Donor</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-person-circle text-success me-1"></i><?php echo htmlspecialchars($donor_fullname); ?></span>
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
                    <span class="badge bg-success mb-2 px-3 py-1 fw-semibold">Aid Dispatch Portal</span>
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($donor_fullname); ?></h2>
                    <p class="text-white-50 mb-0">Select declared deficits across sub-counties, commit aid pledges, and download cryptographically verifiable QR passes.</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        
        <!-- Donor Impact Telemetry -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap"><i class="bi bi-heart-pulse-fill"></i></div>
                    <h6>Total Aid Pledges</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($donor_total_pledges); ?></p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-info" style="background:#e0f2fe;"><i class="bi bi-truck"></i></div>
                    <h6>Active / In-Depot</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($donor_active_pledges); ?></p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="stat-widget">
                    <div class="stat-icon-wrap text-success" style="background:#dcfce7;"><i class="bi bi-check2-circle"></i></div>
                    <h6>Delivered to Beneficiaries</h6>
                    <p class="stat-number"><?php echo htmlspecialchars($donor_delivered_pledges); ?></p>
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
                    <button onclick="printTableData('needsTable', 'Declared Community Needs')" class="btn btn-print btn-sm"><i class="bi bi-printer"></i></button>
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
                            <th>Date Added</th>
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

                        if ($res_needs && $res_needs->num_rows > 0) {
                            while ($row_n = $res_needs->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($row_n["Commodity_ID"]) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row_n["Admin_Name"]) . "</td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($row_n["Location"]) . "</td>";
                                echo "<td class='fw-semibold filter-cell'>" . htmlspecialchars($row_n["Commodity"]) . "</td>";
                                echo "<td><span class='badge bg-secondary-subtle text-secondary-emphasis'>" . htmlspecialchars($row_n["Quantity"]) . "</span></td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_n["Date_Added"]) . "</td>";
                                echo "<td class='text-center'>
                                        <button class='btn btn-primary-custom btn-sm' onclick='openPledgeModal(" . json_encode($row_n) . ")'>
                                            Pledge Aid
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No current deficit requests listed.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Consignment History & QR Passes -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold mb-1">My Consignment History & QR Passes</h4>
                    <p class="text-muted small mb-0">Track and generate verifiable handover tokens for field distribution hubs</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="search-input-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="historySearchInput" class="form-control form-control-sm" placeholder="Search my history...">
                    </div>
                    <button onclick="printTableData('historyTable', 'My Consignment History')" class="btn btn-print btn-sm"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="historyTable">
                    <thead>
                        <tr>
                            <th>Consignment ID</th>
                            <th>Commodity</th>
                            <th>Quantity Pledged</th>
                            <th>Pledge Date</th>
                            <th>Intake Hub</th>
                            <th>Status</th>
                            <th class="text-center">Verifiable Token</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <?php
                        $stmt_h = $conn->prepare("
                            SELECT gd.Dontation_ID, c.Commodity, gd.Quantity AS DonatedQty, c.Quantity AS CommQtyStr, 
                                   gd.Date_Donated, gd.Location, gd.Status
                            FROM `goods_donated` gd
                            JOIN `commodity` c ON gd.Commodity_ID = c.Commodity_ID
                            WHERE gd.Donor_ID = ?
                            ORDER BY gd.Dontation_ID DESC
                        ");
                        $stmt_h->bind_param("i", $donor_id);$stmt_h->execute();
                        $res_h =$stmt_h->get_result();

                        if ($res_h &&$res_h->num_rows > 0) {
                            while ($row_h = $res_h->fetch_assoc()) {$unit = '';
                                if (preg_match('/^\d+(\.\d+)?\s*([a-zA-Z]+)?$/', $row_h['CommQtyStr'],$matches)) {
                                    $unit =$matches[2] ?? '';
                                }

                                $status =$row_h["Status"] ?? 'Pending Pickup';
                                $badge_class = "status-pending";
                                if ($status === "Collected") $badge_class = "status-collected";
                                if ($status === "Delivered") $badge_class = "status-delivered";

                                echo "<tr>";
                                echo "<td><strong>#" . htmlspecialchars($row_h["Dontation_ID"]) . "</strong></td>";
                                echo "<td class='fw-semibold filter-cell'>" . htmlspecialchars($row_h["Commodity"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row_h["DonatedQty"]) . " " . htmlspecialchars($unit) . "</td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_h["Date_Donated"]) . "</td>";
                                echo "<td class='filter-cell'><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($row_h["Location"]) . "</td>";
                                echo "<td><span class='status-badge {$badge_class}'>{$status}</span></td>";
                                echo "<td class='text-center'>
                                        <button class='btn btn-outline-success btn-sm' onclick='openQrModal(" . json_encode($row_h) . ", " . json_encode($donor_fullname) . ")'>
                                            <i class='bi bi-qr-code me-1'></i> View Pass
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>You have not initiated any consignment pledges yet.</td></tr>";
                        }
                        $stmt_h->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Pledge Aid Modal -->
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
                                <input type="number" step="any" required name="quantity" class="form-control" placeholder="Enter amount to donate">
                                <span class="input-group-text bg-light" id="pledgeUnitDisplay">units</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pledge Date</label>
                            <input type="text" name="date" class="form-control bg-light" value="<?php echo date('m/d/Y'); ?>" readonly>
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

    <!-- QR Pass Modal with Visual 3-Stage Progress Stepper -->
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
                    </div>
                    <div class="stepper-item" id="step2">
                        <div class="step-counter">2</div>
                        <span>Collected</span>
                    </div>
                    <div class="stepper-item" id="step3">
                        <div class="step-counter">3</div>
                        <span>Delivered</span>
                    </div>
                </div>

                <!-- QR Container -->
                <div class="p-3 bg-light rounded-4 d-inline-block mx-auto mb-3 border">
                    <div id="qrCodeContainer"></div>
                </div>

                <div class="text-start bg-light p-3 rounded-3 small mb-3 border">
                    <div class="row g-2">
                        <div class="col-6"><strong>Commodity:</strong> <span id="qrCommName"></span></div>
                        <div class="col-6"><strong>Quantity:</strong> <span id="qrQuantity"></span></div>
                        <div class="col-6"><strong>Donor:</strong> <span id="qrDonorName"></span></div>
                        <div class="col-6"><strong>Target Hub:</strong> <span id="qrHubName"></span></div>
                        <div class="col-12 mt-2"><strong>Status:</strong> <span id="qrStatusBadge" class="status-badge"></span></div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary-custom w-100" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Pass</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Open Pledge Modal
        function openPledgeModal(comm) {
            document.getElementById('pledgeCommId').value = comm.Commodity_ID;
            document.getElementById('pledgeLocation').value = comm.Location;
            document.getElementById('pledgeCommName').value = comm.Commodity;
            document.getElementById('pledgeHubDisplay').value = comm.Location;

            // Extract unit suffix
            const parts = comm.Quantity.split(' ');
            const unit = parts.length > 1 ? parts[parts.length - 1] : 'units';
            document.getElementById('pledgeUnitDisplay').textContent = unit;

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

            // Update Progress Stepper
            const s1 = document.getElementById('step1');
            const s2 = document.getElementById('step2');
            const s3 = document.getElementById('step3');

            s2.className = 'stepper-item';
            s3.className = 'stepper-item';

            if (item.Status === 'Collected') {
                s2.className = 'stepper-item completed';
                s2.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
            } else if (item.Status === 'Delivered') {
                s2.className = 'stepper-item completed';
                s2.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
                s3.className = 'stepper-item completed';
                s3.querySelector('.step-counter').innerHTML = '<i class="bi bi-check"></i>';
            }

            // Generate clean QR code
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

        // Instant Live Filters
        document.addEventListener('DOMContentLoaded', function() {
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

            const histSearch = document.getElementById('historySearchInput');
            if (histSearch) {
                histSearch.addEventListener('input', function() {
                    const filter = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('#historyTableBody tr');
                    rows.forEach(r => {
                        r.style.display = r.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }
        });

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