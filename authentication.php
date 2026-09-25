<?php
include "dbconnection.inc.php";

session_start();

if(isset($_POST['ulogin'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    if(empty($email)){
        echo "Please input an email.";
    }else if(empty($password)){
        echo "Please enter your password."; 

    }else{

        $sql = "SELECT * FROM donors WHERE `Email_Address` ='$email'";
        // var_dump($sql);
        // die();
        
        $query = mysqli_query($conn,$sql);

        if(mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);
            
            if(password_verify($password, $row['Password'])){

                $_SESSION['Fname'] = $row['Donor_ID'];
                $_SESSION['Email'] = $row['Email_Address'];
                echo "Login Succesful.";
                header("Location: index2.php");
            }else{
                echo "Incorrect password.";
            }

        }else{
            echo "Donor does not exist.";
        }
    }
}


if(isset($_POST['alogin'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    if(empty($email)){
        echo "Please input an email.";
    }else if(empty($password)){
        echo "Please enter your password."; 

    }else{

        $sql = "SELECT * FROM `admin` WHERE `Email_Address`='$email'";
        // var_dump($sql);
        // die();
        
        $query = mysqli_query($conn,$sql);

        if(mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);
            
            if(password_verify($password, $row['Password'])){

                   $_SESSION['adminname1'] = $row['Administrator_ID'];
                $_SESSION['Email1'] = $row['Email_Address'];
                echo "Login Succesful.";
                header("Location: index1.php");
            }else{
                echo "Incorrect password.";
            }

        }else{
            echo "Area Administrator does not exist.";
        }
    }
}


    if(isset($_POST['slogin'])){

    $id = $_POST['email'];
    $password = $_POST['password'];

    if(empty($id)){
        echo "Please input an email.";
    }else if(empty($password)){
        echo "Please enter your password."; 
        
   }

    else{

        $sql = "SELECT * FROM `admin` WHERE `Email_Address`='$id'";

        $query = mysqli_query($conn,$sql);

        if(mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);
            if(password_verify($password, $row['Password'])){

                $_SESSION['adminname'] = $row['Fullname'];
                $_SESSION['Email'] = $row['Email_Address'];
                echo "Login Succesful.";
                header("Location: system_admin.php");
            }else{
                echo "Incorrect password.";
            }
}else{
    echo "System Administrator does not exist.";
    }
}
}
?>
