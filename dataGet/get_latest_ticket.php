<?php
session_start();
include '../inc/conn.php';

// Query to get the last inserted ticket_no from the activity_logs table
$sql = "SELECT ticket_no FROM activity_logs ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);

// Check if we got a result
if ($result->num_rows > 0) {
    // Fetch the result and return the ticket_no
    $row = $result->fetch_assoc();
    echo $row['ticket_no'];
} else {
    // If no ticket is found, return an empty string
    echo '';
}

// Close the connection
$conn->close();
?>
