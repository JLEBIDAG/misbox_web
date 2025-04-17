<?php
include 'inc/conn.php';
$handle_by = $_SESSION['Username']; // Adjust based on your authentication system

$data = [];

// Fetch weekly, monthly, and total tickets
$query = "SELECT 
            (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)) AS total_tickets_week,
            (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())) AS total_tickets_month,
            (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ?) AS total_tickets";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $handle_by, $handle_by, $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Fetch Pie Chart Data
$query = "SELECT status, COUNT(*) AS total FROM activity_logs WHERE handle_by = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['pieChart'] = [];
while ($row = $result->fetch_assoc()) {
    $data['pieChart'][$row['status']] = $row['total'];
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
$stmt->bind_param("i", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['barChart'] = ['months' => [], 'completed' => [], 'cancelled' => []];

while ($row = $result->fetch_assoc()) {
    $data['barChart']['months'][] = "Month " . $row['month'];
    $data['barChart']['completed'][] = $row['completed'];
    $data['barChart']['cancelled'][] = $row['cancelled'];
}

header('Content-Type: application/json');
echo json_encode($data);
?>
