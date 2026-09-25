<?php
// Enable error reporting for debugging (REMOVE THIS ON PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure dbconnection.inc.php exists and correctly establishes $conn
require_once 'dbconnection.inc.php';
session_start();

// Redirect to login if email is not set in session
if (!isset($_SESSION['Email'])) {
    header("Location: login.html");
    exit(); // Always add exit after a header redirect
} else {
    $email = $_SESSION['Email'];

    // Query the database to get donor information, including Fullname
    $query = mysqli_query($conn, "SELECT Fullname, Donor_ID FROM `donors` WHERE `Email_Address`='$email'");

    // Check if the query was successful and a row was returned
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_array($query);
        // Assign Fullname and Donor_ID from the database to session variables
        $_SESSION['Fullname'] = $row['Fullname'];
        $first = $row['Fullname']; // Assign to $first for use in the HTML
        $donor_id_from_db = $row['Donor_ID'];
    } else {
        // If no donor found with that email, clear session and redirect to login
        session_unset();
        session_destroy();
        header("Location: login.html");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Food Distribution System - Donor Homepage</title>
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

    <style>
        :root {
            --primary: #8BC34A; /* Retaining your green color */
            --secondary: #FFC107; /* A complementary accent color, you can adjust this */
            --light: #F8F9FA;
            --dark: #212529;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light);
        }

        .navbar-brand h1 span {
            color: var(--secondary) !important; /* Keep secondary for the "Food" part */
        }

        .btn-primary, .bg-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #fff !important;
        }

        .btn-secondary, .bg-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: var(--dark) !important; /* Ensure good contrast */
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .text-secondary {
            color: var(--secondary) !important;
        }

        .navbar-dark .navbar-nav .nav-link.active,
        .navbar-dark .navbar-nav .nav-link:hover {
            color: #FFFFFF !important;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(139, 195, 74, 0.25); /* Light primary shadow */
        }

        .table-primary thead th {
            background-color: var(--primary);
            color: white;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .modal-header {
            background-color: var(--primary);
            color: white;
        }
        .modal-header .btn-close {
            filter: invert(1); /* Makes the close button white */
        }

        .back-to-top {
            background-color: var(--secondary);
            color: var(--dark) !important;
        }
        .back-to-top:hover {
            background-color: var(--primary);
            color: white !important;
        }

        /* Hero Section Styling */
        .hero-header {
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('https://images.pexels.com/photos/6647017/pexels-photo-6647017.jpeg') no-repeat center;
            background-size: cover;
            background-position: center 25%;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            color: white;
        }

        .hero-header p {
            font-size: 1.25rem;
            margin-bottom: 30px;
        }

        /* Section Spacing */
        .container-fluid.py-5 {
            padding-top: 6rem !important;
            padding-bottom: 6rem !important;
        }

        /* Card-like appearance for sections */
        .section-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            margin-bottom: 2rem;
        }

        /* Print Button Styling */
        .print-button {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .print-button:hover {
            background-color: var(--primary);
            color: white;
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
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid px-5 d-none d-lg-block">
        <div class="row gx-5 py-3 align-items-center">
            <div class="col-lg-12 text-center"> <div class="d-flex align-items-center justify-content-center">
                    <a href="index2.php" class="navbar-brand m-0"> <h1 class="display-4 text-primary"><span class="text-secondary">Food</span> Donation</h1>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm py-3 py-lg-0 px-3 px-lg-5">
        <a href="index2.php" class="navbar-brand d-flex d-lg-none">
            <h1 class="m-0 display-4 text-secondary"><span class="text-white">Food</span> Donation</h1>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto py-0">
                <a href="index2.php" class="nav-item nav-link active">Home</a>
                <a href="login_page.html" class="nav-item nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid hero-header mb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1>Welcome Back, <?php echo htmlspecialchars($first); ?>!</h1>
                    <p>Your contributions make a significant difference. Explore donation opportunities and manage your profile here.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-5">
        <div class="container section-card">
            <div class="mx-auto text-center mb-5" style="max-width: 700px;">
                <h6 class="text-primary text-uppercase">Opportunities to Donate</h6>
                <h1 class="display-5">Available Commodities for Donation</h1>
            </div>
            <div class="row g-5">
                <div class="col-12">
                    <div class="table-responsive">
                        <table id="printTable1" class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col">Commodity ID</th>
                                    <th scope="col">Area Administrator</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Commodity</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Date Added</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Establish a new connection for this section to avoid conflicts if dbconnection.inc.php has issues
                                // Re-using $conn from dbconnection.inc.php is preferred if the connection is reliable.
                                // For robustness, if dbconnection.inc.php has issues, separate connections might be used,
                                // but typically you'd fix dbconnection.inc.php instead.
                                // Keeping it simple and using the already established $conn here.

                                // Modified SQL query for commodities to ensure the join is correct
                                $sql = "SELECT
                                            c.Commodity_ID,
                                            a.Fullname AS Area_Administrator_Name,
                                            a.Location AS AdminLocation,
                                            c.Commodity,
                                            c.Quantity,
                                            c.Date_Added
                                        FROM
                                            `commodity` c
                                        JOIN
                                            `admin` a ON c.Area_Administrator = a.Administrator_ID";

                                $result = $conn->query($sql); // Use $conn from dbconnection.inc.php

                                if ($result->num_rows > 0) {
                                    while ($row_commodity = $result->fetch_assoc()) {
                                        // ********* PHP LOGIC TO PARSE QUANTITY AND UNIT FOR MODAL *********
                                        $commodity_quantity_full = htmlspecialchars($row_commodity["Quantity"]);
                                        $numeric_quantity = '';
                                        $unit = '';

                                        // Use regex to extract number and potential unit (captured in group 3)
                                        // This regex handles cases like "32", "32.5", "32kg", "32 kg", "32.5 kg"
                                        if (preg_match('/^(\d+(\.\d+)?)\s*([a-zA-Z]+)?$/', $commodity_quantity_full, $matches)) {
                                            $numeric_quantity = $matches[1]; // The number part (e.g., "32" or "32.5")
                                            $unit = isset($matches[3]) ? $matches[3] : ''; // The unit part (e.g., "kg"), if exists
                                        } else {
                                            // Fallback if regex doesn't match, assume it's just the number and no unit
                                            $numeric_quantity = $commodity_quantity_full;
                                            $unit = '';
                                        }
                                        // ********* END PHP LOGIC *********

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row_commodity["Commodity_ID"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row_commodity["Area_Administrator_Name"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row_commodity["AdminLocation"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row_commodity["Commodity"]) . "</td>";
                                        echo "<td>" . $commodity_quantity_full . "</td>"; // Display full quantity (e.g., "32 kg") in this table
                                        echo "<td>" . htmlspecialchars($row_commodity["Date_Added"]) . "</td>";

                                        // Pass parsed quantity and unit as data attributes to the button
                                        echo "<td><button type='button' class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#donateModal'
                                                data-commodity-id='" . htmlspecialchars($row_commodity["Commodity_ID"]) . "'
                                                data-commodity-name='" . htmlspecialchars($row_commodity["Commodity"]) . "'
                                                data-commodity-quantity-numeric='" . htmlspecialchars($numeric_quantity) . "'
                                                data-commodity-unit='" . htmlspecialchars($unit) . "'>Donate Now</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No commodities available for donation at the moment.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <button onclick="printData1()" class="print-button">Print the List of Commodities</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="donateModal" tabindex="-1" aria-labelledby="donateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="donateModalLabel">Make a Donation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="insertion.inc.php">
                        <input type="hidden" id="commodity-id" name="commodity_id">
                        <input type="hidden" name="donor_id" value="<?php echo htmlspecialchars($donor_id_from_db); ?>">
                        <div class="mb-3">
                            <label for="commodity-name" class="form-label">Commodity</label>
                            <input type="text" class="form-control" id="commodity-name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <div class="input-group"> <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                <span class="input-group-text" id="quantity-unit"></span> </div>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Your Location</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Nairobi County, Mombasa County, Kisumu County" required>
                            <div class="form-text">Please specify your county or area to help us coordinate the donation pickup.</div>
                        </div>
                        <input type="hidden" id="today" required name="date">
                        <button type="submit" name="add_donation" class="btn btn-primary w-100">Submit Donation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="liveToast" class="toast hide bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto text-white"><i class="fas fa-check-circle me-2"></i>Donation Status</strong>
            </div>
            <div class="toast-body">
                Thank you! Your donation has been recorded successfully.
            </div>
        </div>
    </div>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="errorToast" class="toast hide bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto text-white"><i class="fas fa-exclamation-circle me-2"></i>Donation Error</strong>
            </div>
            <div class="toast-body">
                Oops! There was an issue processing your donation. Please try again.
            </div>
        </div>
    </div>

    <div class="container-fluid py-5">
        <div class="container section-card">
            <div class="mx-auto text-center mb-5" style="max-width: 500px;">
                <h6 class="text-primary text-uppercase">Your Contribution</h6>
                <h1 class="display-5">My Donation History</h1>
            </div>
            <div class="row g-5">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="printTable2">
                            <thead class="table-primary">
                                <tr>
                                    <th scope="col">Donation ID</th>
                                    <th scope="col">Commodity</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Date Donated</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Re-using $conn from dbconnection.inc.php
                                // SQL query: Join `goods_donated` with `commodity` to get the Quantity with unit
                                $sql = "SELECT
                                            d.Dontation_ID,
                                            c.Commodity,
                                            c.Quantity AS Commodity_Full_Quantity, -- Get the full string from commodity table (e.g., '32 kg')
                                            d.Quantity AS Donated_Numeric_Quantity, -- The numeric quantity donated from goods_donated table
                                            d.Date_Donated,
                                            d.Location,
                                            d.Status
                                        FROM `goods_donated` d
                                        JOIN `commodity` c ON d.Commodity_ID = c.Commodity_ID
                                        WHERE d.Donor_ID = '" . mysqli_real_escape_string($conn, $donor_id_from_db) . "'";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row["Dontation_ID"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row["Commodity"]) . "</td>";

                                        // ********* PHP LOGIC TO EXTRACT UNIT FOR DISPLAY IN DONATION HISTORY (USING EXPLODE) *********
                                        $unit_for_display = '';
                                        $parts = explode(' ', $row["Commodity_Full_Quantity"]); // Split by space
                                        
                                        // If there's more than one part, assume the last part is the unit
                                        if (count($parts) > 1) {
                                            $potential_unit = end($parts); // Get the last part
                                            // Basic check: if the "unit" is actually a number, it's not a unit.
                                            // This prevents "32 50" from showing "50" as a unit.
                                            if (!is_numeric($potential_unit)) {
                                                $unit_for_display = $potential_unit;
                                            }
                                        }
                                        // ********* END PHP LOGIC *********

                                        // Concatenate the numeric donated quantity with the extracted unit
                                        echo "<td>" . htmlspecialchars($row["Donated_Numeric_Quantity"]) . " " . htmlspecialchars($unit_for_display) . "</td>";
                                        
                                        echo "<td>" . htmlspecialchars($row["Date_Donated"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row["Location"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row["Status"]) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>You have not made any donations yet.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <button onclick="printData2()" class="print-button">Print My Donation History</button>
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
                                <p class="text-white mb-0">donatefood@gmail.com</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-telephone text-white me-2"></i>
                                <p class="text-white mb-0">0745603353</p>
                            </div>
                            <div class="d-flex mt-4">
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="https://x.com/"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-secondary btn-square rounded-circle me-2" href="https://www.linkedin.com/"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <h4 class="text-white mb-4">Quick Links</h4>
                            <div class="d-flex flex-column justify-content-start">
                                <a class="text-white mb-2" href="index2.php"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
                                <a class="text-white mb-2" href="login_page.html"><i class="bi bi-arrow-right text-white me-2"></i>Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="index2.php">Food Donation System</a>. All Rights Reserved.</p>
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
        // Set today's date for the donation form
        document.addEventListener('DOMContentLoaded', function() {
            const todayInput = document.getElementById("today");
            if (todayInput) {
                const n = new Date();
                const y = n.getFullYear();
                const m = n.getMonth() + 1; // getMonth() is 0-indexed
                const d = n.getDate();
                // Format date as MM/DD/YYYY for insertion.inc.php which expects this format
                todayInput.value = m + "/" + d + "/" + y;
            }

            // --- Donation Status Toast Display ---
            const urlParams = new URLSearchParams(window.location.search);
            const donationStatus = urlParams.get('donation');
            const liveToast = document.getElementById('liveToast');
            const errorToast = document.getElementById('errorToast');

            if (donationStatus === 'success' && liveToast) {
                const toast = new bootstrap.Toast(liveToast);
                toast.show();
                // Optionally remove the query parameter to prevent re-showing on refresh
                history.replaceState({}, document.title, window.location.pathname);
            } else if (donationStatus === 'error' && errorToast) {
                const toast = new bootstrap.Toast(errorToast);
                toast.show();
                history.replaceState({}, document.title, window.location.pathname);
            }
            // --- End Donation Status Toast Display ---
        });


        // Populate donate modal fields
        var donateModal = document.getElementById('donateModal')
        if (donateModal) {
            donateModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var commodityId = button.getAttribute('data-commodity-id');
                var commodityName = button.getAttribute('data-commodity-name');
                // Get numeric quantity and unit from data attributes
                var numericQuantity = button.getAttribute('data-commodity-quantity-numeric');
                var unit = button.getAttribute('data-commodity-unit');
               
                var modalTitle = donateModal.querySelector('.modal-title');
                var modalCommodityIdInput = donateModal.querySelector('#commodity-id');
                var modalCommodityNameInput = donateModal.querySelector('#commodity-name');
                var modalQuantityInput = donateModal.querySelector('#quantity'); // Get the quantity input
                var modalQuantityUnitSpan = donateModal.querySelector('#quantity-unit'); // Get the new unit span

                modalTitle.textContent = 'Donate to ' + commodityName;
                modalCommodityIdInput.value = commodityId;
                modalCommodityNameInput.value = commodityName;
                modalQuantityInput.value = numericQuantity; // Set only the number to the input type="number"
                modalQuantityUnitSpan.textContent = unit; // Display the unit next to the input

                // Debug for modal - RETAIN FOR DIAGNOSIS IF MODAL UNIT ISSUE PERSISTS
                console.log('Modal Population - Numeric Quantity:', numericQuantity, 'Unit:', unit);
            });
        }

        // Print function for commodities table
        function printData1() {
            var divToPrint = document.getElementById("printTable1");
            var newWin = window.open("");
            newWin.document.write('<html><head><title>List of Commodities</title>');
            newWin.document.write('<link href="css/bootstrap.min.css" rel="stylesheet">'); // Include Bootstrap CSS for printing
            newWin.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #8BC34A; color: white; } </style>'); // Custom styles for print
            newWin.document.write('</head><body>');
            newWin.document.write('<h2>List of Commodities</h2>');
            newWin.document.write(divToPrint.outerHTML);
            newWin.document.close();
            newWin.print();
            newWin.close();
        }

        // Print function for donation history table
        function printData2() {
            var divToPrint = document.getElementById("printTable2");
            var newWin = window.open("");
            newWin.document.write('<html><head><title>My Donation History</title>');
            newWin.document.write('<link href="css/bootstrap.min.css" rel="stylesheet">'); // Include Bootstrap CSS for printing
            newWin.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #8BC34A; color: white; } </style>'); // Custom styles for print
            newWin.document.write('</head><body>');
            newWin.document.write('<h2>My Donation History</h2>');
            newWin.document.write(divToPrint.outerHTML);
            newWin.document.close();
            newWin.print();
            newWin.close();
        }
    </script>
</body>

</html>