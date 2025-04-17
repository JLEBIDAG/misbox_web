<?php 
session_start();
include 'inc/conn.php'; // Ensure this file connects to your database

$conditions = []; // Initialize conditions array
$params = [];
$types = "";

if (!empty($_POST['dateRangeFilterStart']) && !empty($_POST['dateRangeFilterEnd'])) {
    $conditions[] = "DATE(transaction_date) BETWEEN ? AND ?";
    $params[] = $_POST['dateRangeFilterStart'];
    $params[] = $_POST['dateRangeFilterEnd'];
    $types .= "ss"; // Two string types for date values
}

if (!empty($_POST['usernameSelectval'])) { // Fix the key to match AJAX request
    $conditions[] = "FK_userID = ?";
    $params[] = $_POST['usernameSelectval'];
    $types .= "i"; // Integer type for FK_userID
}

// Construct the SQL query
$sql = "SELECT h.*, u.Username FROM tbl_history h 
        INNER JOIN tbl_users u ON h.FK_userID = u.PK_userID";

// Add conditions if any exist
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

// Bind parameters dynamically if conditions exist
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>".htmlspecialchars($row['ticket_no'])."</td>
                <td>
                    <strong>Old:</strong> ".htmlspecialchars($row['old_value'])." <br>
                    <strong>New:</strong> ".htmlspecialchars($row['new_value'])."
                </td>
                <td class='text-end'>".date("M d, Y, h:i A", strtotime($row['transaction_date']))."</td>
                <td class='text-end'>".htmlspecialchars($row['Username'])."</td>
                <td class='text-end'>
                    <span class='badge " . getBadgeClass($row['action']) . "'>" . htmlspecialchars($row['action']) . "</span>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center'>No records found</td></tr>";
}

$stmt->close();
$conn->close();

// Function to assign badge class
function getBadgeClass($action) {
    switch ($action) {
        case 'ADD': return 'badge-info';
        case 'UPDATE': return 'badge-warning';
        case 'DELETE': return 'badge-danger';
        default: return 'badge-secondary';
    }
}
?>
