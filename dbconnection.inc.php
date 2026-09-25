<?php
$host = "localhost";
$user = "root"; //edit if you have set a username for MySQL
$pass = ""; // edit if you have set a password
$db_name = "food_distribution";

// First try to connect to the server
$conn_server = new mysqli($host, $user, $pass);

// Check the server connection
if ($conn_server->connect_error) {
    die("Connection failed: " . $conn_server->connect_error);
}

// Check if database exists
$db_exists = $conn_server->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");

if ($db_exists->num_rows == 0) {
    // Database doesn't exist, redirect to setup script
    $conn_server->close();
    header("Location: db_setup.php");
    exit();
}

$conn_server->close();

// Create connection to the database
$conn = new mysqli($host, $user, $pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>