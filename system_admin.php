<?php
require_once 'dbconnection.inc.php'; // Ensure this file provides a $conn variable for the database connection
session_start();

// Check if System Admin is logged in
if (!isset($_SESSION['adminname'])) { 
    header("Location: login.html");
    exit();
}

$fullname = $_SESSION['adminname'];
$message = ''; // Initialize message variable for user feedback

$edit_mode = false;
$admin_to_edit = null;

// --- Handle Form Submissions (Update and Delete) ---

// Handle Update action
if (isset($_POST['update_admin'])) {
    $admin_id = $_POST['admin_id'];
    $fullname_edit = $_POST['fullname'];
    $email_address_edit = $_POST['email_address'];
    $location_edit = $_POST['location']; // Location can be empty, but other fields are required.

    // Basic validation
    if (!empty($admin_id) && !empty($fullname_edit) && !empty($email_address_edit)) {
        // Use the $conn from dbconnection.inc.php
        $stmt = $conn->prepare("UPDATE `admin` SET `Fullname`=?, `Email_Address`=?, `Location`=? WHERE `Administrator_ID`=?");
        // Assuming Position is not directly editable by admin, or it's fixed as 'Area Administrator'
        $stmt->bind_param("sssi", $fullname_edit, $email_address_edit, $location_edit, $admin_id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Administrator updated successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating administrator: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Please fill all required fields for update.</div>";
    }
}

// Handle Delete action
if (isset($_POST['dela'])) {
    $id_to_delete = $_POST['id3'];
    // Use the $conn from dbconnection.inc.php
    $stmt = $conn->prepare("DELETE FROM `admin` WHERE `Administrator_ID` = ?");
    $stmt->bind_param("i", $id_to_delete);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Administrator deleted successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error deleting administrator: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// --- Check for Edit Request ---
// This part must come AFTER processing POST data, so an update can happen before re-displaying the list.
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $admin_id_to_fetch = $_GET['id'];

    // Use the $conn from dbconnection.inc.php
    $stmt = $conn->prepare("SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location` FROM `admin`
     WHERE `Administrator_ID` = ? AND `Position` = 'Area Administrator'");
    $stmt->bind_param("i", $admin_id_to_fetch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin_to_edit = $result->fetch_assoc();
    } else {
        $message = "<div class='alert alert-danger'>Area Administrator not found or not an Area Administrator.</div>";
        $edit_mode = false; // Revert to list view if not found
    }
    $stmt->close();
}


// --- Fetch Dashboard Statistics ---
// Total Donors
$donor_sql = "SELECT COUNT(*) as total_donors FROM `donors`";
$donor_result = $conn->query($donor_sql);
$total_donors = $donor_result->fetch_assoc()['total_donors'];

// Total Area Admins
$admin_sql = "SELECT COUNT(*) as total_admins FROM `admin` WHERE `Position` = 'Area Administrator'";
$admin_result = $conn->query($admin_sql);
$total_admins = $admin_result->fetch_assoc()['total_admins'];

// Total Donations
$donation_sql = "SELECT COUNT(*) as total_donations FROM `goods_donated`";
$donation_result = $conn->query($donation_sql);
$total_donations = $donation_result->fetch_assoc()['total_donations'];

// Total Commodities (assuming this refers to total needs or types of goods)
$commodity_sql = "SELECT COUNT(*) as total_commodities FROM `commodity`";
$commodity_result = $conn->query($commodity_sql);
$total_commodities = $commodity_result->fetch_assoc()['total_commodities'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Food Donation - System Administrator</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #5cb85c; /* A nice green for primary actions, you can change this */
            --secondary: #f0ad4e; /* A complementary color */
            --dark: #343a40;
            --light: #f8f9fa;
        }

        /* General Body Styling */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
        }

        /* Navbar Enhancements */
        .navbar {
            background-color: var(--dark) !important; /* Darker nav for contrast */
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .navbar .nav-link {
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.7); /* Lighter text for dark background */
            transition: 0.3s;
        }
        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: var(--primary) !important;
        }
        /* SPECIFIC CHANGE: Smaller font for the nav brand text on smaller screens */
        .navbar-brand h1 {
            font-size: 1.75rem; /* Adjust this value as needed, e.g., 2rem, 1.5rem */
            color: var(--primary) !important;
        }
        .navbar-brand h1 span {
             color: var(--light) !important;
        }


        /* Hero Section Styling */
        .bg-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://via.placeholder.com/1920x600.png?text=Food+Donation+Banner') no-repeat center center; /* Placeholder image */
            background-size: cover;
            position: relative;
            z-index: 1;
            padding-top: 8rem; /* More vertical padding */
            padding-bottom: 8rem;
        }
        .bg-hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Dark overlay */
            z-index: -1;
        }
        .bg-hero h1.display-3 { /* Changed from display-1 */
            font-size: 3.5rem; /* Smaller display-1 for better aesthetics */
            font-weight: 700;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 1.5rem !important; /* Adjust margin */
        }
        .bg-hero p.fs-5 { /* Changed from fs-4 */
            font-size: 1.5rem !important;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Section Headings */
        .mx-auto.text-center.mb-5 h6 {
            color: var(--primary) !important;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .mx-auto.text-center.mb-5 h1.display-5 {
            color: var(--dark);
            font-weight: 700;
            margin-top: 0.5rem;
            margin-bottom: 2rem;
        }

        /* Stats Card Styling */
        .stats-card {
            background-color: #ffffff; /* White background for cards */
            border: none; /* Remove default border */
            border-radius: .75rem; /* More rounded corners */
            padding: 2rem; /* Increased padding */
            text-align: center;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.08); /* Softer shadow */
            transition: all 0.4s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%; /* Ensure cards have equal height in a row */
        }
        .stats-card:hover {
            transform: translateY(-8px); /* More pronounced lift */
            box-shadow: 0 0.8rem 2rem rgba(0,0,0,.15); /* Stronger shadow on hover */
        }
        .stats-icon {
            font-size: 4rem; /* Larger icons */
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .stats-title {
            font-size: 1.35rem; /* Slightly larger title */
            font-weight: 600;
            color: var(--dark);
            margin-top: 0; /* Remove default margin */
            margin-bottom: 0.5rem;
        }
        .stats-number {
            font-size: 3rem; /* Larger number */
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0; /* Remove default margin */
        }

        /* Table Styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: .5rem;
            overflow: hidden; /* Ensures rounded corners apply */
        }
        .table thead.table-primary th {
            background-color: var(--primary);
            color: #fff;
            border-color: var(--primary);
            font-weight: 600;
            padding: 1rem;
        }
        .table tbody tr {
            background-color: #fff;
            transition: all 0.2s ease-in-out;
        }
        .table tbody tr:hover {
            background-color: #f0f0f0; /* Light hover effect */
        }
        .table tbody td {
            vertical-align: middle;
            padding: 1rem;
        }
        .table-responsive {
            border-radius: .5rem; /* Match table rounded corners */
            box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,.05); /* Subtle shadow for the table container */
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 5px; /* Spacing between buttons */
            flex-wrap: wrap; /* Allow buttons to wrap if space is limited */
            justify-content: center; /* Center the buttons if desired */
            align-items: center;
        }
        .action-buttons .btn {
            font-size: 0.85rem;
            padding: 0.375rem 0.75rem;
            border-radius: .25rem;
        }
        .btn-info {
            background-color: #17a2b8; /* Bootstrap default info */
            border-color: #17a2b8;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545; /* Bootstrap default danger */
            border-color: #dc3545;
            color: white;
        }

        /* Form Styling (Edit Administrator) */
        .card.p-4.shadow-sm {
            border-radius: .75rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.08) !important;
            background-color: #ffffff;
        }
        .card h3 {
            color: var(--dark);
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: var(--dark);
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), .25); /* Assuming --primary-rgb is available from bootstrap */
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            filter: brightness(90%); /* Simpler way to darken in CSS */
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            filter: brightness(90%);
        }

        /* Footer Styling */
        .bg-footer {
            background-color: var(--dark) !important; /* Match navbar */
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.7);
        }
        .bg-footer a {
            color: var(--primary) !important;
            font-weight: 700;
            text-decoration: none;
        }
        .bg-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm py-3 py-lg-0 px-3 px-lg-5">
        <a href="system_admin.php" class="navbar-brand d-flex d-lg-none">
            <h1 class="m-0 fs-3 text-secondary"><span class="text-white">System</span> Admin</h1>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto py-0">
                <a href="system_admin.php" class="nav-item nav-link active">Dashboard</a>
                <a href="logout.php" class="nav-item nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-primary py-5 bg-hero mb-5">
        <div class="container py-5">
            <div class="row justify-content-start">
                <div class="col-lg-10 text-center text-lg-start">
                    <h1 class="display-3 text-white mb-md-4">Welcome, <?php echo htmlspecialchars($fullname); ?>!</h1>
                    <p class="fs-5 text-white mb-4 pb-2">Your central hub for managing the Food Donation System.</p>
                </div>
            </div>
        </div>
    </div>

    <main>
        <div class="container-fluid py-5">
            <div class="container">
                <div class="mx-auto text-center mb-5" style="max-width: 700px;">
                    <h6 class="text-primary text-uppercase">Platform Overview</h6>
                    <h1 class="display-5">Key System Statistics</h1>
                    <p class="lead text-muted">Get a quick glance at the current status of donors, administrators, 
                        donations, and required commodities.</p>
                </div>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <i class="fas fa-users stats-icon"></i>
                            <h3 class="stats-title">Total Donors</h3>
                            <p class="stats-number"><?php echo htmlspecialchars($total_donors); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <i class="fas fa-user-shield stats-icon"></i>
                            <h3 class="stats-title">Area Administrators</h3>
                            <p class="stats-number"><?php echo htmlspecialchars($total_admins); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <i class="fas fa-hands-helping stats-icon"></i>
                            <h3 class="stats-title">Total Donations</h3>
                            <p class="stats-number"><?php echo htmlspecialchars($total_donations); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <i class="fas fa-box-open stats-icon"></i>
                            <h3 class="stats-title">Total Needs</h3>
                            <p class="stats-number"><?php echo htmlspecialchars($total_commodities); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid py-5">
            <div class="container">
                <div class="mx-auto text-center mb-5" style="max-width: 700px;">
                    <h6 class="text-primary text-uppercase">Administrator Management</h6>
                    <h1 class="display-5">Manage Area Administrators</h1>
                    <p class="lead text-muted">View, edit, or delete existing Area Administrators, or add new ones to the system.</p>
                </div>

                <?php echo $message; // Display messages here ?>

                <?php if ($edit_mode && $admin_to_edit): ?>
                    <div class="card p-4 shadow-sm mb-5">
                        <h3 class="mb-4">Edit Area Administrator: <?php echo htmlspecialchars($admin_to_edit['Fullname']); ?></h3>
                        <form method="POST" action="system_admin.php">
                            <input type="hidden" name="admin_id" value="<?php echo htmlspecialchars($admin_to_edit['Administrator_ID']); ?>">

                            <div class="mb-3">
                                <label for="fullname_edit" class="form-label">Fullname</label>
                                <input type="text" class="form-control" id="fullname_edit" name="fullname" value="<?php echo htmlspecialchars
                                ($admin_to_edit['Fullname']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email_address_edit" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email_address_edit" name="email_address" value="<?php echo htmlspecialchars
                                ($admin_to_edit['Email_Address']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="position_edit" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position_edit" name="position" value="<?php echo htmlspecialchars
                                ($admin_to_edit['Position']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="location_edit" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location_edit" name="location" value="<?php echo htmlspecialchars
                                ($admin_to_edit['Location']); ?>">
                            </div>
                            <button type="submit" name="update_admin" class="btn btn-primary">Update Administrator</button>
                            <a href="system_admin.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col">Admin ID</th>
                                    <th scope="col">Fullname</th>
                                    <th scope="col">Email Address</th>
                                    <th scope="col">Position</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // The database connection $conn from dbconnection.inc.php is used here.
                                $user_sql = "SELECT `Administrator_ID`, `Fullname`, `Email_Address`, `Position`, `Location` FROM `admin` 
                                WHERE `Position` = 'Area Administrator'";
                                $user_result = $conn->query($user_sql);

                                if ($user_result->num_rows > 0) {
                                    while($user_row = $user_result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($user_row["Administrator_ID"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($user_row["Fullname"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($user_row["Email_Address"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($user_row["Position"]) . "</td>";
                                        echo "<td>" . (!empty($user_row["Location"]) ? htmlspecialchars($user_row["Location"]) : "Not specified") . "</td>";
                                        echo "<td class='action-buttons'>
                                                    <a href='?action=edit&id=" . htmlspecialchars($user_row["Administrator_ID"]) . "' class='btn btn-info btn-sm'>Edit</a>
                                                    <form method='POST' action='system_admin.php' onsubmit='return confirm
                                                    (\"Are you sure you want to delete this administrator?\");' style='display:inline-block; margin-left: 5px;'>
                                                        <input type='hidden' name='id3' value='" . htmlspecialchars($user_row["Administrator_ID"]) . "'>
                                                        <button type='submit' name='dela' class='btn btn-danger btn-sm'>Delete</button>
                                                    </form>
                                                </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No Area Administrators found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a href="reg_area.php" class="btn btn-primary py-md-3 px-md-5">Add New Area Administrator</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="container-fluid bg-footer text-white mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="#">Food Donation System</a>. All Rights Reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close the database connection provided by dbconnection.inc.php
$conn->close();
?>