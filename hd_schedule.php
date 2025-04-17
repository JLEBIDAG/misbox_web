<?php
include 'inc/conn.php';
session_start();

$sqlSched = "SELECT time_in, time_out FROM tbl_users WHERE class = 'O2'";
$stmtSched = $conn->prepare($sqlSched);
$stmtSched->execute();
$result = $stmtSched->get_result()->fetch_assoc(); // Fetch data as an associative array

if ($result) {
    echo json_encode($result); // Directly return the fetched array
} else {
    echo json_encode([
        'time_in' => null,
        'time_out' => null
    ]);
}

?>
