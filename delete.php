<?php

if (isset($_POST['add'])) {
  require 'dbconnection.inc.php';
  session_start();
  $id4 = $_POST['id4'];
  $id5 = $_POST['id5'];
  $quan = $_POST['quan'];
    $date = $_POST['date'];

  $sql = "SELECT * FROM `commodity` WHERE `Commodity_ID` = '$id4'";
  $query = mysqli_query($conn,$sql);

        if(mysqli_num_rows($query) > 0){
            $row = mysqli_fetch_assoc($query);

           $_SESSION['num'] = $row['Quantity'];
$null = 0;
$num = $_SESSION['num'];
$res = $num - $quan;

// echo $null;
// echo $res;
if ($res > $null) {
  $sql = "INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`) VALUES ('$id4','$id5','$quan','$date','N/A')";
$sql1 = "UPDATE `commodity` SET `Quantity`='$res' WHERE `Commodity_ID` = '$id4'";
  mysqli_query($conn, $sql);
  mysqli_query($conn, $sql1);
   // var_dump($sql);
   // die();
  header("Location: index2.php");

}else
  {
  $sql = "INSERT INTO `goods_donated`(`Commodity_ID`, `Donor_ID`, `Quantity`, `Date_Donated`, `Location`) VALUES ('$id4','$id5','$quan','$date','N/A')";
$sql1 = "DELETE FROM `commodity` WHERE `Commodity_ID` = '$id4'";
  mysqli_query($conn, $sql);
  mysqli_query($conn, $sql1);
   // var_dump($sql);
   // die();
  header("Location: index2.php");

}}else{
                echo "An error occured.";
            }

}

if (isset($_POST['deld'])) {
  require 'dbconnection.inc.php';

  $id2 = $_POST['id2'];

  if (empty($id2)) {
    echo "Please Input a Donation ID.";
  }else{
    $sql = "DELETE FROM `goods_donated` WHERE `goods_donated`.`Dontation_ID` = '$id2'";
    mysqli_query($conn, $sql);
    header("Location: index.php");
  }
}

if (isset($_POST['dela'])) {
  require 'dbconnection.inc.php';

  $id3 = $_POST['id3'];

  if (empty($id3)) {
    echo "Please Input a Administrator ID.";
  }else{
    $sql = "DELETE FROM `admin` WHERE `Administrator_ID` = '$id3'";
    mysqli_query($conn, $sql);
    header("Location: index.php");
  }
}

if (isset($_POST['delc'])) {
  require 'dbconnection.inc.php';

  $id1 = $_POST['id1'];

  if (empty($id1)) {
    echo "Please Input a Commodity ID.";
  }else{
    $sql = "DELETE FROM `commodity` WHERE `Commodity_ID` = '$id1'";
    mysqli_query($conn, $sql);
    header("Location: index.php");
  }
}

if (isset($_POST['delc1'])) {
  require 'dbconnection.inc.php';

  $id1 = $_POST['id1'];

  if (empty($id1)) {
    echo "Please Input a Commodity ID.";
  }else{
    $sql = "DELETE FROM `commodity` WHERE `Commodity_ID` = '$id1'";
    mysqli_query($conn, $sql);
    header("Location: index1.php");
  }
}

if (isset($_POST['delu'])) {
  require 'dbconnection.inc.php';

  $id = $_POST['id'];

  if (empty($id)) {
    echo "Please Input a Donor ID.";
  }else{
    $sql = "DELETE FROM `users` WHERE `Donor_ID` = '$id'";
    mysqli_query($conn, $sql);
    header("Location: index.php");
  }
}
?>