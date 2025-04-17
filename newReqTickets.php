<?php
include 'inc/conn.php';

$sql = "SELECT COUNT(*) as due_count FROM activity_logs WHERE handle_by IS NULL";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo $row['due_count']; // Output the count
} else {
    echo "0"; // If query fails, return 0
}

$conn->close();
?>
