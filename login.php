<?php
session_start();
include('inc/conn.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Securely get user input
    $Username = trim($_POST['username']);
    $Password = $_POST['password'];

    // Check if the user exists in the database using prepared statements
    $query = "SELECT * FROM tbl_users WHERE BINARY Username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch user data
        $user = $result->fetch_assoc();

        // Verify the entered Password with the hashed Password
        if (password_verify($Password, $user['Password'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            // Update login status
            $updateQuery = "UPDATE tbl_users SET is_login = 1 WHERE Username = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("s", $Username);
            $updateStmt->execute();

            // Store necessary user data in session (DO NOT store password!)
            $_SESSION['PK_userID'] = $user['PK_userID'];
            $_SESSION['Username'] = $user['Username'];
            $_SESSION['FirstName'] = $user['FirstName'];
            $_SESSION['LastName'] = $user['LastName'];
            $_SESSION['Email'] = $user['Email'];
            $_SESSION['class'] = $user['class'];
            $_SESSION['time_in'] = $user['time_in'];
            $_SESSION['time_out'] = $user['time_out'];
            $_SESSION['FK_Departmentname'] = $user['FK_Departmentname'];
            $_SESSION['FK_userRole'] = $user['FK_userRole'];

            // Log the login event securely
            date_default_timezone_set("Asia/Manila");
            $date = date('Y-m-d H:i:s'); // Use a standard datetime format
            $PK_userID = $user['PK_userID'];
            $desc = $user['FirstName'] . " " . $user['LastName']; // Full name for clarity
            $action = "LOGIN";

            $insertQuery = $conn->prepare("INSERT INTO `logs` (`FK_userID`, `action`, `description`, `datetime`) VALUES (?, ?, ?, ?)");
            $insertQuery->bind_param("isss", $PK_userID, $action, $desc, $date);
            $insertQuery->execute();

            // Respond with a success message
            echo json_encode(['status' => 'true']);
        } else {
            // Prevent brute-force attacks by limiting login attempts
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= 5) {
                echo json_encode(['status' => 'false', 'message' => 'Too many failed login attempts. Try again later.']);
            } else {
                echo json_encode(['status' => 'false', 'message' => 'Invalid password. Please try again.']);
            }
        }
    } else {
        // User not found
        echo json_encode(['status' => 'false', 'message' => 'User not found. Please check your username.']);
    }
}
?>
