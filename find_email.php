<?php
require_once "dbconnection.inc.php";

session_start();

if(isset($_POST['submit'])){

    $email = $_POST['email'];

    if(empty($email)){
        echo "Please input an email.";
    }else{

        $sql = "SELECT * FROM `donors` WHERE `Email_Address`='$email'";

        $query = mysqli_query($conn, $sql);

        if(mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);
            
                $_SESSION['rq'] = $row['Recovery_Question'];
                $_SESSION['Email'] = $row['Email_Address'];
                header("Location: recover_u.php");
            }else{
                echo "Incorrect Email.";
            }

        }
    }
?>
