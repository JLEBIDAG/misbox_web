<?php
session_start();
include 'inc/conn.php';

$handle_by = $_SESSION['Username']; // Get the logged-in username

// Prepare the SQL statement
$sqlToday = "SELECT COUNT(*) AS total_tickets_today 
             FROM activity_logs 
             WHERE handle_by = ? AND status = 'Completed'
             AND DATE(created_at) = CURDATE()";

$stmt = $conn->prepare($sqlToday);
$stmt->bind_param("s", $handle_by); // Bind the username as a string
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $rowToday = $result->fetch_assoc();
    echo $rowToday['total_tickets_today']; // Output the correct column
} else {
    echo "0"; // If query fails, return 0
}

// Close statement
$stmt->close();
?>