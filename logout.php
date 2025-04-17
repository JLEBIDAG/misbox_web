<?php

include('inc/conn.php');
session_start();    

// Update the is_login column to 0 for the logged-in user
$updateQuery = "UPDATE `tbl_users` SET `is_login` = 0 WHERE `PK_userID` = '" . $_SESSION['PK_userID'] . "'";
mysqli_query($conn, $updateQuery);

// Fetch user data (optional, not needed for the update, but keeping it if you need to use the data)
$uquery = mysqli_query($conn, "SELECT * FROM `tbl_users` WHERE `PK_userID`='" . $_SESSION['PK_userID'] . "'");
$urow = mysqli_fetch_assoc($uquery);


$_SESSION['PK_userID'] = $urow['PK_userID'];
$_SESSION['FirstName'] = $urow['FirstName'];
$_SESSION['LastName'] = $urow['LastName'];


date_default_timezone_set("Asia/Manila");
$date=date('F j, Y g:i:s a');

// $datetime = date('m/d/Y h:i:s a', time());

$userId = $urow['PK_userID'];
$desc = $urow['FirstName'] . ' ' . $urow['LastName'];

$action = "LOGOUT";

$insertQuery = mysqli_query($conn, "INSERT INTO `logs` (`FK_userID`, `action`, `description`, `datetime`) VALUE ('$userId', '$action', '$desc', '$date')");


// Destroy the session and log out the user
session_unset();
session_destroy();

// Optionally clear any session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the index page
header('location:index.php');
?>


?>