<?php
session_start();
include 'inc/conn.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fromUser = $_SESSION['Username'];
    $ticketNo = $_POST['ticketNo'] ?? null;  // Fix: Retrieve ticketNo from POST
    $fkUser = $_POST['fkUser'] ?? null;
    $message = $_POST['message'] ?? null;

    //select fiurst in the user are existing
    $selectUser = "SELECT Username FROM tbl_users WHERE PK_userID = ?";
    $stmt = $conn->prepare($selectUser);
    $stmt->bind_param("i", $fkUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $dataUser = $result->fetch_assoc();

    $toUser = $dataUser['Username'];

    if ($ticketNo && $fkUser && $message) { // Ensure all values are provided
        $dateNow = date('Y-m-d H:i:s');

        $sqlInsert = "INSERT INTO tbl_messsages (`messages`, `FK_userID`, `ticket_no`, `created_at`, `from_user`,`to_user`, `from_userID`) 
                      VALUES (?, ?, ?, ?, ?, ?, 1)";

        if ($stmt = $conn->prepare($sqlInsert)) {
            $stmt->bind_param("sissss", $message, $fkUser, $ticketNo, $dateNow, $fromUser, $toUser);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Message sent successfully!"]);
            } else {
                echo json_encode(["status" => "error", "message" => $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "All fields are required!"]);
    }
}

$conn->close();
?>
