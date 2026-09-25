<?php
// Enable error reporting for debugging (REMOVE THIS ON PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnection.inc.php';
session_start();

if (!isset($_SESSION['Email1']) && !isset($_SESSION['adminname1'])) {
    header("Location: login.html");
    exit(); // Always exit after a header redirect
} else {
    $email = $_SESSION['Email1'];
    // It's better to store Administrator_ID in session directly or fetch it here
    // Assuming 'second' holds the Administrator_ID for 'adr' field.
    // If 'second' is adminname, you'd need a lookup here or in insertion.inc.php
    $admin_id_from_session = isset($_SESSION['Administrator_ID']) ? $_SESSION['Administrator_ID'] : '';
    
    // If Administrator_ID is not in session, fetch it from DB using email
    if (empty($admin_id_from_session)) {
        $stmt = mysqli_prepare($conn, "SELECT Fullname, Location, Administrator_ID FROM `admin` WHERE `Email_Address` = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $query_result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_array($query_result);
        mysqli_stmt_close($stmt);

        if ($row) {
            $admin_fullname = $row['Fullname'];
            $admin_location = $row['Location'];
            $admin_id_from_session = $row['Administrator_ID']; // Store ID in session for future use
            $_SESSION['Administrator_ID'] = $admin_id_from_session;
            $_SESSION['adminname1'] = $admin_fullname; // Ensure adminname1 is set for other uses
        } else {
             // Fallback if admin not found, clear session and redirect
            session_unset();
            session_destroy();
            header("Location: login.html");
            exit();
        }
    } else {
        // If ID is already in session, just fetch fullname and location if needed for display
        $stmt = mysqli_prepare($conn, "SELECT Fullname, Location FROM `admin` WHERE `Administrator_ID` = ?");
        mysqli_stmt_bind_param($stmt, "i", $admin_id_from_session);
        mysqli_stmt_execute($stmt);
        $query_result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_array($query_result);
        mysqli_stmt_close($stmt);
        if ($row) {
            $admin_fullname = $row['Fullname'];
            $admin_location = $row['Location'];
        } else {
             // Fallback if admin_id in session is invalid
            session_unset();
            session_destroy();
            header("Location: login.html");
            exit();
        }
    }
}

// Handle status messages from insertion.inc.php
$message = '';
$msg_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $msg_type = $_SESSION['msg_type'];
    unset($_SESSION['message']);
    unset($_SESSION['msg_type']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Food Donation System - Add A Commodity Page</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">

    <style>
        :root {
            /* Consistent with your previous page's primary and secondary for the logo */
            --primary: #8BC34A; /* Retaining your green color from the first page */
            --secondary: #FFC107; /* Retaining your complementary accent color (orange/yellow) from the first page */

            /* Other colors for this page, you can adjust these */
            --primary-green: #28a745; /* A strong, vibrant green */
            --secondary-green: #38c172; /* A slightly lighter, accent green */
            --dark-green: #218838; /* Darker green for hover/active states */
            --light-green-bg: #e6ffe6; /* Very light green for backgrounds */
            --text-dark: #343a40; /* Dark text for contrast */
            --text-light: #f8f9fa; /* Light text for dark backgrounds */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f0f2f5; /* Light grey background for the whole page */
        }

        /* Topbar styling */
        .px-5.d-none.d-lg-block .text-primary {
            color: var(--secondary-green) !important; /* This rule affects other text-primary elements in the top bar */
        }

        /* Logo Styling for "Food Donation" */
        .navbar-brand h1 .food-text {
            color: var(--secondary) !important; /* Orange/Yellow for "Food" */
        }

        .navbar-brand h1 .donation-text {
            color: var(--primary) !important; /* Green for "Donation" */
        }

        /* Hero Section styling */
        .bg-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url(https://placehold.co/1920x250/28a745/ffffff?text=Add+Commodity) no-repeat center center; /* Placeholder image with green overlay and updated text */
            background-size: cover;
            min-height: 250px; /* Reduced height for a less prominent hero */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center; /* Center text horizontally */
        }
        .bg-hero .display-1 {
            font-size: 3.5rem; /* Adjust font size for the new title */
            margin-bottom: 0 !important; /* Remove bottom margin */
        }
        .bg-hero .btn-primary {
            display: none; /* Hide the button in the hero section */
        }


        /* Contact Section (Form) styling */
        .contact-form-section .bg-primary {
            background-color: var(--light-green-bg) !important; /* Lighter green for the form background */
            border-radius: 15px; /* Rounded corners for the form container */
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Soft shadow */
        }
        .contact-form-section .text-primary {
            color: var(--primary-green) !important;
        }
        .contact-form-section .form-control,
        .contact-form-section .form-select {
            border: 2px solid var(--secondary-green) !important;
            border-radius: 8px; /* Rounded corners for inputs */
            padding: 15px 20px;
            font-size: 1rem;
            color: var(--text-dark);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .contact-form-section .form-control:focus,
        .contact-form-section .form-select:focus {
            border-color: var(--dark-green) !important;
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25); /* Green focus glow */
        }
        .contact-form-section .form-control::placeholder {
            color: #888;
        }

        .contact-form-section .btn-secondary {
            background: linear-gradient(to right, var(--primary-green), var(--secondary-green)); /* Green gradient button */
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 50px; /* Pill-shaped button */
            padding: 15px 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .contact-form-section .btn-secondary:hover {
            background: linear-gradient(to right, var(--dark-green), var(--primary-green));
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); /* Slight lift on hover */
        }

        /* Footer styling */
        .bg-footer.bg-primary {
            background-color: var(--primary-green) !important;
        }
        .bg-footer .text-white {
            color: var(--text-light) !important;
        }
        .bg-footer .text-secondary {
            color: var(--secondary-green) !important;
        }
        .bg-dark {
            background-color: #212529 !important; /* Keep dark for contrast */
        }
        .bg-dark .text-secondary {
            color: var(--secondary-green) !important;
        }
        .bg-footer .btn-secondary {
            background-color: var(--secondary-green) !important;
            border-color: var(--secondary-green) !important;
            color: white;
        }
        .bg-footer .btn-secondary:hover {
            background-color: var(--dark-green) !important;
            border-color: var(--dark-green) !important;
        }

        /* Back to Top Button */
        .back-to-top.btn-secondary {
            background-color: var(--secondary-green) !important;
            border-color: var(--secondary-green) !important;
            color: white;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .back-to-top.btn-secondary:hover {
            background-color: var(--dark-green) !important;
            border-color: var(--dark-green) !important;
            transform: translateY(-3px);
        }

        /* General adjustments for better spacing and responsiveness */
        .container-fluid.py-5 {
            padding-top: 3rem !important;
            padding-bottom: 3rem !important;
        }
        .mx-auto.text-center.mb-5 {
            margin-bottom: 3rem !important;
        }
        .row.g-3 > div {
            padding: 0.75rem; /* Adjust padding between form elements */
        }
        /* Style for toast messages */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 250px;
        }
        .toast-header {
            border-bottom: 1px solid rgba(0,0,0,.05);
        }
        .toast-body {
            padding: 1rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-5 d-none d-lg-block">
        <div class="row gx-5 py-3 align-items-center">
            <div class="col-lg-3">
                <div class="d-flex align-items-center justify-content-start">
                    <i class="bi bi-phone-vibrate fs-1 text-secondary-green me-2"></i>
                    <h2 class="mb-0 text-dark">0745603353</h2>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-center">
                    <a href="index1.php" class="navbar-brand ms-lg-5">
                        <h1 class="m-0 display-4"><span class="food-text">Food</span> <span class="donation-text">Donation</span></h1>
                    </a>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="d-flex align-items-center justify-content-end">
                    <a class="btn btn-primary btn-square rounded-circle me-2" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="btn btn-primary btn-square rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-5 contact-form-section">
        <div class="container">
            <div class="mx-auto text-center mb-5" style="max-width: 500px;">
                <h6 class="text-primary text-uppercase">Add A Commodity</h6>
            </div>
            <div class="row g-0 justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="bg-primary h-100 p-5">
                        <!-- Toast message display -->
                        <?php if (!empty($message)): ?>
                            <div class="toast show align-items-center text-white bg-<?php echo $msg_type; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <?php echo htmlspecialchars($message); ?>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="insertion.inc.php" id="commodityForm">
                            <div class="row g-3">
                                <div class="col-12">
                                    <input type="text" class="form-control bg-light border-0 px-4" placeholder="Commodity Name" style="height: 55px;" required name="com">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control bg-light border-0 px-4" placeholder="Quantity" style="height: 55px;" required name="quantity_value" id="quantityValue" min="0" step="any">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select bg-light border-0 px-4" style="height: 55px;" required name="quantity_unit" id="quantityUnit">
                                        <option value="">Select Unit</option>
                                        <option value="kg">Kilograms (kg)</option>
                                        <option value="liters">Liters</option>
                                        <option value="units">Units</option>
                                        <option value="packs">Packs</option>
                                        <option value="grams">Grams (g)</option>
                                        <option value="ml">Milliliters (ml)</option>
                                        <option value="dozen">Dozen</option>
                                        <option value="bags">Bags</option>
                                        <option value="boxes">Boxes</option>
                                    </select>
                                </div>
                                <!-- Hidden input to store the combined quantity and unit -->
                                <input type="hidden" name="number" id="combinedQuantity">

                                <div class="col-12">
                                    <!-- This hidden input should pass the Administrator_ID -->
                                    <input class="form-control" hidden required value="<?php echo htmlspecialchars($admin_id_from_session); ?>" type="text" name="adr">
                                    <input type="text" id="today" required hidden name="date1">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-secondary w-100 py-3" type="submit" name="addc">Add Commodity</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-footer bg-primary text-white mt-5">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div id="contact" class="col-lg-4 col-md-12 pt-5 mb-5">
                            <h4 class="text-white mb-4">Get In Touch</h4>
                            <div class="d-flex mb-2">
                                <i class="bi bi-geo-alt text-white me-2"></i>
                                <p class="text-white mb-0">Nairobi, KENYA.</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-envelope-open text-white me-2"></i>
                                <p class="text-white mb-0">tarirai.makoni@starthmore.edu</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-telephone text-white me-2"></i>
                                <p class="text-white mb-0">0745603353</p>
                            </div>
                            <div class="d-flex mt-4">
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Quick Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <a class="text-white mb-2" href="index1.php"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
                                <a class="text-white mb-2" href="#about"><i class="bi bi-arrow-right text-white me-2"></i>About Us</a>
                                <a class="text-white mb-2" href="#service"><i class="bi bi-arrow-right text-white me-2"></i>Our Services</a>
                                <a class="text-white mb-2" href="logout.php"><i class="bi bi-arrow-right text-white me-2"></i>Logout</a>
                                <a class="text-white" href="#contact"><i class="bi bi-arrow-right text-white me-2"></i>Contact Us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="index.html">Food Donation System</a>. All Rights Reserved.</p>
        </div>
    </div>
    <a href="#" class="btn btn-secondary py-3 fs-4 back-to-top"><i class="bi bi-arrow-up"></i></a>


    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <script src="js/main.js"></script>
    <script type="text/javascript">
        // Set today's date for the form
        document.addEventListener('DOMContentLoaded', function() {
            const todayInput = document.getElementById("today");
            if (todayInput) {
                const n = new Date();
                const y = n.getFullYear();
                const m = n.getMonth() + 1;
                const d = n.getDate();
                // Format date as YYYY-MM-DD for MySQL
                todayInput.value = y + "-" + (m < 10 ? '0' : '') + m + "-" + (d < 10 ? '0' : '') + d;
            }

            // JavaScript to combine quantity and unit before form submission
            const commodityForm = document.getElementById('commodityForm');
            if (commodityForm) {
                commodityForm.addEventListener('submit', function(event) {
                    const quantityValue = document.getElementById('quantityValue').value;
                    const quantityUnit = document.getElementById('quantityUnit').value;
                    const combinedQuantityInput = document.getElementById('combinedQuantity');

                    if (quantityValue && quantityUnit) {
                        // Combine the value and unit, e.g., "10 kg"
                        combinedQuantityInput.value = quantityValue + ' ' + quantityUnit;
                    } else {
                        // If either is missing, prevent submission and show an error
                        alert('Please enter both quantity and select a unit.');
                        event.preventDefault(); // Prevent form submission
                    }
                });
            }

            // Optional: Show toast messages if status parameter is present in URL
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const toastElement = document.querySelector('.toast'); // Select the toast element

            if (status && toastElement) {
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
                // Optionally remove the query parameter to prevent re-showing on refresh
                history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>

</html>