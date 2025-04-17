<?php
include('inc/conn.php');
session_start();
$username = $_SESSION['Username'];
$result = $conn->query("SELECT MAX(PK_activityLogID) as latest_id FROM activity_logs where handle_by = handle_by = '$username'");
$row = $result->fetch_assoc();

echo $row['latest_id'];

$conn->close();
?>
