<!-- <
session_start();
include 'inc/conn.php'; // Ensure this file connects to your database

if (isset($_SESSION['Username'])) {
    $conditions = ["is_approved = 0"]; // Empty by default
    $params = []; // Empty array for parameters
    $types = ""; // Empty type string

    if (!empty($_POST['requestDate1']) && !empty($_POST['requestDate2'])) {
        $conditions[] = "DATE(r.created_at) BETWEEN ? AND ?";
        $params[] = $_POST['requestDate1'];
        $params[] = $_POST['requestDate2'];
        $types .= "ss"; // Two string types
    }


    if (!empty($_POST['departmentSelect'])) {
        $conditions[] = "a.requestor_department = ?";
        $params[] = $_POST['departmentSelect'];
        $types .= "i"; // String type
    }

    if (!empty($_POST['statusSelect'])) {
        $conditions[] = "status = ?";
        $params[] = $_POST['statusSelect'];
        $types .= "s"; // String type
    }

    // Construct the final SQL query
    $sql = "SELECT r.*, a.*, u.* FROM tbl_requests r 
    INNER JOIN activity_logs a ON r.FK_activityLogID = a.PK_activityLogID
    INNER JOIN tbl_users u ON r.FK_userID = u.PK_userID";

    // Add conditions if there are any
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // Ordering
    $sql .= " ORDER BY 
                CASE 
                    WHEN priority = 'Urgent' AND status != 'Completed' THEN 0
                    WHEN priority = 'High' AND status != 'Completed' THEN 2
                    WHEN priority = 'Medium' AND status != 'Completed' THEN 3
                    WHEN priority = 'Low' AND status != 'Completed' THEN 4
                    ELSE 5
                END";

    // Prepare and execute statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters if needed
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $bgColors = [
                'Completed' => '#00D26A',
                'In Progress' => '#00A6ED',
                'Cancelled' => '#F92F60',
                'On Hold' => '#8A95A0',
                'Open' => '#FCD53F'
            ];
            $bgColor = $bgColors[$row["status"]] ?? 'transparent';

            echo "<tr data-id='{$row["PK_activityLogID"]}' data-ticketNo='{$row["ticket_no"]}' style='cursor: pointer'>
                    <td style='background-color: {$bgColor}; width: 0px;' title='{$row["status"]}'></td>
                    <td>{$row["ticket_no"]}</td>
                    <td>{$row["priority"]}</td>
                    <td>{$row["task_description"]}</td>
                     <td>{$row["message"]}</td>
                    <td style='width:50%'>";
            $words = explode(' ', $row["concern"]);
            $limited_text = implode(' ', array_slice($words, 0, 20));
            echo htmlspecialchars($limited_text);
            if (count($words) > 20)
                echo '...';
            echo "</td>
                <td>
                    <button type='button' class='btn btn-primary btn-xs view-btn' 
                        data-id='{$row["PK_activityLogID"]}' 
                        data-ticketNo='{$row["ticket_no"]}' 
                        style='margin:5px;'>
                        <span class='icon-eye'></span>
                    </button>
                    
                    <button type='button' class='btn btn-success btn-xs req-btn' 
                        id='reqBtn' 
                        data-id='{$row["PK_activityLogID"]}' 
                        data-ticketNo='{$row["ticket_no"]}' 
                        style='margin:5px;'>
                        <span class='far fa-edit'></span>
                    </button>
                </td>
            </tr>";

        }
    } else {
        echo "<tr><td colspan='6' class='text-center'>No records found</td></tr>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<tr><td colspan='6' class='text-center'>Invalid request</td></tr>";
}
?>
 -->
 <?php

sleep(1);
session_start();
include 'inc/conn.php';

if(isset($_POST['request'])){
    $request = $_POST['request'];

    $sql = "SELECT r.*, a.*, u.*
    FROM tbl_requests r
    INNER JOIN activity_logs a ON r.FK_activityLogID = a.PK_activityLogID
    INNER JOIN tbl_users u ON r.FK_userID = u.PK_userID
    WHERE is_approved = 0 AND a.requestor_department = '$request'"; // Fixed incorrect variable

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $count = mysqli_num_rows($result);
    } else {
        $count = 0;
    }
}
?>

<table>
    <?php if($count): ?>
    <thead>
        <tr>
            <th>Ticket No</th>
            <th>Priority</th>
            <th>Task Title</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>" id="viewBtn" style="cursor: pointer">
            <td><?= $row["ticket_no"] ?></td>
            <td><?= $row["priority"] ?></td>
            <td><?= $row["task_description"] ?></td>
            <td><?= $row["message"] ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
    <?php else: ?>
    <tr>
        <?php
        if (!isset($count) || $count == 0) {
            echo "<table class='table'><tr><td colspan='4' class='text-center'>No records found</td></tr></table>";
        }
        ?>
    </tr>
    <?php endif; ?>
</table>
