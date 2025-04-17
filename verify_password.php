<?php
session_start();
include "inc/conn.php"; // Make sure this file correctly initializes $conn

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['Username']) || !isset($_SESSION['PK_userID'])) {
        echo json_encode(["status" => "error", "message" => "User not logged in."]);
        exit();
    }

    $Username = $_SESSION['Username'];
    $userID = $_SESSION['PK_userID']; // Get the user's ID
    $Oldpassword = trim($_POST['oldPassword']);
    $Newpassword = trim($_POST['newPassword']);

    if (empty($Oldpassword) || empty($Newpassword)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    // Fetch the current password
    $query = "SELECT Password FROM tbl_users WHERE BINARY Username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($Oldpassword, $user['Password'])) {
            // Hash the new password
            $hashedPassword = password_hash($Newpassword, PASSWORD_BCRYPT);

            // Start transaction to ensure consistency
            $conn->begin_transaction();

            // Update the password
            $updateQuery = "UPDATE tbl_users SET Password = ? WHERE Username = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $hashedPassword, $Username);

            if ($updateStmt->execute()) {
                // Insert into history table
                $historyQuery = "INSERT INTO tbl_history (transaction_date, ticket_no, fields, old_value, new_value, FK_userID, action)
                                 VALUES (NOW(), NULL, 'Password Change', '******', '******', ?, 'UPDATE')";
                $historyStmt = $conn->prepare($historyQuery);
                $historyStmt->bind_param("i", $userID);

                if ($historyStmt->execute()) {
                    $conn->commit(); // Commit transaction if both queries succeed
                    echo json_encode(["status" => "success", "message" => "Password updated successfully."]);
                } else {
                    $conn->rollback(); // Rollback if history insertion fails
                    echo json_encode(["status" => "error", "message" => "Failed to log history."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update password."]);
            }

            $updateStmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Old password is incorrect."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found."]);
    }

    $stmt->close();
    $conn->close();
}
?>
