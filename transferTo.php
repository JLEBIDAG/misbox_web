<?php
session_start();
date_default_timezone_set("Asia/Manila");
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set("Asia/Manila");
include 'inc/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize POST data
    $ticketID = $_POST['PK_activityLogID'] ?? null;
    $ticket_no = $_POST['ticket_no'] ?? '';
    $handle_by = $_POST['transfertoUsername'] ?? '';
    $isViewed = 0;

    if (empty($ticketID) || empty($handle_by)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    // Fetch current data before updating
    $sqlFetch = "SELECT * FROM activity_logs WHERE PK_activityLogID = ?";
    if ($stmtFetch = $conn->prepare($sqlFetch)) {
        $stmtFetch->bind_param("i", $ticketID);
        $stmtFetch->execute();
        $result = $stmtFetch->get_result();
        $oldData = $result->fetch_assoc();
        $stmtFetch->close();
    } else {
        echo json_encode(["success" => false, "message" => "SQL error: " . $conn->error]);
        exit;
    }

    // Track changes
    $changedFields = [];
    if ($oldData['handle_by'] !== $handle_by) {
        $changedFields[] = [
            'field' => 'handle_by',
            'old_value' => $oldData['handle_by'],
            'new_value' => $handle_by
        ];
    }

    $dateTransfer = date("Y-m-d H:i:s");
    $updateAt = date("Y-m-d H:i:s");

    // Update record
    $sqlUpdate = "UPDATE activity_logs SET handle_by = ?, transferDate = ?, is_viewed = ?, updated_at = ? WHERE PK_activityLogID = ?";
    if ($stmtUpdate = $conn->prepare($sqlUpdate)) {
        $stmtUpdate->bind_param("ssisi", $handle_by, $dateTransfer, $isViewed, $updateAt,  $ticketID);

        if ($stmtUpdate->execute()) {
            // Insert history records if changes exist
            if (!empty($changedFields)) {
                $userID = $_SESSION['PK_userID'] ?? null;
                $transactionDate = date("Y-m-d H:i:s");

                $sqlHistory = "INSERT INTO tbl_history (transaction_date, ticket_no, fields, old_value, new_value, FK_userID, action) 
                               VALUES (?, ?, ?, ?, ?, ?, 'TRANSFER')";

                if ($stmtHistory = $conn->prepare($sqlHistory)) {
                    foreach ($changedFields as $change) {
                        $stmtHistory->bind_param(
                            "sssssi",
                            $transactionDate,
                            $ticket_no,
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

            echo json_encode(["success" => true, "message" => "Ticket Transferred successfully!"]);
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
