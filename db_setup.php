<?php

$host = "localhost";
$user = "root"; // default XAMPP username
$pass = ""; // default XAMPP password

// Create connection to MySQL server
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection to MySQL server failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS food_distribution";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

$conn->select_db("food_distribution");

// Create donors table
$sql = "CREATE TABLE IF NOT EXISTS `donors` (
  `Donor_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Fullname` varchar(255) NOT NULL,
  `Email_Address` varchar(255) NOT NULL,
  `Phone_Number` varchar(20) NOT NULL,
  `Recovery_Question` varchar(255) NOT NULL,
  `Recovery_Answer` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`Donor_ID`),
  UNIQUE KEY `Email_Address` (`Email_Address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) !== TRUE) {
    die("Error creating donors table: " . $conn->error);
}

//  admin table
$sql = "CREATE TABLE IF NOT EXISTS `admin` (
  `Administrator_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Fullname` varchar(255) NOT NULL,
  `Email_Address` varchar(255) NOT NULL,
  `Position` varchar(50) NOT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `Recovery_Question` varchar(255) NOT NULL,
  `Recovery_Answer` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`Administrator_ID`),
  UNIQUE KEY `Email_Address` (`Email_Address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) !== TRUE) {
    die("Error creating admin table: " . $conn->error);
}

// Add location column to existing admin table if it doesn't exist
$check_column = "SHOW COLUMNS FROM `admin` LIKE 'Location'";
$column_result = $conn->query($check_column);
if ($column_result->num_rows == 0) {
    $alter_sql = "ALTER TABLE `admin` ADD COLUMN `Location` varchar(255) DEFAULT NULL AFTER `Position`";
    if ($conn->query($alter_sql) !== TRUE) {
        echo "Error adding location column: " . $conn->error;
    }
}

// commodity table
$sql = "CREATE TABLE IF NOT EXISTS `commodity` (
  `Commodity_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Area_Administrator` varchar(50) NOT NULL,
  `Commodity` varchar(255) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Date_Added` date NOT NULL,
  PRIMARY KEY (`Commodity_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) !== TRUE) {
    die("Error creating commodity table: " . $conn->error);
}

//  goods_donated table
$sql = "CREATE TABLE IF NOT EXISTS `goods_donated` (
  `Dontation_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Commodity_ID` int(11) NOT NULL,
  `Donor_ID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Date_Donated` date NOT NULL,
  `Location` varchar(255) NOT NULL,
  PRIMARY KEY (`Dontation_ID`),
  KEY `Commodity_ID` (`Commodity_ID`),
  KEY `Donor_ID` (`Donor_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) !== TRUE) {
    die("Error creating goods_donated table: " . $conn->error);
}

// Create default System Administrator if it doesn't exist
$check_admin = "SELECT * FROM `admin` WHERE `Position` = 'System Administrator' LIMIT 1";
$result = $conn->query($check_admin);

if ($result->num_rows == 0) {
    $default_password = password_hash("admin123", PASSWORD_DEFAULT);
    $create_admin = "INSERT INTO `admin` (`Fullname`, `Email_Address`, `Position`, `Recovery_Question`, `Recovery_Answer`, `Password`) 
                    VALUES ('System Admin', 'admin@fooddistribution.com', 'System Administrator', 'What is the default password?', 'admin123', '$default_password')";
    
    if ($conn->query($create_admin) !== TRUE) {
        echo "Error creating default admin: " . $conn->error;
    }
}

// Create default Area Administrator if it doesn't exist
$check_area_admin = "SELECT * FROM `admin` WHERE `Position` = 'Area Administrator' LIMIT 1";
$result_area = $conn->query($check_area_admin);

if ($result_area->num_rows == 0) {
    $area_password = password_hash("area123", PASSWORD_DEFAULT);
    $create_area_admin = "INSERT INTO `admin` (`Fullname`, `Email_Address`, `Position`, `Location`, `Recovery_Question`, `Recovery_Answer`, `Password`) 
                         VALUES ('Area Admin', 'areaadmin@fooddistribution.com', 'Area Administrator', 'Nairobi County', 'What is the default area password?', 'area123', '$area_password')";
    
    if ($conn->query($create_area_admin) !== TRUE) {
        echo "Error creating default area admin: " . $conn->error;
    }
}

$conn->close();

header("Location: login.html");
exit();
?> 