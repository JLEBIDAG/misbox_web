<?php
session_start();
include 'inc/conn.php';

$handle_by = $_SESSION['Username']; // Get the logged-in username

// Prepare the SQL statement
$sqlWeek = "SELECT COUNT(*) AS total_tickets_month 
             FROM activity_logs 
             WHERE handle_by = ? AND status = 'Completed'
             AND YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)";

$stmt = $conn->prepare($sqlWeek);
$stmt->bind_param("s", $handle_by); // Bind the username as a string
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $rowWeek = $result->fetch_assoc();
    echo $rowWeek['total_tickets_month']; // Output the correct column
} else {
    echo "0"; // If query fails, return 0
}

// Close statement
$stmt->close();
?>