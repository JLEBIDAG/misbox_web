<?php
session_start();
include 'inc/conn.php';

$handle_by = $_SESSION['handle_by'] ?? ''; // Get handle_by from AJAX request

$data = [];

// Fetch Pie Chart Data
$query = "SELECT status, COUNT(*) AS total FROM activity_logs WHERE handle_by = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['pieChart'] = [];

while ($row = $result->fetch_assoc()) {
    $data['pieChart'][] = [
        'status' => $row['status'],
        'total' => $row['total']
    ];
}

// Fetch Bar Chart Data
$query = "SELECT 
            MONTH(created_at) AS month, 
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
          FROM activity_logs
          WHERE handle_by = ?
          GROUP BY MONTH(created_at)
          ORDER BY month";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['barChart'] = ['months' => [], 'completed' => [], 'cancelled' => []];

while ($row = $result->fetch_assoc()) {
    $data['barChart']['months'][] = date("M", mktime(0, 0, 0, $row['month'], 1));
    $data['barChart']['completed'][] = (int) $row['completed'];
    $data['barChart']['cancelled'][] = (int) $row['cancelled'];
}

// Return JSON data
echo json_encode($data);
?>