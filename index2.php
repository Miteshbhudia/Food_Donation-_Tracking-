<?php
session_start();
require_once 'dbconnection.inc.php';

// Check donor session
$email = $_SESSION['Email'] ?? $_SESSION['Email1'] ?? null;
if (!$email) {
    header("Location: login_page.html");
    exit();
}

// Fetch authenticated donor details safely
$stmt = $conn->prepare("SELECT Fullname, Donor_ID FROM `donors` WHERE `Email_Address` = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$donor_res = $stmt->get_result();

if ($donor_res && $donor_res->num_rows > 0) {
    $donor_row = $donor_res->fetch_assoc();
    $_SESSION['Fullname'] = $donor_row['Fullname'];
    $first = $donor_row['Fullname'];
    $donor_id_from_db = $donor_row['Donor_ID'];
} else {
    session_unset();
    session_destroy();
    header("Location: login_page.html");
    exit();
}
$stmt->close();
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Client-side QR Code Generator -->
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
            padding: 3.5rem 0;
            margin-bottom: 3rem;
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
            padding: 0.5rem 1.1rem;
            transition: all 0.2s;
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

        /* Consignment Pass Modal */
        .consignment-pass {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1.5rem;
            background-color: #f8fafc;
            text-align: center;
        }
        #qrcode {
            display: flex;
            justify-content: center;
            margin: 1.25rem 0;
        }

        /* Footer */
        .site-footer {
            background-color: #0b1120;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 3.5rem 0 1.5rem 0;
            margin-top: 5rem;
        }
        .site-footer a { color: #cbd5e1; text-decoration: none; }
        .site-footer a:hover { color: var(--primary); }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="index2.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success fs-6 fw-semibold ms-2">Donor</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($first); ?></span>
                <a href="homepage.html" class="btn btn-outline-secondary btn-sm">Home</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Greeting -->
    <header class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-success mb-2 px-3 py-2 fw-semibold">Authenticated Donor</span>
                    <h2 class="fw-bold mb-2">Welcome Back, <?php echo htmlspecialchars($first); ?></h2>
                    <p class="text-white-50 mb-0">Browse real-time sub-county deficits, submit pledges, and track distribution through verifiable QR consignment tokens.</p>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        
        <!-- Live Toast Feedbacks -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
            <div id="liveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i>Donation pledge submitted successfully! Your consignment QR pass is ready below.</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>

        <!-- Section 1: Available Needs / Commodities -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h4 class="fw-bold mb-1">Declared Community Needs</h4>
                    <p class="text-muted small mb-0">Sub-county commodities requested by Area Administrators</p>
                </div>
                <button onclick="printTableData('printTable1', 'Declared Community Needs')" class="btn btn-print"><i class="bi bi-printer me-1"></i> Print Needs List</button>
            </div>

            <div class="table-responsive">
                <table id="printTable1" class="table table-hover">
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
                    <tbody>
                        <?php
                        $sql_commodities = "SELECT c.Commodity_ID, a.Fullname AS AdminName, a.Location AS AdminLocation, c.Commodity, c.Quantity, c.Date_Added
                                            FROM `commodity` c
                                            JOIN `admin` a ON c.Area_Administrator = a.Administrator_ID";
                        $res_commodities = $conn->query($sql_commodities);

                        if ($res_commodities && $res_commodities->num_rows > 0) {
                            while ($row_c = $res_commodities->fetch_assoc()) {
                                $full_qty = htmlspecialchars($row_c["Quantity"]);
                                $num_qty = '';
                                $unit = '';
                                if (preg_match('/^(\d+(\.\d+)?)\s*([a-zA-Z]+)?$/', $full_qty, $matches)) {
                                    $num_qty = $matches[1];
                                    $unit = $matches[3] ?? '';
                                } else {
                                    $num_qty = $full_qty;
                                }

                                echo "<tr>";
                                echo "<td><span class='badge bg-light text-dark border'>#" . htmlspecialchars($row_c["Commodity_ID"]) . "</span></td>";
                                echo "<td>" . htmlspecialchars($row_c["AdminName"]) . "</td>";
                                echo "<td><i class='bi bi-geo-alt text-success me-1'></i>" . htmlspecialchars($row_c["AdminLocation"]) . "</td>";
                                echo "<td class='fw-semibold'>" . htmlspecialchars($row_c["Commodity"]) . "</td>";
                                echo "<td><span class='badge bg-secondary-subtle text-secondary-emphasis'>" . $full_qty . "</span></td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_c["Date_Added"]) . "</td>";
                                echo "<td class='text-center'>
                                        <button type='button' class='btn btn-primary-custom btn-sm' data-bs-toggle='modal' data-bs-target='#donateModal'
                                            data-commodity-id='" . htmlspecialchars($row_c["Commodity_ID"]) . "'
                                            data-commodity-name='" . htmlspecialchars($row_c["Commodity"]) . "'
                                            data-commodity-quantity-numeric='" . htmlspecialchars($num_qty) . "'
                                            data-commodity-unit='" . htmlspecialchars($unit) . "'>Pledge Aid</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No commodity requests currently registered in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Donation History & QR Pass Generator -->
        <div class="section-card">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
                <div>
                    <h4 class="fw-bold mb-1">My Consignment History & QR Passes</h4>
                    <p class="text-muted small mb-0">Track and generate verifiable handover tokens for field distribution hubs</p>
                </div>
                <button onclick="printTableData('printTable2', 'My Donation History')" class="btn btn-print"><i class="bi bi-printer me-1"></i> Print History</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="printTable2">
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
                    <tbody>
                        <?php
                        $stmt_donations = $conn->prepare("
                            SELECT d.Dontation_ID, c.Commodity, c.Quantity AS Commodity_Full_Qty, d.Quantity AS Donated_Qty, d.Date_Donated, d.Location, d.Status
                            FROM `goods_donated` d
                            JOIN `commodity` c ON d.Commodity_ID = c.Commodity_ID
                            WHERE d.Donor_ID = ?
                            ORDER BY d.Dontation_ID DESC
                        ");
                        $stmt_donations->bind_param("i", $donor_id_from_db);
                        $stmt_donations->execute();
                        $res_donations = $stmt_donations->get_result();

                        if ($res_donations && $res_donations->num_rows > 0) {
                            while ($row_d = $res_donations->fetch_assoc()) {
                                $unit_display = '';
                                $parts = explode(' ', $row_d["Commodity_Full_Qty"]);
                                if (count($parts) > 1 && !is_numeric(end($parts))) {
                                    $unit_display = end($parts);
                                }

                                $status = $row_d["Status"];
                                $badge_class = "status-pending";
                                if ($status === "Collected") $badge_class = "status-collected";
                                if ($status === "Delivered") $badge_class = "status-delivered";

                                echo "<tr>";
                                echo "<td><strong>#" . htmlspecialchars($row_d["Dontation_ID"]) . "</strong></td>";
                                echo "<td class='fw-semibold'>" . htmlspecialchars($row_d["Commodity"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row_d["Donated_Qty"]) . " " . htmlspecialchars($unit_display) . "</td>";
                                echo "<td class='text-muted small'>" . htmlspecialchars($row_d["Date_Donated"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row_d["Location"]) . "</td>";
                                echo "<td><span class='status-badge {$badge_class}'>{$status}</span></td>";
                                echo "<td class='text-center'>
                                        <button class='btn btn-outline-success btn-sm' onclick='openQrModal(" . json_encode($row_d) . ", \"{$unit_display}\")'>
                                            <i class='bi bi-qr-code-scan me-1'></i> View Pass
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>You have not made any donations yet. Click 'Pledge Aid' above to submit your first donation.</td></tr>";
                        }
                        $stmt_donations->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Pledge Modal -->
    <div class="modal fade" id="donateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="donateModalLabel">Pledge Aid Consignment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="insertion.inc.php">
                        <input type="hidden" id="commodity-id" name="commodity_id">
                        <input type="hidden" name="donor_id" value="<?php echo htmlspecialchars($donor_id_from_db); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Requested Commodity</label>
                            <input type="text" class="form-control" id="commodity-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pledge Quantity</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                <span class="input-group-text" id="quantity-unit"></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Distribution / Drop-off Hub</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Nairobi Central Hub" required>
                        </div>
                        <input type="hidden" id="today" required name="date">
                        <button type="submit" name="add_donation" class="btn btn-primary-custom w-100 py-2 mt-2">Submit Consignment Pledge</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Consignment QR Pass Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Aid Consignment Pass</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="printablePass">
                    <div class="consignment-pass">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-success">FoodTrace Verified</span>
                            <span class="text-muted small" id="qrPassDate"></span>
                        </div>
                        <h4 class="fw-bold mb-0">Consignment #<span id="qrPassId"></span></h4>
                        <p class="text-muted small mb-2">Handover Verification Badge</p>

                        <!-- Live Rendered QR Element -->
                        <div id="qrcode"></div>

                        <div class="text-start bg-white p-3 rounded border">
                            <div class="row g-2 small">
                                <div class="col-6"><strong>Commodity:</strong> <span id="qrPassCommodity"></span></div>
                                <div class="col-6"><strong>Quantity:</strong> <span id="qrPassQty"></span></div>
                                <div class="col-6"><strong>Donor:</strong> <?php echo htmlspecialchars($first); ?></div>
                                <div class="col-6"><strong>Target Hub:</strong> <span id="qrPassHub"></span></div>
                                <div class="col-12 mt-2 pt-2 border-top"><strong>Current Status:</strong> <span id="qrPassStatus" class="status-badge"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <span class="small text-muted">Show this QR token to the Area Administrator</span>
                    <button type="button" class="btn btn-dark btn-sm" onclick="printConsignmentPass()"><i class="bi bi-printer me-1"></i> Print Pass</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container text-center">
            <p class="mb-0 small text-white-50">&copy; Food Aid Traceability System. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set date format MM/DD/YYYY for donation submission
        document.addEventListener('DOMContentLoaded', function() {
            const todayInput = document.getElementById("today");
            if (todayInput) {
                const n = new Date();
                todayInput.value = (n.getMonth() + 1) + "/" + n.getDate() + "/" + n.getFullYear();
            }

            // Trigger success toast if arriving from donation submission
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('donation') === 'success') {
                const toastEl = document.getElementById('liveToast');
                if (toastEl) new bootstrap.Toast(toastEl).show();
                history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // Populate pledge modal
        const donateModal = document.getElementById('donateModal');
        if (donateModal) {
            donateModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                donateModal.querySelector('#commodity-id').value = btn.getAttribute('data-commodity-id');
                donateModal.querySelector('#commodity-name').value = btn.getAttribute('data-commodity-name');
                donateModal.querySelector('#quantity').value = btn.getAttribute('data-commodity-quantity-numeric');
                donateModal.querySelector('#quantity-unit').textContent = btn.getAttribute('data-commodity-unit');
            });
        }

        // Generate Dynamic QR Code in Modal
        let qrCodeInstance = null;
        function openQrModal(item, unit) {
            document.getElementById('qrPassId').textContent = item.Dontation_ID;
            document.getElementById('qrPassDate').textContent = item.Date_Donated;
            document.getElementById('qrPassCommodity').textContent = item.Commodity;
            document.getElementById('qrPassQty').textContent = `${item.Donated_Qty} ${unit}`;
            document.getElementById('qrPassHub').textContent = item.Location;
            
            const statusEl = document.getElementById('qrPassStatus');
            statusEl.textContent = item.Status;
            statusEl.className = 'status-badge ' + (item.Status === 'Delivered' ? 'status-delivered' : (item.Status === 'Collected' ? 'status-collected' : 'status-pending'));

            // Clear previous QR canvas
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = "";

            // Encode consignment ID for scanner recognition
            qrCodeInstance = new QRCode(qrContainer, {
                text: String(item.Dontation_ID),
                width: 170,
                height: 170,
                colorDark : "#0f172a",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            new bootstrap.Modal(document.getElementById('qrModal')).show();
        }

        // Print Consignment Pass
        function printConsignmentPass() {
            const passHtml = document.getElementById('printablePass').innerHTML;
            const win = window.open('', '', 'height=600,width=650');
            win.document.write('<html><head><title>Print Consignment Pass</title>');
            win.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            win.document.write('<style>body { font-family: sans-serif; padding: 20px; text-align: center; } .consignment-pass { border: 2px dashed #000; padding: 20px; } #qrcode img { margin: 0 auto; }</style>');
            win.document.write('</head><body>');
            win.document.write(passHtml);
            win.document.write('</body></html>');
            win.document.close();
            setTimeout(() => { win.print(); win.close(); }, 500);
        }

        // General Table Printer
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