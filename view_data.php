<?php
include('inc/conn.php');

$id = $_POST['id'] ?? '';

// Prepare statement
$sql = "
SELECT 
    created_at,
    FK_userID,
    requestor_department,
    position,
    requestor_name,
    priority,
    status,
    dateTimeAccomp,
    handle_by,
    task_category,
    task_description,
    concern,
    resolution,
    is_viewed,
    dueDate,
    created_by
FROM activity_logs
WHERE PK_activityLogID = ? LIMIT 1";

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
