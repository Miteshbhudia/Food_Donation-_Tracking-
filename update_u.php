<?php

require_once 'dbconnection.inc.php';

if (isset($_POST['submit'])) {

  $id = $_POST['did'];
  $fname = $_POST['fname'];
    $phone = $_POST['pho'];


  $sql = "UPDATE `donors` SET `Fullname`='$fname',`Phone_Number`='$pho' WHERE `User_ID` = '$id'";

  mysqli_query($conn, $sql);
   // var_dump($sql);
   // die();
  header("Location: index1.php?update=success");

}

if (isset($_POST['addloc'])) {

  $lid = $_POST['lid'];
  $location = $_POST['loc'];


  $sql = "UPDATE `goods_donated` SET `Location`='$location' WHERE `Dontation_ID` = '$lid'";

  mysqli_query($conn, $sql);
   // var_dump($sql);
   // die();
  header("Location: index.php");

}


?>