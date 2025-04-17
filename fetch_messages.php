<?php
session_start();
include "inc/conn.php"; // Adjust the path to your database connection file

// Fetch unread messages
$sqlMessage = "SELECT m.*, u.* 
               FROM tbl_messsages m
               INNER JOIN tbl_users u ON m.from_userID = u.PK_userID
               WHERE to_user = ? AND is_read = 0 
               ORDER BY m.created_at DESC LIMIT 3";

$stmtMess = $conn->prepare($sqlMessage);
$stmtMess->bind_param("s", $_SESSION['Username']);
$stmtMess->execute();
$resultMessg = $stmtMess->get_result();

$messages = [];
while ($row = $resultMessg->fetch_assoc()) {
    $messages[] = [
        'profile' => htmlspecialchars($row['profile']),
        'from_user' => htmlspecialchars($row['from_user']),
        'message' => htmlspecialchars(mb_strimwidth($row['messages'], 0, 30, '...')),
        'time' => date("F j, Y, g:i a", strtotime($row['created_at']))
    ];
}

// Fetch total unread count
$totalUnread = $resultMessg->num_rows;

// Return JSON response
echo json_encode(['total' => $totalUnread, 'messages' => $messages]);

$stmtMess->close();
$conn->close();
?>
