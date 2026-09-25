<?php
require_once 'dbconnection.inc.php';
session_start();

if (!isset($_SESSION['Email1']) && !isset($_SESSION['adminname1'])) {
    header("Location: login.html");
    exit(); // Always exit after a header redirect
} else {
    $email = $_SESSION['Email1'];
    // Use prepared statements for security
    $stmt = mysqli_prepare($conn, "SELECT Fullname, Location, Administrator_ID FROM `admin` WHERE `Email_Address` = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $query_result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($query_result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $admin_fullname = $row['Fullname'];
        $admin_location = $row['Location'];
        $admin_id = $row['Administrator_ID'];
    } else {
        // Handle case where admin is not found, perhaps redirect to login
        session_unset();
        session_destroy();
        header("Location: login.html");
        exit();
    }
}

// Handle status update POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $donation_id = $_POST['donation_id'];
    $new_status = $_POST['status'];

    // Validate input to prevent SQL injection or invalid status values
    $allowed_statuses = ['Pending Pickup', 'Collected', 'Delivered']; // Define your allowed statuses. Ensure these match your actual statuses.
    if (in_array($new_status, $allowed_statuses)) {
        // Re-establish connection for the update if $conn might be closed from other operations.
        // Or, ideally, ensure $conn remains open for the entire script execution.
        // For consistency with your original approach, we'll keep a separate connection here for updates.
        $conn_update = mysqli_connect("localhost", "root", "", "food_distribution");
        if ($conn_update->connect_error) {
            die("Connection failed: " . $conn_update->connect_error);
        }

        $stmt_update = mysqli_prepare($conn_update, "UPDATE `goods_donated` SET `Status` = ? WHERE `Dontation_ID` = ?");
        mysqli_stmt_bind_param($stmt_update, "si", $new_status, $donation_id);

        if (mysqli_stmt_execute($stmt_update)) {
            // Status updated successfully. Redirect to prevent resubmission on refresh.
            header("Location: index1.php?status_updated=success");
            exit();
        } else {
            // Error handling
            error_log("Error updating donation status: " . mysqli_error($conn_update));
            // You might want to add a user-friendly error message here
        }
        mysqli_stmt_close($stmt_update);
        $conn_update->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Food Donation System - Area Administrator Homepage</title>
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

    <style type="text/css">
        /* Custom Table Styles for a modern look */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            background-color: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        th, tr, td {
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #343a40;
            border-bottom: 2px solid #dee2e6;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e9ecef;
        }

        /* Specific styles for buttons to make them cohesive */
        .print-button, .add-commodity-link, .delete-commodity-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }

        .print-button {
            background-color: #0d6efd;
            color: #fff;
            border: 1px solid #0d6efd;
        }
        .print-button:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .add-commodity-link {
            background-color: #198754;
            color: #fff;
            border: 1px solid #198754;
        }
        .add-commodity-link:hover {
            background-color: #157347;
            border-color: #146c43;
        }

        .delete-commodity-btn {
            background-color: #dc3545;
            color: #fff;
            border: 1px solid #dc3545;
        }
        .delete-commodity-btn:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }

        /* Styling for form elements */
        .delete-form .form-control {
            width: auto;
            display: inline-block;
            margin-right: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            color: #212529;
            background-color: #fff;
            font-size: 1rem;
            line-height: 1.5;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .delete-form .form-control:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Adjustments for section headings */
        .display-5 {
            font-size: calc(1.425rem + 2.1vw);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 2rem;
            color: #343a40;
        }

        /* General container padding */
        .py-5 {
            padding-top: 3rem !important;
            padding-bottom: 3rem !important;
        }
        .mb-5 {
            margin-bottom: 3rem !important;
        }
        .mt-5 {
            margin-top: 3rem !important;
        }

        /* --- NEW STYLES FOR CAROUSEL IMAGE AND OVERLAY --- */
        .carousel-item {
            position: relative; /* Needed for positioning the overlay */
        }

        .carousel-item img {
            width: 100%;        /* Ensures it fills the width of its container */
            height: 500px;      /* Set a fixed height for the carousel image */
            object-fit: cover;  /* Crucial: Covers the area while maintaining aspect ratio, cropping if needed */
        }

        .carousel-caption::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5); /* Black overlay with 50% opacity */
            z-index: 0; /* Ensures it's behind the text but over the image */
        }

        .carousel-caption h3,
        .carousel-caption h1,
        .carousel-caption p {
            color: #FFFFFF !important; /* Changed text color to white and made it important to override existing styles */
            position: relative; /* Brings text above the overlay */
            z-index: 1; /* Ensures text is on top of the overlay */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7); /* Add a subtle text shadow for better readability */
        }

        /* --- LOGOUT BUTTON SPECIFIC STYLES --- */
        .carousel-caption .btn-primary {
            position: relative; /* This is key! Makes z-index effective */
            z-index: 2; /* Ensures the button is above the overlay and text */
            /* You can add more styling here if you want to modify its appearance further */
        }
        /* --- END NEW STYLES --- */
    </style>
</head>

<body>

    <div class="container-fluid px-5 d-none d-lg-block">
        <div class="row gx-5 py-3 align-items-center">
            <div class="col-lg-12 text-center"> <div class="d-flex align-items-center justify-content-center">
                    <a href="index1.php" class="navbar-brand m-0"> <h1 class="display-4 text-primary"><span class="text-secondary">Food</span> Donation</h1>
                    </a>
                </div>
            </div>
            </div>
    </div>
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm py-3 py-lg-0 px-3 px-lg-5">
        <a href="index1.php" class="navbar-brand d-flex d-lg-none">
            <h1 class="m-0 display-4 text-secondary"><span class="text-white">Food</span> Donation</h1>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav mx-auto py-0">
                <a href="index1.php" class="nav-item nav-link active">Home</a>
                <a href="login_page.html" class="nav-item nav-link">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-0">
        <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100 carousel-img" src="https://images.pexels.com/photos/9090854/pexels-photo-9090854.jpeg" alt="Food Donation Image">
                    <div class="carousel-caption top-0 bottom-0 start-0 end-0 d-flex flex-column align-items-center justify-content-center">
                        <div class="text-start p-5" style="max-width: 900px;">
                            <h3 class="text-white">Food Donation</h3>
                            <h1 class="display-1 text-white mb-md-4">Welcome <?php echo htmlspecialchars($admin_fullname); ?></h1>
                            <?php if (!empty($admin_location)): ?>
                            <p class="text-white mb-3"><i class="bi bi-geo-alt me-2"></i>Managing: <?php echo htmlspecialchars($admin_location); ?></p>
                            <?php endif; ?>
                            <a href="login_page.html" class="btn btn-primary py-md-3 px-md-5 me-3">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid py-5" id="service">
        <div class="container">
            <div class="mx-auto text-center mb-5" style="max-width: 700px;">
                <h1 class="display-5">List of Commodities in Your Area</h1>
            </div>
            <div class="row g-5 justify-content-center">
                <div class="col-lg-10 col-md-12">
                    <table id="printTable1" class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Commodity ID</th>
                                <th>Area Administrator ID</th>
                                <th>Location</th>
                                <th>Commodity</th>
                                <th>Quantity</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Re-establish connection for the first table
                            $conn_table1 = mysqli_connect("localhost", "root", "", "food_distribution");

                            // Check connection
                            if ($conn_table1->connect_error) {
                                die("Connection failed: " . $conn_table1->connect_error);
                            }

                            $stmt_commodities = mysqli_prepare($conn_table1, "SELECT c.Commodity_ID, c.Area_Administrator, a.Location, c.Commodity, c.Quantity, c.Date_Added
                                FROM `commodity` c
                                JOIN `admin` a ON c.Area_Administrator = a.Administrator_ID
                                WHERE c.Area_Administrator = ?");
                            mysqli_stmt_bind_param($stmt_commodities, "i", $admin_id); // Assuming admin_id is an integer
                            mysqli_stmt_execute($stmt_commodities);
                            $result_commodities = mysqli_stmt_get_result($stmt_commodities);

                            if ($result_commodities->num_rows > 0) {
                                while($row_commodity = $result_commodities->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row_commodity["Commodity_ID"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_commodity["Area_Administrator"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_commodity["Location"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_commodity["Commodity"]) . "</td>";
                                    // This line will display the quantity with the unit, e.g., "32 kg"
                                    echo "<td>" . htmlspecialchars($row_commodity["Quantity"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_commodity["Date_Added"]) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted'>No commodities found for this area.</td></tr>";
                            }
                            mysqli_stmt_close($stmt_commodities);
                            $conn_table1->close();
                            ?>
                        </tbody>
                    </table>
                    <div class="d-flex flex-column flex-md-row justify-content-center align-items-center mt-4">
                        <button onclick="printData1()" class="print-button btn btn-primary mb-3 me-md-3 w-100 w-md-auto">Print List of Commodities</button>
                        <a href="commodity.php" class="add-commodity-link btn btn-success mb-3 w-100 w-md-auto">Add a New Commodity</a>
                    </div>
                    <div class="mt-4 text-center">
                        <label class="d-block mb-3 text-muted">To delete a Commodity, kindly input the Commodity ID in the field below:</label>
                        <form method="POST" action="delete.php" class="d-flex flex-column flex-md-row justify-content-center align-items-center delete-form">
                            <input type="text" required name="id1" class="form-control me-md-2 mb-2 mb-md-0" placeholder="Commodity ID...">
                            <button type="submit" name="delc1" class="delete-commodity-btn btn btn-danger">Delete Commodity</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid py-5">
        <div class="container">
            <div class="mx-auto text-center mb-5" style="max-width: 700px;">
                <h1 class="display-5">List of Goods Donated</h1>
                <p class="lead text-muted">Overview of all goods that have been donated, with status management.</p>
            </div>
            <div class="row g-5 justify-content-center">
                <div class="col-lg-12 col-md-12">
                    <table id="printTable2" class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Donation ID</th>
                                <th>Commodity ID</th>
                                <th>Donor ID</th>
                                <th>Commodity Name</th>
                                <th>Quantity Donated</th>
                                <th>Date Donated</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Re-establish connection for the second table as the previous one was closed
                            $conn_table2 = mysqli_connect("localhost", "root", "", "food_distribution");
                            // Check connection
                            if ($conn_table2->connect_error) {
                                die("Connection failed: " . $conn_table2->connect_error);
                            }

                            // MODIFIED: Fetch data with JOIN to get Commodity Name and the original commodity's quantity string
                            $sql_donations = "SELECT
                                                gd.Dontation_ID,
                                                gd.Commodity_ID,
                                                gd.Donor_ID,
                                                c.Commodity AS Commodity_Name,
                                                gd.Quantity AS Donated_Quantity_Numeric,
                                                c.Quantity AS Original_Commodity_Quantity_String,
                                                gd.Date_Donated,
                                                gd.Location,
                                                gd.Status
                                              FROM `goods_donated` gd
                                              JOIN `commodity` c ON gd.Commodity_ID = c.Commodity_ID";
                            $result_donations = $conn_table2->query($sql_donations);

                            if ($result_donations->num_rows > 0) {
                                while($row_donation = $result_donations->fetch_assoc()) {
                                    // Extract unit from Original_Commodity_Quantity_String (e.g., "32 kg" -> "kg")
                                    $unit = '';
                                    if (preg_match('/^\d+(\.\d+)?\s*([a-zA-Z]+)?$/', $row_donation['Original_Commodity_Quantity_String'], $matches)) {
                                        $unit = isset($matches[2]) ? $matches[2] : ''; // Capture the unit part
                                    }

                                    // Construct the display string for Quantity
                                    $display_quantity = htmlspecialchars($row_donation["Donated_Quantity_Numeric"]);
                                    if (!empty($unit)) {
                                        $display_quantity .= " " . htmlspecialchars($unit);
                                    }

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row_donation["Dontation_ID"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_donation["Commodity_ID"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_donation["Donor_ID"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_donation["Commodity_Name"]) . "</td>";
                                    echo "<td>" . $display_quantity . "</td>"; // Display the quantity with its unit
                                    echo "<td>" . htmlspecialchars($row_donation["Date_Donated"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_donation["Location"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row_donation["Status"]) . "</td>"; // Display the status

                                    // Add the status update form
                                    echo "<td>";
                                    echo "<form method='POST' action='' class='d-flex align-items-center'>";
                                    echo "<input type='hidden' name='donation_id' value='" . htmlspecialchars($row_donation["Dontation_ID"]) . "'>";
                                    echo "<select name='status' class='form-select form-select-sm me-2'>";
                                    echo "<option value='Pending Pickup'" . ($row_donation["Status"] == 'Pending Pickup' ? ' selected' : '') . ">Pending Pickup</option>";
                                    echo "<option value='Collected'" . ($row_donation["Status"] == 'Collected' ? ' selected' : '') . ">Collected</option>";
                                    echo "<option value='Delivered'" . ($row_donation["Status"] == 'Delivered' ? ' selected' : '') . ">Delivered</option>";
                                    echo "</select>";
                                    echo "<button type='submit' name='update_status' class='btn btn-sm btn-primary'>Update</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center text-muted'>0 results - No goods donated yet.</td></tr>"; // colspan updated
                            }
                            $conn_table2->close();
                            ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center mt-4">
                        <button onclick="printData2()" class="print-button btn btn-primary mb-3">Print List of Goods Donated</button>
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
                                <a class="text-white mb-2" href="index1.php"><i class="bi bi-arrow-right text-white me-2"></i>Home</a>
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
           <p class="mb-0">&copy; <a class="text-secondary fw-bold" href="index1.php">Food Donation System</a>. All Rights Reserved.</p>
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
        function printData1() {
            var divToPrint = document.getElementById("printTable1");
            newWin = window.open("");
            newWin.document.write('<html><head><title>Commodities List</title>');
            newWin.document.write('<link href="css/bootstrap.min.css" rel="stylesheet">'); // Include Bootstrap for print styles
            newWin.document.write('<style>');
            newWin.document.write(`
                body { font-family: 'Open Sans', sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; color: #343a40; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; background-color: #fff; box-shadow: none; border-radius: 0; }
                th, tr, td { border: 1px solid #dee2e6; padding: 12px 15px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: 600; color: #343a40; border-bottom: 2px solid #dee2e6; }
                tr:nth-child(even) { background-color: #f2f2f2; }
            `);
            newWin.document.write('</style></head><body>');
            newWin.document.write('<h1>List of Commodities for ' + <?php echo json_encode($admin_location); ?> + '</h1>'); // Add a title for print
            newWin.document.write(divToPrint.outerHTML);
            newWin.document.write('</body></html>');
            newWin.print();
            newWin.close();
        }

        function printData2() {
            var divToPrint = document.getElementById("printTable2");
            newWin = window.open("");
            newWin.document.write('<html><head><title>Goods Donated List</title>');
            newWin.document.write('<link href="css/bootstrap.min.css" rel="stylesheet">'); // Include Bootstrap for print styles
            newWin.document.write('<style>');
            newWin.document.write(`
                body { font-family: 'Open Sans', sans-serif; margin: 20px; }
                h1 { text-align: center; margin-bottom: 20px; color: #343a40; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; background-color: #fff; box-shadow: none; border-radius: 0; }
                th, tr, td { border: 1px solid #dee2e6; padding: 12px 15px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: 600; color: #343a40; border-bottom: 2px solid #dee2e6; }
                tr:nth-child(even) { background-color: #f2f2f2; }
            `);
            newWin.document.write('</style></head><body>');
            newWin.document.write('<h1>List of Goods Donated</h1>'); // Add a title for print
            newWin.document.write(divToPrint.outerHTML);
            newWin.document.write('</body></html>');
            newWin.print();
            newWin.close();
        }
    </script>
</body>

</html>