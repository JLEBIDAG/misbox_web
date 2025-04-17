
<?php
session_start();
include 'inc/conn.php';

$sql = "SELECT COUNT(*) AS total
        FROM activity_logs";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo $row['total']; // Output the count
} else {
    echo "0"; // If query fails, return 0
}
$conn->close();
?>
