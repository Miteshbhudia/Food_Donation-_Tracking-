<?php
session_start();

// 1. If logged in as System Administrator
if (isset($_SESSION['adminname']) || (isset($_SESSION['Email']) && !isset($_SESSION['admin_location']) && !isset($_SESSION['donor_name']))) {
    header("Location: system_admin.php");
    exit();
}

// 2. If logged in as Area Administrator
if (isset($_SESSION['admin_location']) || isset($_SESSION['Email1'])) {
    header("Location: index1.php");
    exit();
}

// 3. If logged in as Donor
if (isset($_SESSION['donor_name']) || isset($_SESSION['Email2'])) {
    header("Location: index2.php");
    exit();
}

// 4. Default: If not logged in, take them to the public homepage
header("Location: homepage.html");
exit();
?>