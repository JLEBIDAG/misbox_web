<?php
session_start();
include "inc/conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userReq = $_SESSION['PK_userID'] ?? null;
    $id = $_POST['id'] ?? null;
    $ticketno = $_POST['ticketNo'] ?? ''; // Match JavaScript
    $message = $_POST['message'] ?? ''; // Match JavaScript

    if ($userReq !== null && $id !== null && !empty($message)) {

        // Prepare SQL statement
        $sqlInsert = "INSERT INTO tbl_requests (FK_activityLogID, ticket_no, FK_userID, is_approved, message) VALUES (?, ?, ?, 0, ?)";
        $stmt = $conn->prepare($sqlInsert);

        if ($stmt) {
            $stmt->bind_param("isis", $id, $ticketno, $userReq, $message); // Corrected order

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Request submitted successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error executing query: " . $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Error preparing query: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request data."]);
    }
}
?>
