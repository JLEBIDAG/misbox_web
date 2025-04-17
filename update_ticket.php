<?php
session_start();
date_default_timezone_set("Asia/Manila");
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'inc/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticketID = $_POST['PK_activityLogID'] ?? null;
    $ticket_no = $_POST['ticket_no'] ?? '';
    $requestor_department = $_POST['AddRequestorDepartment'] ?? '';
    $requestor_name = $_POST['requestorName'] ?? '';
    $position = $_POST['position'] ?? '';
    $priority = $_POST['ViewPriority'] ?? '';
    $status = $_POST['ViewStatus'] ?? '';
    $handle_by = $_POST['handle_by'] ?? '';

    if (!empty($_POST['UpdateAccomBy'])) {
        $handle_by = $_POST['UpdateAccomBy'];
    }

    $message = $_POST['message']?? '';
    $isViewed = 1;
    $severity = $_POST['AddSeverity'] ?? '';
    $task_category = $_POST['taskCategory'] ?? '';
    $task_description = $_POST['taskDescription'] ?? '';
    $concern = $_POST['concern'] ?? '';
    $resolution = $_POST['resolution'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $dateTimeAccomp = ($status === "Completed") ? date("Y-m-d H:i:s") : null;
    $dueDate = $_POST['dueDate'] ?? '';

    if ($status === "Completed") {
        $dueDate = null;
        $is_request = null;
    } elseif (empty($dueDate) && $status === "On Hold") {
        $dueDate = date("Y-m-d H:i:s", strtotime("+7 days"));
    }

    if ($status === "Cancelled") {
        $dueDate = null;
    }

    if (!$ticketID) {
        echo json_encode(["success" => false, "message" => "Invalid ticket ID"]);
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
    $fields = [
        "requestor_department" => $requestor_department,
        "requestor_name" => $requestor_name,
        "position" => $position,
        "priority" => $priority,
        "status" => $status,
        "dateTimeAccomp" => $dateTimeAccomp,
        "handle_by" => $handle_by,
        "severity" => $severity,
        "task_category" => $task_category,
        "task_description" => $task_description,
        "concern" => $concern,
        "resolution" => $resolution,
        "remarks" => $remarks,
        "dueDate" => $dueDate
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
    $sqlUpdate = "UPDATE activity_logs 
        SET requestor_department = ?, requestor_name = ?, position = ?, priority = ?, status = ?, dateTimeAccomp = ?, 
            handle_by = ?, severity = ?, task_category = ?, task_description = ?, concern = ?, resolution = ?, 
            remarks = ?, dueDate = ?, is_request = ?, is_viewed = ?
        WHERE PK_activityLogID = ?";

    if ($stmtUpdate = $conn->prepare($sqlUpdate)) {
        $stmtUpdate->bind_param(
            "ssssssssssssssiii",
            $requestor_department,
            $requestor_name,
            $position,
            $priority,
            $status,
            $dateTimeAccomp,
            $handle_by,
            $severity,
            $task_category,
            $task_description,
            $concern,
            $resolution,
            $remarks,
            $dueDate,
            $is_request,
            $isViewed,
            $ticketID
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

            // ✅ TICKET STATUS HANDLING
            // Determine values based on status
            $inProgress = ($status === "In Progress") ? 1 : 0;
            $onHold = ($status === "On Hold") ? 1 : 0;

            // Check if ticket_status row exists
            $checkSQL = "SELECT COUNT(*) FROM ticket_status WHERE FK_activityLogID = ?";
            $checkStmt = $conn->prepare($checkSQL);
            $checkStmt->bind_param("i", $ticketID);
            $checkStmt->execute();
            $checkStmt->bind_result($existingCount);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($existingCount == 0) {
                // Insert new record
                $insertSQL = "INSERT INTO ticket_status (FK_activityLogID, is_Inprogress, is_Onhold, dateTime_taken, messages)
                              VALUES (?, ?, ?, NOW(), ?)";
                $insertStmt = $conn->prepare($insertSQL);
                $insertStmt->bind_param("iiis", $ticketID, $inProgress, $onHold, $message);
                $insertStmt->execute();
                $insertStmt->close();
            } else {
                // Update existing record
                $updateStatusSQL = "UPDATE ticket_status 
                                    SET is_Inprogress = ?, is_Onhold = ? 
                                    WHERE FK_activityLogID = ?";
                $updateStmt = $conn->prepare($updateStatusSQL);
                $updateStmt->bind_param("iii", $inProgress, $onHold, $ticketID);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // ✅ Set dateTime_Accomp when status is completed
            if ($status === "Completed") {
                $updateComplete = "UPDATE ticket_status SET dateTime_Accomp = NOW() WHERE FK_activityLogID = ?";
                $stmtComp = $conn->prepare($updateComplete);
                $stmtComp->bind_param("i", $ticketID);
                $stmtComp->execute();
                $stmtComp->close();
            }

            if ($status === "On Hold" && !empty($message)) {
                $messageUpdate = "UPDATE ticket_status SET messages = ? WHERE FK_activityLogID = ?";
                $stmtMess = $conn->prepare($messageUpdate);
                $stmtMess->bind_param("si", $message, $ticketID);
                $stmtMess->execute();
                $stmtMess->close();
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
