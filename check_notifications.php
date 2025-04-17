<?php

// Include your database connection
include 'inc/conn.php';

// Query to get unread notifications count (modify table/column names as needed)
$sql = "SELECT COUNT(*) as count FROM activity_logs a 
INNER JOIN tbl_users u ON a.FK_UserID = u.PK_userID 
WHERE a.is_viewed = 0 AND a.handle_by = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();

$result =$stmt->get_result();
$row = $result->fetch_assoc();

// Return JSON response
echo json_encode(['count' => $row['count']]);

$conn->close();
?>
