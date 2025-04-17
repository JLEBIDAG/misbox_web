<?php 
include('inc/conn.php');
session_start();
$username = $_SESSION['Username'];
// Get the most recent update time
 
$result = $conn->query("SELECT MAX(updated_at) as last_update FROM activity_logs WHERE handle_by = '$username'");

$row = $result->fetch_assoc();

echo $row['last_update'];

$conn->close();
?>
