<?php
session_start();
include 'inc/conn.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(["success" => false, "message" => "Invalid ID"]);
        exit;
    }

    $id = intval($_POST['id']); // Prevent SQL Injection

    $query = "UPDATE activity_logs SET is_viewed = 1 WHERE PK_activityLogID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Ticket updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update ticket."]);
    }

    $stmt->close();
    $conn->close();
}
?>
