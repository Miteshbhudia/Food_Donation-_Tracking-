<?php
// Check if database exists and create it if it doesn't
$host = "localhost";
$user = "root"; // default XAMPP username
$pass = ""; // default XAMPP password
$db_name = "food_distribution";

// Create connection to MySQL server
$conn_server = new mysqli($host, $user, $pass);

// Check connection
if ($conn_server->connect_error) {
    die("Connection to database server failed: " . $conn_server->connect_error);
}

// Check if database exists
$db_exists = $conn_server->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");

if ($db_exists->num_rows == 0) {
    // Database doesn't exist, redirect to setup script
    $conn_server->close();
    header("Location: db_setup.php");
    exit();
} else {
    // Database exists, redirect to login page
    $conn_server->close();
    header("Location: login_page.html");
    exit();
}
?> 