<?php
session_start();
include 'inc/conn.php'; // Make sure you have your DB connection setup


$sql = "SELECT a.ticket_no, a.task_description, a.dueDate, u.Username 
        FROM activity_logs a
        INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
        WHERE a.handle_by = ? AND a.status != 'Cancelled'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        "title" => $row['task_description'] . " - " . $row['ticket_no'],
        "start" => $row['dueDate'], // Ensure this is in YYYY-MM-DD format
        "className" => "fc-primary"
    ];
}

echo json_encode($events);
?>

