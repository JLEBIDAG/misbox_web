<?php
header('Content-Type: application/json'); // Ensure JSON response
 // Ensure correct DB connection
 include '../inc/conn.php';
$sql = "SELECT ticket_no FROM tbl_tickets ORDER BY timestamp DESC LIMIT 1;";
$result = $conn->query($sql);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if ($result === false) {
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    exit;
}

if ($result->num_rows > 0) {
    $latestTicket = $result->fetch_assoc();
    echo json_encode(['ticket_no' => $latestTicket['ticket_no']]);
} else {
    echo json_encode(['ticket_no' => null]);
}
?>
