<?php
include('inc/conn.php');

$id = $_POST['id'] ?? '';

// Prepare statement
$sql = "
SELECT t.*, a.*  
FROM ticket_status t
INNER JOIN activity_logs a ON t.FK_activityLogID = a.PK_activityLogID
WHERE a.PK_activityLogID = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id); // "i" means integer

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode($row);

// Close
$stmt->close();
$conn->close();
?>
