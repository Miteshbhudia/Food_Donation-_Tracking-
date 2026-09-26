<?php
session_start();
require_once 'dbconnection.inc.php';

// Verify Area Admin authentication across session keys
$email = $_SESSION['Email1'] ?? $_SESSION['Email'] ?? null;
$session_admin_id =$_SESSION['Administrator_ID'] ?? null;

if (!$email && !$session_admin_id) {
    header("Location: login_page.html");
    exit();
}

// Fetch and sync Area Administrator credentials
if ($email) {
    $stmt =$conn->prepare("SELECT Administrator_ID, Fullname, Location, Email_Address FROM `admin` WHERE `Email_Address` = ?");
    $stmt->bind_param("s", $email);
} else {
    $stmt =$conn->prepare("SELECT Administrator_ID, Fullname, Location, Email_Address FROM `admin` WHERE `Administrator_ID` = ?");
    $stmt->bind_param("i", $session_admin_id);
}

$stmt->execute();$admin_res = $stmt->get_result();$admin = $admin_res->fetch_assoc();$stmt->close();

if (!$admin) {
    session_unset();
    session_destroy();
    header("Location: login_page.html");
    exit();
}

// Synchronize all expected session variables
$admin_id       =$admin['Administrator_ID'];
$admin_fullname =$admin['Fullname'];
$admin_location =$admin['Location'];
$_SESSION['Administrator_ID'] =$admin_id;
$_SESSION['Email1']           =$admin['Email_Address'];
$_SESSION['adminname1']       =$admin_fullname;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Declare Commodity Need - Food Aid Traceability System</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
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
            display: flex;
            flex-direction: column;
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

        .form-wrapper {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            padding: 3.5rem 0;
        }

        .commodity-card {
            max-width: 620px;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            padding: 2.75rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.925rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        }

        .btn-submit {
            background-color: var(--primary);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.85rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-submit:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .site-footer {
            background-color: #0b1120;
            color: #94a3b8;
            font-size: 0.9rem;
            padding: 2rem 0;
            margin-top: auto;
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="index1.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success fs-6 fw-semibold ms-2">Area Admin</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-geo-alt-fill text-success me-1"></i><?php echo htmlspecialchars($admin_location); ?></span>
                <a href="index1.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="form-wrapper">
        <div class="container">
            <div class="commodity-card">
                <div class="text-center mb-4">
                    <span class="badge bg-success mb-2 px-3 py-1 fw-semibold">Regional Supply Request</span>
                    <h3 class="fw-bold mb-1">Declare Community Need</h3>
                    <p class="text-muted small mb-0">Publish an aid deficit for <strong><?php echo htmlspecialchars($admin_location); ?></strong> to prospective donors.</p>
                </div>

                <form method="POST" action="insertion.inc.php" id="commodityForm">
                    <!-- Explicit hidden attributes for backend processing -->
                    <input type="hidden" name="addc" value="1">
                    <input type="hidden" name="adr" value="<?php echo htmlspecialchars($admin_id); ?>">
                    <input type="hidden" name="date1" id="today">
                    <input type="hidden" name="number" id="combinedQuantity">

                    <div class="mb-3">
                        <label for="com" class="form-label">Commodity / Food Item</label>
                        <input type="text" class="form-control" id="com" name="com" placeholder="e.g. Maize Flour, Rice, Cooking Oil, Clean Water" required autofocus>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-7">
                            <label for="quantityValue" class="form-label">Required Quantity</label>
                            <input type="number" class="form-control" id="quantityValue" name="quantity_value" placeholder="e.g. 200" min="1" step="any" required>
                        </div>
                        <div class="col-md-5">
                            <label for="quantityUnit" class="form-label">Unit of Measure</label>
                            <select class="form-select" id="quantityUnit" name="quantity_unit" required>
                                <option value="kg" selected>Kilograms (kg)</option>
                                <option value="liters">Liters (L)</option>
                                <option value="packs">Packs</option>
                                <option value="bags">Bags</option>
                                <option value="boxes">Boxes</option>
                                <option value="units">Units</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="addc" class="btn btn-submit" id="submitBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Commodity
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3 pt-3 border-top">
                    <a href="index1.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left me-1"></i> Cancel and Return to Area Dashboard
                    </a>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date (YYYY-MM-DD)
            const todayInput = document.getElementById("today");
            if (todayInput) {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                todayInput.value = `${y}-${m}-${d}`;
            }

            // Sync combined quantity string prior to POST dispatch
            const form = document.getElementById('commodityForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const qVal = document.getElementById('quantityValue').value.trim();
                    const qUnit = document.getElementById('quantityUnit').value.trim();
                    const combinedInput = document.getElementById('combinedQuantity');

                    if (!qVal || !qUnit) {
                        e.preventDefault();
                        alert('Please specify both the quantity and unit.');
                        return;
                    }

                    combinedInput.value = `${qVal} ${qUnit}`;
                });
            }
        });
    </script>
</body>

</html>