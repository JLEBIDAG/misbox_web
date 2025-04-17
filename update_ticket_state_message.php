<?php
session_start();
date_default_timezone_set("Asia/Manila");
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'inc/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticketID = $_POST['ViewTicketNo'] ?? null;
    $FKactivityLogID = $_POST['FKactivityLogID'] ?? '';

    $oldmessage = $_POST['ViewOldMessages'] ?? '';
    $newmessage = $_POST['ViewMessages'] ?? '';

    if (!$ticketID) {
        echo json_encode(["success" => false, "message" => "Invalid ticket ID"]);
        exit;
    }

    // Fetch current data before updating
    $sqlFetch = "SELECT * FROM ticket_status WHERE FK_activityLogID = ?";
    if ($stmtFetch = $conn->prepare($sqlFetch)) {
        $stmtFetch->bind_param("i", $FKactivityLogID);
        $stmtFetch->execute();
        $result = $stmtFetch->get_result();
        $oldData = $result->fetch_assoc();
        $stmtFetch->close();
    } else {
        echo json_encode(["success" => false, "message" => "SQL error: " . $conn->error]);
        exit;
    }

    // Track changes
    $fields = [
        'messages' => $oldmessage,
        'new_messages' => $newmessage
    ];

    $changedFields = [];
    foreach ($fields as $column => $newValue) {
        $oldValue = $oldData[$column] ?? null;
        if ($newValue !== null && $newValue != $oldValue) {
            $changedFields[] = [
                'field' => $column,
                'old_value' => $oldValue,
                'new_value' => $newValue
            ];
        }
    }

    // Update activity_logs
    $sqlUpdate = "UPDATE ticket_status 
        SET messages = ? , new_messages = ?
        WHERE FK_activityLogID = ?";

    if ($stmtUpdate = $conn->prepare($sqlUpdate)) {
        $stmtUpdate->bind_param(
            "ssi",
            $oldmessage,
            $newmessage,
            $FKactivityLogID
        );

        if ($stmtUpdate->execute()) {
            // Insert history logs
            if (!empty($changedFields)) {
                $userID = $_SESSION['PK_userID'] ?? null;
                $transactionDate = date("Y-m-d H:i:s");

                $sqlHistory = "INSERT INTO tbl_history (transaction_date, ticket_no, fields, old_value, new_value, FK_userID, action) 
                               VALUES (?, ?, ?, ?, ?, ?, 'UPDATE')";

                if ($stmtHistory = $conn->prepare($sqlHistory)) {
                    foreach ($changedFields as $change) {
                        $stmtHistory->bind_param(
                            "sssssi",
                            $transactionDate,
                            $ticketID, // ✅ Use correct variable
                            $change['field'],
                            $change['old_value'],
                            $change['new_value'],
                            $userID
                        );
                        $stmtHistory->execute();
                    }
                    $stmtHistory->close();
                }
            }

            echo json_encode(["success" => true, "message" => "Ticket updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $stmtUpdate->error]);
        }

        $stmtUpdate->close();
    } else {
        echo json_encode(["success" => false, "message" => "SQL error: " . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>
