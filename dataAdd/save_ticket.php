<?php
session_start();
date_default_timezone_set("Asia/Manila");
include '../inc/conn.php';

if (!isset($_SESSION['PK_userID'])) {
    die('User not logged in.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userID = $_SESSION['PK_userID'];
    $class = $_SESSION['class'];
    $username = $_SESSION['Username'];

    $requestor_department = $_POST['AddRequestorDepartment'] ?? NULL;
    $position = $_POST['position'] ?? NULL;
    $requestor_name = $_POST['requestorName'] ?? NULL;
    $priority = $_POST['ViewPriority'] ?? NULL;
    $status = $_POST['ViewStatus'] ?? NULL;
    $AddIssue = $_POST['AddIssue'] ?? NULL;

    $isViewed = ($_SESSION['class'] == 'HD') ? 0 : 1;

    $handle_by = $_POST['UpdateAccomBy'] ?? $username;
    if ($handle_by === 'NULL') {
        $handle_by = NULL;
    }

    $severity = $_POST['AddSeverity'] ?? NULL;
    $task_category = $_POST['taskCategory'] ?? NULL;
    $task_description = $_POST['taskDescription'] ?? NULL;
    $concern = $_POST['concern'] ?? NULL;
    $resolution = $_POST['resolution'] ?? NULL;
    $remarks = $_POST['remarks'] ?? NULL;

    if ($status === "Completed") {
        $dueDate = null;
    } elseif ($status === "On Hold") {
        $dueDate = date("Y-m-d H:i:s", strtotime("+7 days"));
    } elseif ($status === "Cancelled") {
        $dueDate = null;
    } else {
        $dueDate = date("Y-m-d H:i:s", strtotime("+2 days"));
    }

    $dateTimeAccomp = ($status === "Completed") ? date("Y-m-d H:i:s") : null;

    function generateUniqueTicket($conn)
    {
        $currentYear = date("Y");
        do {
            $randomNumber = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $newTicketNo = "$currentYear-$randomNumber";

            $stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs WHERE ticket_no = ?");
            $stmt->bind_param("s", $newTicketNo);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

        } while ($count > 0);

        return $newTicketNo;
    }

    $ticket_no = generateUniqueTicket($conn);

    if ($stmt = $conn->prepare("INSERT INTO activity_logs (ticket_no, FK_userID, created_at, requestor_department, position, requestor_name, priority, status, dateTimeAccomp, handle_by, severity, issue_reported, task_category, task_description, concern, resolution, remarks, is_viewed, dueDate, created_by) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
        $stmt->bind_param("sissssssssssssssiss", $ticket_no, $userID, $requestor_department, $position, $requestor_name, $priority, $status, $dateTimeAccomp, $handle_by, $severity, $AddIssue, $task_category, $task_description, $concern, $resolution, $remarks, $isViewed, $dueDate, $username);

        if ($stmt->execute()) {
            echo "Ticket saved successfully. Ticket Number: $ticket_no<br>";

            $activityLogID = $stmt->insert_id;

            // Insert into tbl_history
            $transaction_date = date("Y-m-d H:i:s");
            $action = "ADD";

            $fields = [
                "requestor_department" => $requestor_department,
                "position" => $position,
                "requestor_name" => $requestor_name,
                "priority" => $priority,
                "status" => $status,
                "dateTimeAccomp" => $dateTimeAccomp,
                "handle_by" => $handle_by,
                "severity" => $severity,
                "issue_reported" => $AddIssue,
                "task_category" => $task_category,
                "task_description" => $task_description,
                "concern" => $concern,
                "resolution" => $resolution,
                "remarks" => $remarks,
                "dueDate" => $dueDate
            ];

            foreach ($fields as $column => $new_value) {
                if (!is_null($new_value)) {
                    echo "Inserting into tbl_history: Field = $column, New Value = $new_value <br>";

                    $stmt_history = $conn->prepare("INSERT INTO tbl_history (transaction_date, ticket_no, fields, old_value, new_value, FK_userID, action) VALUES (?, ?, ?, NULL, ?, ?, ?)");
                    if ($stmt_history === false) {
                        die("Error preparing statement for tbl_history: " . $conn->error);
                    }

                    $stmt_history->bind_param("ssssss", $transaction_date, $ticket_no, $column, $new_value, $userID, $action);
                    if (!$stmt_history->execute()) {
                        echo "Error inserting into tbl_history: " . $stmt_history->error . "<br>";
                    }
                    $stmt_history->close();
                }
            }

            // Normalize status for comparison
            $statusNormalized = strtolower(trim($status));
            echo "Normalized Status: '$statusNormalized'<br>"; // Debug

            if ($statusNormalized === "in progress" || $statusNormalized === "on hold") {
                $is_in_progress = ($statusNormalized === "in progress") ? 1 : 0;
                $is_on_hold = ($statusNormalized === "on hold") ? 1 : 0;
                $dateTime_taken = date("Y-m-d H:i:s");

                $stmt_ticket_status = $conn->prepare("INSERT INTO ticket_status (FK_activityLogID, is_Inprogress, is_Onhold, dateTime_taken, dateTime_Accomp, messages) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt_ticket_status === false) {
                    die("Error preparing ticket_status statement: " . $conn->error);
                }

                $messages = NULL;
                $stmt_ticket_status->bind_param("iiisss", $activityLogID, $is_in_progress, $is_on_hold, $dateTime_taken, $dateTimeAccomp, $messages);

                if ($stmt_ticket_status->execute()) {
                    echo "Status inserted into ticket_status.<br>";
                } else {
                    echo "Error inserting into ticket_status: " . $stmt_ticket_status->error . "<br>";
                }
                $stmt_ticket_status->close();
            }

        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();
?>
