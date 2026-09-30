<?php
session_start();
require_once 'dbconnection.inc.php';

// Verify System Admin authentication
if (!isset($_SESSION['adminname']) and !isset($_SESSION['Email'])) { 
    header("Location: login_page.html");
    exit();
}
$fullname = $_SESSION['adminname'] ?? 'System Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Provision Account - Food Aid Traceability System</title>
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

        .register-card {
            max-width: 680px;
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="system_admin.php" class="brand-title">Food<span>Trace</span> <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-semibold ms-2">System Admin</span></a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="system_admin.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
    </nav>

    <!-- Main Form Section -->
    <div class="form-wrapper">
        <div class="container">
            <div class="register-card">
                <div class="text-center mb-4">
                    <span class="badge bg-success mb-2 px-3 py-1 fw-semibold">User Provisioning</span>
                    <h3 class="fw-bold mb-1">Create System Account</h3>
                    <p class="text-muted small mb-0">Provision an authorized role for Area Administrators, Donors, or System Administrators.</p>
                </div>

                <form method="POST" action="insertion.inc.php">
                    <input type="hidden" name="admin_create_user" value="1">

                    <div class="row g-3">
                        <!-- Role Selection -->
                        <div class="col-12">
                            <label class="form-label text-success">Select Account Role</label>
                            <select class="form-select fw-semibold" name="role" id="roleSelect" onchange="toggleRoleFields()">
                                <option value="Area Administrator" selected>Area Administrator (Field Officer)</option>
                                <option value="Donor">Donor (Individual / Institutional)</option>
                                <option value="System Administrator">System Administrator (Central Admin)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="e.g. John Kamau" required name="fname" autofocus>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" placeholder="e.g. 0712345678" required name="phone">
                        </div>

                        <!-- Hub Location (Dynamic) -->
                        <div class="col-12" id="locationGroup">
                            <label class="form-label" id="locationLabel">Assigned Hub / Sub-County Location</label>
                            <input type="text" class="form-control" id="locationInput" placeholder="e.g. Nairobi Central Hub" name="location" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" placeholder="user@fooddistribution.com" required name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Create password" required name="password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" placeholder="Repeat password" required name="cpassword">
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-submit" type="submit" name="adda">
                            <i class="bi bi-person-check-fill me-1"></i> Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3 pt-3 border-top">
                    <a href="system_admin.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left me-1"></i> Cancel and Return to System Admin Console
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
        function toggleRoleFields() {
            const role = document.getElementById('roleSelect').value;
            const locGroup = document.getElementById('locationGroup');
            const locInput = document.getElementById('locationInput');
            const locLabel = document.getElementById('locationLabel');

            if (role === 'Area Administrator') {
                locGroup.style.display = 'block';
                locLabel.textContent = 'Assigned Hub / Sub-County Location';
                locInput.placeholder = 'e.g. Nairobi Central Hub, Kisumu West Depot';
                locInput.required = true;
            } else if (role === 'Donor') {
                locGroup.style.display = 'none';
                locInput.required = false;
                locInput.value = '';
            } else if (role === 'System Administrator') {
                locGroup.style.display = 'block';
                locLabel.textContent = 'Administrative Headquarters / Region';
                locInput.placeholder = 'e.g. National Headquarters, Nairobi';
                locInput.required = false;
                locInput.value = 'National HQ';
            }
        }
    </script>
</body>

</html>