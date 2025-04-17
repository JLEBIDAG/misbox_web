<?php
session_start();
include "inc/conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userReq = $_SESSION['PK_userID'] ?? null;
    $id = $_POST['id'] ?? null;
    $ticketno = $_POST['ticketNo'] ?? null; 

    if ($userReq !== null && $id !== null && $ticketno !== null) {
        // Convert ticket number to an integer
        $ticketno = (int) $ticketno;

        // Approve request
        $sqlUpdate = "UPDATE tbl_requests SET is_approved = 1 WHERE ticket_no = ?";
        $stmt = $conn->prepare($sqlUpdate);

        if ($stmt) {
            $stmt->bind_param("i", $ticketno);

            if ($stmt->execute()) {
                // Update activity_logs (assuming PK_requestID can be found using ticket_no)
                $updateActLogs = "UPDATE activity_logs al
                    JOIN tbl_requests tr ON al.ticket_no = tr.ticket_no
                    SET al.is_request = tr.PK_requestID
                    WHERE al.ticket_no = ?";

                $stmt2 = $conn->prepare($updateActLogs);
                if ($stmt2) {
                    $stmt2->bind_param("i", $ticketno);
                    $stmt2->execute();
                    $stmt2->close();
                }

                echo json_encode(["status" => "success", "message" => "Approved Request successfully!"]);
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
