 <?php

     session_start();
      if(isset($_POST['submit'])){
        $password = $_POST['pass'];
        $passwordconfirm = $_POST['cpass'];

require_once 'dbconnection.inc.php';

$email = $_SESSION['Email'];

if (empty($password)){
  echo "Kindly input a Password.";
} else if (empty($passwordconfirm)){
  echo "Kindly input Confirm Password.";
} 
else if($password == $passwordconfirm){

$hashedpassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE `admin` SET `Password`='$hashedpassword' WHERE `Email_Address`= '$email'";

        $query = mysqli_query($conn,$sql);

        if ($conn->query($sql) === TRUE) {
  echo "New Password Recorded Successfully";
   header("Location: login.html");
   session_destroy();
 }
 else {
  echo "Error deleting record: " . $conn->error;
}
} 
}
?>
     
