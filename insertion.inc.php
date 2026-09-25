<?php
session_start();
require_once 'dbconnection.inc.php'; // Ensure this path is correct

// --- Donor Registration (Existing code from your submission) ---
if(isset($_POST["reg"])){
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $question = mysqli_real_escape_string($conn, $_POST['rq']);
    $answer = mysqli_real_escape_string($conn, $_POST['ra']);
    $password = $_POST['password']; // Raw password
    $passconfirm = $_POST['cpassword']; // Raw password confirmation

    if (empty($password)){
        echo "Kindly input a password.";
    } else if ($password == $passconfirm) {
        $hashedpassword = password_hash($password, PASSWORD_DEFAULT);

        // Using prepared statement for security
        $stmt = $conn->prepare("INSERT INTO `donors`(`Fullname`, `Email_Address`, `Phone_Number`, `Recovery_Question`, `Recovery_Answer`, `Password`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fname, $email, $phone, $question, $answer, $hashedpassword);

        if ($stmt->execute()) {
            header("Location: login.html?signup=success");
            exit(); // Always exit after header redirect
        } else {
            // Error handling for database insertion
            error_log("Donor registration failed: " . $stmt->error);
            echo "Error during registration. Please try again.";
        }
        $stmt->close();
    } else {
        echo "Passwords do not match.";
    }
}

// --- Admin Addition (Existing code from your submission, improved security) ---
if (isset($_POST['adda'])) {
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $question = mysqli_real_escape_string($conn, $_POST['rq']);
    $answer = mysqli_real_escape_string($conn, $_POST['ra']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if ($password == $cpassword) {
        $hashedpassword = password_hash($password, PASSWORD_DEFAULT);

        // Using prepared statement for security
        $stmt = $conn->prepare("INSERT INTO `admin`(`Fullname`, `Email_Address`, `Position`, `Location`, `Recovery_Question`, `Recovery_Answer`, `Password`) VALUES (?, ?,'Area Administrator', ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fname, $email, $location, $question, $answer, $hashedpassword);

        if ($stmt->execute()) {
            header("Location: index.php"); // Assuming this redirects to an admin homepage
            exit();
        } else {
            error_log("Admin registration failed: " . $stmt->error);
            echo "Error during admin registration. Please try again.";
        }
        $stmt->close();
    } else {
        echo "Passwords do not match.";
    }
}

// --- Commodity Addition (Existing code from your submission, improved security) ---
if (isset($_POST['addc'])) {
    $adr = mysqli_real_escape_string($conn, $_POST['adr']); // Area_Administrator_ID assumed
    $com = mysqli_real_escape_string($conn, $_POST['com']);
    $num = mysqli_real_escape_string($conn, $_POST['number']); // Quantity string, e.g., "32 kg"
    $date1 = mysqli_real_escape_string($conn, $_POST['date1']);

    // Using prepared statement for security
    $stmt = $conn->prepare("INSERT INTO `commodity`(`Area_Administrator`, `Commodity`, `Quantity`, `Date_Added`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $adr, $com, $num, $date1); // 'i' for adr if it's an integer ID

    if ($stmt->execute()) {
        header("Location: index1.php"); // Assuming this redirects to an admin commodity page
        exit();
    } else {
        error_log("Commodity addition failed: " . $stmt->error);
        echo "Error adding commodity. Please try again.";
    }
    $stmt->close();
}


// --- DONATION SUBMISSION (The critical part for your issue) ---
if (isset($_POST['add_donation'])) {
    $commodity_id = mysqli_real_escape_string($conn, $_POST['commodity_id']);
    $donor_id = mysqli_real_escape_string($conn, $_POST['donor_id']);
    $quantity_donated_numeric = mysqli_real_escape_string($conn, $_POST['quantity']); // This is the NUMERIC quantity from the donor
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $date_donated_raw = $_POST['date']; // From index2.php, format MM/DD/YYYY

    // Convert date to YYYY-MM-DD for MySQL
    $date_obj = DateTime::createFromFormat('m/d/Y', $date_donated_raw);
    if ($date_obj) {
        $date_donated_formatted = $date_obj->format('Y-m-d');
    } else {
        // Fallback or error handling if date format is incorrect
        error_log("Invalid date format received for donation: " . $date_donated_raw);
        $date_donated_formatted = date('Y-m-d'); // Use current date as fallback
    }

    $status = "Pending Pickup"; // Default status

    // Insert into goods_donated table using prepared statement for security
    $stmt_insert = $conn->prepare("INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`, `Status`) VALUES (?, ?, ?, ?, ?, ?)");
    // 'i' for int, 's' for string
    $stmt_insert->bind_param("iissss", $commodity_id, $donor_id, $quantity_donated_numeric, $date_donated_formatted, $location, $status);

    if ($stmt_insert->execute()) {
        // Only attempt to update commodity quantity if the donation insertion was successful
        // This update needs to subtract from the *numeric* part of the Quantity string in the commodity table.
        // This is complex if Quantity is stored as "32 kg".
        // A better long-term solution is to store numeric quantity and unit separately in the `commodity` table.

        // For now, we need to fetch the current full quantity string, parse it, subtract, and then put it back.
        // This is prone to race conditions and data corruption if not handled carefully in a high-traffic environment.
        
        // Step 1: Get current commodity quantity string
        $stmt_get_current_qty = $conn->prepare("SELECT Quantity FROM `commodity` WHERE `Commodity_ID` = ?");
        $stmt_get_current_qty->bind_param("i", $commodity_id);
        $stmt_get_current_qty->execute();
        $result_current_qty = $stmt_get_current_qty->get_result();
        $row_current_qty = $result_current_qty->fetch_assoc();
        $current_full_quantity_str = $row_current_qty['Quantity'] ?? ''; // e.g., "32 kg"
        $stmt_get_current_qty->close();

        // Step 2: Parse current quantity string to get numeric value and unit
        $current_numeric_qty = 0;
        $unit_from_commodity = '';
        if (preg_match('/^(\d+(\.\d+)?)\s*([a-zA-Z]+)?$/', $current_full_quantity_str, $matches)) {
            $current_numeric_qty = (float)$matches[1];
            $unit_from_commodity = isset($matches[3]) ? $matches[3] : '';
        }

        // Step 3: Calculate new numeric quantity
        $new_numeric_qty = $current_numeric_qty - (float)$quantity_donated_numeric;

        // Ensure quantity doesn't go below zero
        if ($new_numeric_qty < 0) {
            $new_numeric_qty = 0;
        }

        // Step 4: Reconstruct the new full quantity string
        $new_full_quantity_str = $new_numeric_qty . ($unit_from_commodity ? ' ' . $unit_from_commodity : '');

        // Step 5: Update the commodity quantity in the commodity table
        $stmt_update_commodity = $conn->prepare("UPDATE `commodity` SET `Quantity` = ? WHERE `Commodity_ID` = ?");
        $stmt_update_commodity->bind_param("si", $new_full_quantity_str, $commodity_id);
        
        if (!$stmt_update_commodity->execute()) {
            // Log error if commodity update fails, but don't prevent redirect for donation success
            error_log("Commodity quantity update failed for ID " . $commodity_id . ": " . $stmt_update_commodity->error);
        }
        $stmt_update_commodity->close();

        // Redirect on success
        header("Location: index2.php?donation=success");
        exit();
    } else {
        // Log error for donation insertion failure
        error_log("Donation insertion failed: " . $stmt_insert->error);
        header("Location: index2.php?donation=error");
        exit();
    }

    $stmt_insert->close();
}

$conn->close(); // Close connection at the end of the script
?>