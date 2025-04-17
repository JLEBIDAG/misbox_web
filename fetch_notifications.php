<?php
require 'inc/conn.php'; // Ensure this connects to your database

// Fetch notifications
$query = "SELECT PK_activityLogID, ticket_no, task_description, created_at FROM notifications_table ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($query);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['PK_activityLogID'],
        'ticket_no' => htmlspecialchars($row['ticket_no']),
        'task_description' => htmlspecialchars($row['task_description']),
        'created_at' => date("F j, Y, g:i a", strtotime($row['created_at']))
    ];
}

// Get total count of unread notifications
$countQuery = "SELECT COUNT(*) AS total FROM notifications_table WHERE is_read = 0";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalNotifications = $countRow['total'];

echo json_encode([
    'notifCount' => $totalNotifications,
    'notifications' => $notifications
]);
?>
