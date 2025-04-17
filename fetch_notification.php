<?php
session_start();
include "inc/conn.php"; 

// Fetch unread notifications
$sql = "SELECT a.*, u.Username 
        FROM activity_logs a
        INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
        WHERE a.handle_by = ? AND a.is_viewed = 0 AND a.status != 'Completed'
        ORDER BY a.created_at";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => htmlspecialchars($row['PK_activityLogID']),
        'ticket_no' => htmlspecialchars($row['ticket_no']),
        'task_description' => htmlspecialchars(mb_strimwidth($row['task_description'], 0, 30, '...')),
        'time' => date("F j, Y, g:i a", strtotime($row['created_at']))
    ];
}

// Fetch total unread count
$totalUnread = $result->num_rows;

// Return JSON response
echo json_encode(['total' => $totalUnread, 'notifications' => $notifications]);

$stmt->close();
$conn->close();
?>
