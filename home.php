<?php
include('inc/conn.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['PK_userID']) || (trim($_SESSION['PK_userID']) == '')) {
    header('location:index.php');
    exit();
}
// Fetch the current user's details
$uquery = mysqli_query($conn, "SELECT u.*, d.* FROM `tbl_users` u INNER JOIN `db_departmentgroups` d ON u.FK_Departmentname = d.PK_DepartmentID WHERE PK_userID='" . $_SESSION['PK_userID'] . "'");
$urow = mysqli_fetch_assoc($uquery);

?>

<?php
$page_name = basename($_SERVER['PHP_SELF']);
$allPage = 'all_page';

$sqlPages = "SELECT * FROM db_pages WHERE (page_name = ? OR page_name = ?) AND is_underMaintenance = 1";
$stmtPage = $conn->prepare($sqlPages);
$stmtPage->execute([$page_name, $allPage]);
$page = $stmtPage->fetch();

if ($page) {
    // Redirect to maintenance page and store the original page URL
    header("Location: under_maintenance.php?redirect=" . urlencode($page_name));
    exit;
}

?>



<?php
// Fetch notifications for the logged-in user
$sql = "SELECT a.*, u.Username, u.PK_userID
FROM activity_logs a
INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
WHERE a.handle_by = ? AND a.is_viewed = 0  AND a.status != 'Completed'
ORDER BY a.created_at DESC"; // Assuming there is a timestamp column

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();
$result1 = $stmt->get_result();

$notifCount = $result1->num_rows; // Count total notifications

// Fetch request ticket to edit
$requestSql = "SELECT r.*, a.*, u.*
FROM tbl_requests r
INNER JOIN activity_logs a ON r.FK_activityLogID = a.PK_activityLogID
INNER JOIN tbl_users u ON r.FK_userID = u.PK_userID
WHERE is_approved = 0"; // Assuming there is a timestamp column

$reqstmt = $conn->prepare($requestSql);
$reqstmt->execute();
$reqRes = $reqstmt->get_result();

$requestTotal = $reqRes->num_rows; // Count total request


// Fetch notifications for the logged-in user
$sqlMessage = "SELECT m.*, u.*
FROM tbl_messsages m
INNER JOIN tbl_users u ON m.from_userID = u.PK_userID
WHERE to_user = ? AND is_read = 0"; // Assuming there is a timestamp column

$stmtMess = $conn->prepare($sqlMessage);
$stmtMess->bind_param("s", $_SESSION['Username']);
$stmtMess->execute();
$resultMessg = $stmtMess->get_result();

$totalMess = $resultMessg->num_rows; // Count total messages

?>

<?php


$handle_by = $_SESSION['Username']; // Get the logged-in username

$data = [];

// Fetch weekly, monthly, and total tickets
$query = "SELECT 
    (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)) AS total_tickets_week,
    (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND DATE(created_at) = CURDATE()) AS total_tickets_today,
    (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())) AS total_tickets_month,
    (SELECT COUNT(*) FROM activity_logs) AS total_tickets";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $handle_by, $handle_by, $handle_by); // Fix: Use "sss" and pass $handle_by three times
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$total_tickets = $data['total_tickets'] ?? 0; // Handle null case

// Avoid division by zero
$day_progress = ($total_tickets > 0) ? ($data['total_tickets_today'] / $total_tickets) * 100 : 0;
$week_progress = ($total_tickets > 0) ? ($data['total_tickets_week'] / $total_tickets) * 100 : 0;
$month_progress = ($total_tickets > 0) ? ($data['total_tickets_month'] / $total_tickets) * 100 : 0;
$total_progress = 100; // Always 100% since total_tickets is the reference



// Fetch Pie Chart Data
$query = "SELECT status, COUNT(*) AS total FROM activity_logs WHERE handle_by = ? GROUP BY status";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['pieChart'] = [];

while ($row = $result->fetch_assoc()) {
    $data['pieChart'][] = [
        'status' => $row['status'],
        'total' => $row['total']
    ];

}

// Fetch Bar Chart Data
$query = "SELECT 
            MONTH(created_at) AS month, 
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
          FROM activity_logs
          WHERE handle_by = ?
          GROUP BY MONTH(created_at)
          ORDER BY month";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data['barChart'] = ['months' => [], 'completed' => [], 'cancelled' => []];

while ($row = $result->fetch_assoc()) {
    $data['barChart']['months'][] = date("M", mktime(0, 0, 0, $row['month'], 1)); // Convert month number to short name
    $data['barChart']['completed'][] = (int) $row['completed'];
    $data['barChart']['cancelled'][] = (int) $row['cancelled'];
}

// Encode data for JavaScript use
echo "<script>const chartData = " . json_encode($data) . ";</script>";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: { "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['assets/css/fonts.min.css'] },
            active: function () {
                sessionStorage.fonts = true;
            }
        });
    </script>


    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/plugins.min.css">
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css">

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Buttons extension CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <!-- Select2 CSS (for multi-select dropdowns) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">

    <style>
        .ModalHeaderDesign {
            width: 100%;
            height: 100%;
            /* Add your background pattern here */
            background: rgba(29, 31, 32, 0.904) radial-gradient(rgba(255, 255, 255, 0.712) 10%, transparent 1%);
            background-size: 11px 11px;
        }

        .hover-table {
            overflow: hidden;
            /* Hides the scrollbar */
        }

        .hover-table tbody tr {

            transition: background-color 0.3s, transform 0.3s;
        }

        .hover-table tbody tr:hover {
            font-weight: bold;
            background-color: #f1f1f1;
            transform: scale(1.02);
        }

        .dataTables_length select {
            /* Adjust the size as needed */
            height: 34px;
        }

        textarea {
            width: 100%;
            resize: vertical;
        }

        thead input {
            width: 100%;
            padding: 3px;
            box-sizing: border-box;
        }

        .logo-header .logo {
            color: #ffffff;
            opacity: 1;
            position: relative;
            height: 100%;
            display: flex;
            align-items: center
        }


        .form-group {
            position: relative;

        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: #6a11cb;
            outline: none;
            box-shadow: 0 0 5px rgba(106, 17, 203, 0.5);
        }

        .floating-label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            font-size: 16px;
            color: #999;
            pointer-events: none;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus~.floating-label,
        .form-control:not(:placeholder-shown)~.floating-label {
            top: 0;
            left: 15px;
            font-size: 12px;
            color: #6a11cb;
        }

        textarea.form-control {
            resize: none;
        }

        select.form-control {
            appearance: none;
        }

        /* Wrap the select element */
        .select-wrapper {
            position: relative;
            width: 100%;
        }

        /* Style the select field */
        .select-wrapper select {
            appearance: none;
            /* Removes default arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            background-color: white;
            cursor: pointer;
        }

        /* Floating label */
        .select-wrapper .floating-label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            font-size: 16px;
            color: #999;
            pointer-events: none;
            transition: all 0.2s ease-in-out;
        }

        /* Move label when select has a value */
        .select-wrapper select:focus~.floating-label,
        .select-wrapper select:not([value=""])~.floating-label {
            top: 0;
            left: 15px;
            font-size: 12px;
            color: #6a11cb;
        }

        /* Custom dropdown arrow */
        .select-wrapper::after {
            content: "▼";
            /* Unicode for downward arrow */
            font-size: 14px;
            color: #999;
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        /* Change arrow color on focus */
        .select-wrapper select:focus~.floating-label+ ::after {
            color: #6a11cb;
        }
    </style>

    <style>
        .progress-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(#0d6efd var(--progress), #e9ecef 0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            position: relative;
            margin: auto;
            /* Centers in the column */
        }

        .progress-circle::before {
            content: "";
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }

        .progress-circle span {
            position: relative;
        }
    </style>
    <style type="text/css">
        .swal-icon--error {
            border-color: #f27474;
            -webkit-animation: animateErrorIcon .5s;
            animation: animateErrorIcon .5s
        }

        .swal-icon--error__x-mark {
            position: relative;
            display: block;
            -webkit-animation: animateXMark .5s;
            animation: animateXMark .5s
        }

        .swal-icon--error__line {
            position: absolute;
            height: 5px;
            width: 47px;
            background-color: #f27474;
            display: block;
            top: 37px;
            border-radius: 2px
        }

        .swal-icon--error__line--left {
            -webkit-transform: rotate(45deg);
            transform: rotate(45deg);
            left: 17px
        }

        .swal-icon--error__line--right {
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg);
            right: 16px
        }

        @-webkit-keyframes animateErrorIcon {
            0% {
                -webkit-transform: rotateX(100deg);
                transform: rotateX(100deg);
                opacity: 0
            }

            to {
                -webkit-transform: rotateX(0deg);
                transform: rotateX(0deg);
                opacity: 1
            }
        }

        @keyframes animateErrorIcon {
            0% {
                -webkit-transform: rotateX(100deg);
                transform: rotateX(100deg);
                opacity: 0
            }

            to {
                -webkit-transform: rotateX(0deg);
                transform: rotateX(0deg);
                opacity: 1
            }
        }

        @-webkit-keyframes animateXMark {
            0% {
                -webkit-transform: scale(.4);
                transform: scale(.4);
                margin-top: 26px;
                opacity: 0
            }

            50% {
                -webkit-transform: scale(.4);
                transform: scale(.4);
                margin-top: 26px;
                opacity: 0
            }

            80% {
                -webkit-transform: scale(1.15);
                transform: scale(1.15);
                margin-top: -6px
            }

            to {
                -webkit-transform: scale(1);
                transform: scale(1);
                margin-top: 0;
                opacity: 1
            }
        }

        @keyframes animateXMark {
            0% {
                -webkit-transform: scale(.4);
                transform: scale(.4);
                margin-top: 26px;
                opacity: 0
            }

            50% {
                -webkit-transform: scale(.4);
                transform: scale(.4);
                margin-top: 26px;
                opacity: 0
            }

            80% {
                -webkit-transform: scale(1.15);
                transform: scale(1.15);
                margin-top: -6px
            }

            to {
                -webkit-transform: scale(1);
                transform: scale(1);
                margin-top: 0;
                opacity: 1
            }
        }

        .swal-icon--warning {
            border-color: #f8bb86;
            -webkit-animation: pulseWarning .75s infinite alternate;
            animation: pulseWarning .75s infinite alternate
        }

        .swal-icon--warning__body {
            width: 5px;
            height: 47px;
            top: 10px;
            border-radius: 2px;
            margin-left: -2px
        }

        .swal-icon--warning__body,
        .swal-icon--warning__dot {
            position: absolute;
            left: 50%;
            background-color: #f8bb86
        }

        .swal-icon--warning__dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-left: -4px;
            bottom: -11px
        }

        @-webkit-keyframes pulseWarning {
            0% {
                border-color: #f8d486
            }

            to {
                border-color: #f8bb86
            }
        }

        @keyframes pulseWarning {
            0% {
                border-color: #f8d486
            }

            to {
                border-color: #f8bb86
            }
        }

        .swal-icon--success {
            border-color: #a5dc86
        }

        .swal-icon--success:after,
        .swal-icon--success:before {
            content: "";
            border-radius: 50%;
            position: absolute;
            width: 60px;
            height: 120px;
            background: #fff;
            -webkit-transform: rotate(45deg);
            transform: rotate(45deg)
        }

        .swal-icon--success:before {
            border-radius: 120px 0 0 120px;
            top: -7px;
            left: -33px;
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg);
            -webkit-transform-origin: 60px 60px;
            transform-origin: 60px 60px
        }

        .swal-icon--success:after {
            border-radius: 0 120px 120px 0;
            top: -11px;
            left: 30px;
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg);
            -webkit-transform-origin: 0 60px;
            transform-origin: 0 60px;
            -webkit-animation: rotatePlaceholder 4.25s ease-in;
            animation: rotatePlaceholder 4.25s ease-in
        }

        .swal-icon--success__ring {
            width: 80px;
            height: 80px;
            border: 4px solid hsla(98, 55%, 69%, .2);
            border-radius: 50%;
            box-sizing: content-box;
            position: absolute;
            left: -4px;
            top: -4px;
            z-index: 2
        }

        .swal-icon--success__hide-corners {
            width: 5px;
            height: 90px;
            background-color: #fff;
            padding: 1px;
            position: absolute;
            left: 28px;
            top: 8px;
            z-index: 1;
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg)
        }

        .swal-icon--success__line {
            height: 5px;
            background-color: #a5dc86;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 2
        }

        .swal-icon--success__line--tip {
            width: 25px;
            left: 14px;
            top: 46px;
            -webkit-transform: rotate(45deg);
            transform: rotate(45deg);
            -webkit-animation: animateSuccessTip .75s;
            animation: animateSuccessTip .75s
        }

        .swal-icon--success__line--long {
            width: 47px;
            right: 8px;
            top: 38px;
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg);
            -webkit-animation: animateSuccessLong .75s;
            animation: animateSuccessLong .75s
        }

        @-webkit-keyframes rotatePlaceholder {
            0% {
                -webkit-transform: rotate(-45deg);
                transform: rotate(-45deg)
            }

            5% {
                -webkit-transform: rotate(-45deg);
                transform: rotate(-45deg)
            }

            12% {
                -webkit-transform: rotate(-405deg);
                transform: rotate(-405deg)
            }

            to {
                -webkit-transform: rotate(-405deg);
                transform: rotate(-405deg)
            }
        }

        @keyframes rotatePlaceholder {
            0% {
                -webkit-transform: rotate(-45deg);
                transform: rotate(-45deg)
            }

            5% {
                -webkit-transform: rotate(-45deg);
                transform: rotate(-45deg)
            }

            12% {
                -webkit-transform: rotate(-405deg);
                transform: rotate(-405deg)
            }

            to {
                -webkit-transform: rotate(-405deg);
                transform: rotate(-405deg)
            }
        }

        @-webkit-keyframes animateSuccessTip {
            0% {
                width: 0;
                left: 1px;
                top: 19px
            }

            54% {
                width: 0;
                left: 1px;
                top: 19px
            }

            70% {
                width: 50px;
                left: -8px;
                top: 37px
            }

            84% {
                width: 17px;
                left: 21px;
                top: 48px
            }

            to {
                width: 25px;
                left: 14px;
                top: 45px
            }
        }

        @keyframes animateSuccessTip {
            0% {
                width: 0;
                left: 1px;
                top: 19px
            }

            54% {
                width: 0;
                left: 1px;
                top: 19px
            }

            70% {
                width: 50px;
                left: -8px;
                top: 37px
            }

            84% {
                width: 17px;
                left: 21px;
                top: 48px
            }

            to {
                width: 25px;
                left: 14px;
                top: 45px
            }
        }

        @-webkit-keyframes animateSuccessLong {
            0% {
                width: 0;
                right: 46px;
                top: 54px
            }

            65% {
                width: 0;
                right: 46px;
                top: 54px
            }

            84% {
                width: 55px;
                right: 0;
                top: 35px
            }

            to {
                width: 47px;
                right: 8px;
                top: 38px
            }
        }

        @keyframes animateSuccessLong {
            0% {
                width: 0;
                right: 46px;
                top: 54px
            }

            65% {
                width: 0;
                right: 46px;
                top: 54px
            }

            84% {
                width: 55px;
                right: 0;
                top: 35px
            }

            to {
                width: 47px;
                right: 8px;
                top: 38px
            }
        }

        .swal-icon--info {
            border-color: #c9dae1
        }

        .swal-icon--info:before {
            width: 5px;
            height: 29px;
            bottom: 17px;
            border-radius: 2px;
            margin-left: -2px
        }

        .swal-icon--info:after,
        .swal-icon--info:before {
            content: "";
            position: absolute;
            left: 50%;
            background-color: #c9dae1
        }

        .swal-icon--info:after {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-left: -3px;
            top: 19px
        }

        .swal-icon {
            width: 80px;
            height: 80px;
            border-width: 4px;
            border-style: solid;
            border-radius: 50%;
            padding: 0;
            position: relative;
            box-sizing: content-box;
            margin: 20px auto
        }

        .swal-icon:first-child {
            margin-top: 32px
        }

        .swal-icon--custom {
            width: auto;
            height: auto;
            max-width: 100%;
            border: none;
            border-radius: 0
        }

        .swal-icon img {
            max-width: 100%;
            max-height: 100%
        }

        .swal-title {
            color: rgba(0, 0, 0, .65);
            font-weight: 600;
            text-transform: none;
            position: relative;
            display: block;
            padding: 13px 16px;
            font-size: 27px;
            line-height: normal;
            text-align: center;
            margin-bottom: 0
        }

        .swal-title:first-child {
            margin-top: 26px
        }

        .swal-title:not(:first-child) {
            padding-bottom: 0
        }

        .swal-title:not(:last-child) {
            margin-bottom: 13px
        }

        .swal-text {
            font-size: 16px;
            position: relative;
            float: none;
            line-height: normal;
            vertical-align: top;
            text-align: left;
            display: inline-block;
            margin: 0;
            padding: 0 10px;
            font-weight: 400;
            color: rgba(0, 0, 0, .64);
            max-width: calc(100% - 20px);
            overflow-wrap: break-word;
            box-sizing: border-box
        }

        .swal-text:first-child {
            margin-top: 45px
        }

        .swal-text:last-child {
            margin-bottom: 45px
        }

        .swal-footer {
            text-align: right;
            padding-top: 13px;
            margin-top: 13px;
            padding: 13px 16px;
            border-radius: inherit;
            border-top-left-radius: 0;
            border-top-right-radius: 0
        }

        .swal-button-container {
            margin: 5px;
            display: inline-block;
            position: relative
        }

        .swal-button {
            background-color: #7cd1f9;
            color: #fff;
            border: none;
            box-shadow: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 24px;
            margin: 0;
            cursor: pointer
        }

        .swal-button[not:disabled]:hover {
            background-color: #78cbf2
        }

        .swal-button:active {
            background-color: #70bce0
        }

        .swal-button:focus {
            outline: none;
            box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(43, 114, 165, .29)
        }

        .swal-button[disabled] {
            opacity: .5;
            cursor: default
        }

        .swal-button::-moz-focus-inner {
            border: 0
        }

        .swal-button--cancel {
            color: #555;
            background-color: #efefef
        }

        .swal-button--cancel[not:disabled]:hover {
            background-color: #e8e8e8
        }

        .swal-button--cancel:active {
            background-color: #d7d7d7
        }

        .swal-button--cancel:focus {
            box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(116, 136, 150, .29)
        }

        .swal-button--danger {
            background-color: #e64942
        }

        .swal-button--danger[not:disabled]:hover {
            background-color: #df4740
        }

        .swal-button--danger:active {
            background-color: #cf423b
        }

        .swal-button--danger:focus {
            box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(165, 43, 43, .29)
        }

        .swal-content {
            padding: 0 20px;
            margin-top: 20px;
            font-size: medium
        }

        .swal-content:last-child {
            margin-bottom: 20px
        }

        .swal-content__input,
        .swal-content__textarea {
            -webkit-appearance: none;
            background-color: #fff;
            border: none;
            font-size: 14px;
            display: block;
            box-sizing: border-box;
            width: 100%;
            border: 1px solid rgba(0, 0, 0, .14);
            padding: 10px 13px;
            border-radius: 2px;
            transition: border-color .2s
        }

        .swal-content__input:focus,
        .swal-content__textarea:focus {
            outline: none;
            border-color: #6db8ff
        }

        .swal-content__textarea {
            resize: vertical
        }

        .swal-button--loading {
            color: transparent
        }

        .swal-button--loading~.swal-button__loader {
            opacity: 1
        }

        .swal-button__loader {
            position: absolute;
            height: auto;
            width: 43px;
            z-index: 2;
            left: 50%;
            top: 50%;
            -webkit-transform: translateX(-50%) translateY(-50%);
            transform: translateX(-50%) translateY(-50%);
            text-align: center;
            pointer-events: none;
            opacity: 0
        }

        .swal-button__loader div {
            display: inline-block;
            float: none;
            vertical-align: baseline;
            width: 9px;
            height: 9px;
            padding: 0;
            border: none;
            margin: 2px;
            opacity: .4;
            border-radius: 7px;
            background-color: hsla(0, 0%, 100%, .9);
            transition: background .2s;
            -webkit-animation: swal-loading-anim 1s infinite;
            animation: swal-loading-anim 1s infinite
        }

        .swal-button__loader div:nth-child(3n+2) {
            -webkit-animation-delay: .15s;
            animation-delay: .15s
        }

        .swal-button__loader div:nth-child(3n+3) {
            -webkit-animation-delay: .3s;
            animation-delay: .3s
        }

        @-webkit-keyframes swal-loading-anim {
            0% {
                opacity: .4
            }

            20% {
                opacity: .4
            }

            50% {
                opacity: 1
            }

            to {
                opacity: .4
            }
        }

        @keyframes swal-loading-anim {
            0% {
                opacity: .4
            }

            20% {
                opacity: .4
            }

            50% {
                opacity: 1
            }

            to {
                opacity: .4
            }
        }

        .swal-overlay {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0;
            overflow-y: auto;
            background-color: rgba(0, 0, 0, .4);
            z-index: 10000;
            pointer-events: none;
            opacity: 0;
            transition: opacity .3s
        }

        .swal-overlay:before {
            content: " ";
            display: inline-block;
            vertical-align: middle;
            height: 100%
        }

        .swal-overlay--show-modal {
            opacity: 1;
            pointer-events: auto
        }

        .swal-overlay--show-modal .swal-modal {
            opacity: 1;
            pointer-events: auto;
            box-sizing: border-box;
            -webkit-animation: showSweetAlert .3s;
            animation: showSweetAlert .3s;
            will-change: transform
        }

        .swal-modal {
            width: 478px;
            opacity: 0;
            pointer-events: none;
            background-color: #fff;
            text-align: center;
            border-radius: 5px;
            position: static;
            margin: 20px auto;
            display: inline-block;
            vertical-align: middle;
            -webkit-transform: scale(1);
            transform: scale(1);
            -webkit-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            z-index: 10001;
            transition: opacity .2s, -webkit-transform .3s;
            transition: transform .3s, opacity .2s;
            transition: transform .3s, opacity .2s, -webkit-transform .3s
        }

        @media (max-width:500px) {
            .swal-modal {
                width: calc(100% - 20px)
            }
        }

        @-webkit-keyframes showSweetAlert {
            0% {
                -webkit-transform: scale(1);
                transform: scale(1)
            }

            1% {
                -webkit-transform: scale(.5);
                transform: scale(.5)
            }

            45% {
                -webkit-transform: scale(1.05);
                transform: scale(1.05)
            }

            80% {
                -webkit-transform: scale(.95);
                transform: scale(.95)
            }

            to {
                -webkit-transform: scale(1);
                transform: scale(1)
            }
        }

        @keyframes showSweetAlert {
            0% {
                -webkit-transform: scale(1);
                transform: scale(1)
            }

            1% {
                -webkit-transform: scale(.5);
                transform: scale(.5)
            }

            45% {
                -webkit-transform: scale(1.05);
                transform: scale(1.05)
            }

            80% {
                -webkit-transform: scale(.95);
                transform: scale(.95)
            }

            to {
                -webkit-transform: scale(1);
                transform: scale(1)
            }
        }
    </style>

    <style>
        .hover-table tbody tr {
            transition: background-color 0.3s, transform 0.3s;
        }

        .hover-table tbody tr:hover {
            font-weight: bold;
            background-color: #f1f1f1;
            transform: scale(1.02);
        }

        .hover-table {
            overflow: hidden;
            /* Hides the scrollbar */
        }



        .logo-header .logo {
            color: #ffffff;
            opacity: 1;
            position: relative;
            height: 100%;
            display: flex;
            align-items: center
        }

        .new-ticket {
            background-color: #eaf7ff;
            /* Light blue background for new entries */
            border-left: 4px solid #007bff;
            /* Blue accent */
        }

        .highlight {
            color: #007bff;
            /* Highlighted text color */
            font-weight: bold;
        }

        .highlight-time {
            font-style: italic;
            color: #ff5733;
            /* Different color for recent timestamps */
        }
    </style>

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Public+Sans:300,400,500,600,700" media="all">
    <link rel="stylesheet" href="assets/css/fonts.min.css" media="all">
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: { "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['assets/css/fonts.min.css'] },
            active: function () {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/plugins.min.css">
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css">

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="dark">


                    <?php
                    if ($_SESSION["PK_userID"] == $urow['PK_userID']) {
                        if ($_SESSION['FK_Departmentname'] == $urow['PK_DepartmentID']) {
                            $Departmentname = $urow['Departmentname'];
                            echo "<a class='logo' href='home.php'>$Departmentname Department</a>";
                        }
                    }
                    ?>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>

                </div>
                <!-- End Logo Header -->
            </div>
            <?php include 'sidebar.php' ?>
        </div>
        <!-- End Sidebar -->
        <?php include 'maintenanceModal.php' ?>


        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">

                        <?php
                        if ($_SESSION["PK_userID"] == $urow['PK_userID']) {
                            if ($_SESSION['FK_Departmentname'] == $urow['PK_DepartmentID']) {
                                $Departmentname = $urow['Departmentname'];
                                echo "<a class='logo' href='home.php'>$Departmentname Department</a>";
                            }
                        }

                        ?>

                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>

                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <!-- #message notif -->
                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-envelope"></i>
                                    <span id="messageCount" class="notification" style="display: none;"></span>
                                </a>
                                <ul class="dropdown-menu messages-notif-box animated fadeIn"
                                    aria-labelledby="messageDropdown">
                                    <li>
                                        <div class="dropdown-title d-flex justify-content-between align-items-center">
                                            Messages
                                            <a href="#" class="small">Mark all as read</a>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="notif-center" id="messageDropdownList">
                                            <div class="text-center">Loading...</div>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="see-all" href="notifications.php">See all messages<i
                                                class="fa fa-angle-right"></i></a>
                                    </li>
                                </ul>
                            </li>
                            <!--notification  -->
                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-bell"></i>
                                    <span id="notifCount" class="notification" style="display: none;"></span>
                                </a>
                                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">
                                    <li>
                                        <div class="dropdown-title">You have<span id="notifTitleCount">0</span> new
                                            notification(s)</div>
                                    </li>
                                    <li>
                                        <div class="notif-center" id="notifDropdownList">
                                            <div class="text-center">Loading...</div>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="see-all" href="notifications.php">See all notifications<i
                                                class="fa fa-angle-right"></i></a>
                                    </li>
                                </ul>
                            </li>

                            <li class="nav-item topbar-icon dropdown hidden-caret" id="alert_demo_2">
                                <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <i class="fas fa-power-off"></i>
                                </a>
                            </li>
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="profile-pic" href="profile.php" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="<?php echo $urow['profile'] ?>" alt="..."
                                            class="avatar-img rounded-circle">
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi,</span> <span
                                            class="fw-bold"><?php echo $urow['FirstName'] ?>
                                            <?php echo $urow['MiddleName'] ?></span>
                                        <?php echo $urow['LastName'] ?></span>
                                    </span>
                                </a>

                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>



            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Dashboard</h3>
                            <h6 class="op-7 mb-2">MIS Department</h6>
                        </div>
                    </div>

                    <?php
                    $sql = "SELECT u.Username, u.PK_userID, a.PK_activityLogID, a.ticket_no, a.status
                            FROM activity_logs a
                            INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
                            WHERE a.handle_by = ? 
                            AND DATEDIFF(a.dueDate, CURDATE()) = 1"; // Get all due tasks, including "On Hold"
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $_SESSION['Username']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $activeTasks = [];
                    $onHoldTasks = [];

                    while ($row = $result->fetch_assoc()) {
                        if ($row['status'] == 'On Hold') {
                            $onHoldTasks[] = $row;
                        } else {
                            $activeTasks[] = $row;
                        }
                    }

                    $dueActiveCount = count($activeTasks);
                    $dueOnHoldCount = count($onHoldTasks);
                    $totalDueCount = $dueActiveCount + $dueOnHoldCount;

                    // Notification Settings
                    $notifClass = ($totalDueCount > 0) ? 'bg-danger text-white' : 'bg-success text-white';
                    $notifIcon = ($totalDueCount > 0) ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-check-circle-fill"></i>';
                    $notifMessage = ($totalDueCount > 0) ? "You have $totalDueCount task(s) due tomorrow:" : "You're all caught up!";
                    ?>

                    <!-- Toast Notification Container -->
                    <div class="toast-container position-fixed bottom-0 start-0 p-3">
                        <?php if ($totalDueCount > 0): ?>
                            <div id="dueTaskToast" class="toast <?= $notifClass ?>" role="alert" aria-live="assertive"
                                aria-atomic="true">
                                <div class="toast-header">
                                    <?= $notifIcon ?>
                                    <strong class="ms-2 me-auto">Task Reminder</strong>
                                    <small>Just now</small>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast"
                                        aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    <p><?= $notifMessage ?></p>

                                    <!-- Active Tasks -->
                                    <?php if ($dueActiveCount > 0): ?>
                                        <p class="fw-bold text-warning">⚠️ Active Tasks:</p>
                                        <ul class="mb-2">
                                            <?php foreach ($activeTasks as $task): ?>
                                                <li>
                                                    <a href="view_ticket.php?id=<?= $task['PK_activityLogID'] ?>"
                                                        class="text-decoration-none text-white">
                                                        Ticket #<?= $task['ticket_no'] ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <!-- On Hold Tasks -->
                                    <?php if ($dueOnHoldCount > 0): ?>
                                        <p class="fw-bold text-secondary">⏸️ On Hold Tasks:</p>
                                        <ul class="mb-0">
                                            <?php foreach ($onHoldTasks as $task): ?>
                                                <li>
                                                    <a href="view_ticket.php?id=<?= $task['PK_activityLogID'] ?>"
                                                        class="text-decoration-none text-light">
                                                        Ticket #<?= $task['ticket_no'] ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class='row'>
                        <div class="container">
                            <div class="row text-center justify-content-center">
                                <div class="col-md-3">
                                    <h6>Total Ticket this day</h6>
                                    <div class="progress-circle" style="--progress: <?= round($day_progress) ?>%;">
                                        <!-- <span><?= $data['total_tickets_today'] ?></span> -->
                                        <!-- <span id="totalTickets"></span> -->
                                        <span id="totalToday"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <h6>Total Ticket this week</h6>
                                    <div class="progress-circle" style="--progress: <?= round($week_progress) ?>%;">
                                        <span id="totalWeek"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <h6>Total Ticket this month</h6>
                                    <div class="progress-circle" style="--progress: <?= round($month_progress) ?>%;">
                                        <span id="totalMonth"></span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <h6>Total Tickets</h6>
                                    <div class="progress-circle" style="--progress: <?= round($total_progress) ?>%;">
                                        <span id="totalTickets"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="pieChart" style="width: 50%; height: 50%"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="barChart"></canvas>
                            </div>
                        </div>
                    </div>


                    <?php
                    if ($_SESSION["PK_userID"] == $urow['PK_userID']):

                        // Fetch ticket counts based on status
                        if ($_SESSION['FK_userRole'] == 1 || $_SESSION['FK_userRole'] == 2) {

                            $sql = "SELECT status, COUNT(*) as count 
							FROM activity_logs 
							WHERE handle_by = ? 
							GROUP BY status";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $_SESSION['Username']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Initialize status counts
                            $statusCounts = [
                                'Open' => 0,
                                'In Progress' => 0,
                                'Completed' => 0,
                                'On Hold' => 0,
                                'Cancelled' => 0
                            ];

                            while ($row = $result->fetch_assoc()) {
                                $statusCounts[$row['status']] = $row['count'];
                            }
                        }

                        if ($_SESSION['FK_userRole'] == 1 || $_SESSION['FK_userRole'] == 2) {
                            $sql = "SELECT COUNT(*) as count 
									FROM activity_logs 
									WHERE handle_by = ? AND dueDate < CURDATE()";

                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $_SESSION['Username']);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch the count of overdue tickets
                            $row = $result->fetch_assoc();
                            $overdueCount = $row['count'] ?? 0;
                        }

                        if ($_SESSION['FK_userRole'] == 1) {
                            // Fetch overdue tickets only (dueDate before today)
                            if ($_SESSION['FK_userRole'] == 1) {
                                $sqlDue = "SELECT COUNT(*) as due_count FROM activity_logs WHERE dueDate < CURDATE() AND status != 'Cancelled' AND status != 'On Hold'";
                                $stmtDue = $conn->prepare($sqlDue);
                                $stmtDue->execute();
                                $resultDue = $stmtDue->get_result();
                                $dueRow = $resultDue->fetch_assoc();
                                $dueBefore = $dueRow['due_count'] ?? 0; // Default to 0 if no records found
                            }

                            // Fetch tickets due today
                            if ($_SESSION['FK_userRole'] == 1) {
                                $sqlDue = "SELECT COUNT(*) as due_count FROM activity_logs WHERE dueDate = CURDATE() AND status != 'Cancelled'  AND status != 'On Hold'";
                                $stmtDue = $conn->prepare($sqlDue);
                                $stmtDue->execute();
                                $resultDue = $stmtDue->get_result();
                                $dueRow = $resultDue->fetch_assoc();
                                $dueToday = $dueRow['due_count'] ?? 0; // Default to 0 if no records found
                            }

                            // Set class dynamically
                            $overdueClass = ($dueBefore > 0) ? "bg-danger" : "bg-success";
                            $overdueIcon = ($dueBefore > 0) ? "fas fa-exclamation-triangle" : "fas fa-check-circle";

                            $dueTodayClass = ($dueToday > 0) ? "bg-danger" : "bg-success";
                            $dueTodayIcon = ($dueToday > 0) ? "fas fa-exclamation-triangle" : "fas fa-check-circle";
                        }


                        if ($_SESSION['class'] == 'HD') {
                            // Fetch overdue tickets only (dueDate before today)
                            if ($_SESSION['class'] == 'HD') {
                                $sqlNewTick = "SELECT COUNT(*) as due_count FROM activity_logs WHERE handle_by IS NULL";
                                $stmtNewTick = $conn->prepare($sqlNewTick);
                                $stmtNewTick->execute();
                                $resultNewTick = $stmtNewTick->get_result();
                                $newTickets = $resultNewTick->fetch_assoc();
                                $newReqTicket = $newTickets['due_count'] ?? 0; // Default to 0 if no records found
                            }


                            // Set class dynamically
                            $overdueClass = ($newReqTicket > 0) ? "bg-danger" : "bg-success";
                            $overdueIcon = ($newReqTicket > 0) ? "fas fa-exclamation-triangle" : "fas fa-check-circle";


                        }




                        ?>
                        <div class='row'>
                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-warning bubble-shadow-small'>
                                                    <i class='fas fa-envelope-open'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>Open Ticket</p>
                                                    <h4 class='card-title'><?= $statusCounts['Open'] ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-info bubble-shadow-small'>
                                                    <i class='fas fa-spinner'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>In Progress Ticket</p>
                                                    <h4 class='card-title'><?= $statusCounts['In Progress'] ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-success bubble-shadow-small'>
                                                    <i class='fas fa-check-circle'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>Completed Ticket</p>
                                                    <h4 class='card-title'><?= $statusCounts['Completed'] ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-secondary bubble-shadow-small'>
                                                    <i class='fas fa-ban'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>On Hold Ticket</p>
                                                    <h4 class='card-title'><?= $statusCounts['On Hold'] ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-danger bubble-shadow-small'>
                                                    <i class='fas fa-times'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>Cancelled Ticket</p>
                                                    <h4 class='card-title'><?= $statusCounts['Cancelled'] ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class='col-sm-6 col-md-2'>
                                <div class='card card-stats card-round'>
                                    <div class='card-body'>
                                        <div class='row align-items-center'>
                                            <div class='col-icon'>
                                                <div class='icon-big text-center icon-danger bubble-shadow-small'>
                                                    <i class='fas fa-exclamation-circle'></i>
                                                </div>
                                            </div>
                                            <div class='col col-stats ms-3 ms-sm-0'>
                                                <div class='numbers'>
                                                    <p class='card-category'>Overdue Ticket</p>
                                                    <h4 class='card-title'><?= $overdueCount ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($_SESSION['FK_userRole'] == 1): // Display only for admin ?>
                            <div class='row mt-3'>
                                <div class='col-sm-6 col-md-6'>
                                    <div class='card card-stats card-round <?= $overdueClass ?> text-white'>
                                        <div class='card-body'>
                                            <div class='row align-items-center'>
                                                <div class='col-icon'>
                                                    <div class='icon-big text-center icon-light bubble-shadow-small'>
                                                        <i class='<?= $overdueIcon ?>'></i>
                                                    </div>
                                                </div>
                                                <div class='col col-stats ms-3 ms-sm-0'>
                                                    <div class='numbers'>
                                                        <p class='card-category text-white'>Overdue Tickets (Before Today)</p>
                                                        <h4 class='card-title'><?= $dueBefore ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class='col-sm-6 col-md-6'>
                                    <div class='card card-stats card-round <?= $dueTodayClass ?> text-white'>
                                        <div class='card-body'>
                                            <div class='row align-items-center'>
                                                <div class='col-icon'>
                                                    <div class='icon-big text-center icon-light bubble-shadow-small'>
                                                        <i class='<?= $dueTodayIcon ?>'></i>
                                                    </div>
                                                </div>
                                                <div class='col col-stats ms-3 ms-sm-0'>
                                                    <div class='numbers'>
                                                        <p class='card-category text-white'>Due Tickets (Today)</p>
                                                        <h4 class='card-title'><?= $dueToday ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>

                        <?php if ($_SESSION['class'] == 'HD'): // Display only for admin ?>
                            <div class='row mt-3'>
                                <div class='col-sm-6 col-md-6'>
                                    <div id="newReqTicketsContainer" class="card card-stats card-round text-white">
                                        <div class='card-body'>
                                            <div class='row align-items-center'>
                                                <div class='col-icon'>
                                                    <div class='icon-big text-center icon-light bubble-shadow-small'>
                                                        <i id="newReqTicketsIcon"></i>
                                                    </div>
                                                </div>
                                                <div class='col col-stats ms-3 ms-sm-0'>
                                                    <div class='numbers'>
                                                        <p class='card-category text-white'>Unassigned Ticket</p>
                                                        <h4 class='card-title' id="newReqTickets">0</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>

                        <?php endif; ?>
                    <?php endif; ?>


                    <?php
                    if ($_SESSION['FK_userRole'] == 1) {
                        // Fetch overdue tickets only (dueDate before today)
                        $sqlDue = "SELECT ticket_no, handle_by, created_at 
							FROM activity_logs 
							WHERE dueDate < CURDATE()"; // Overdue tickets
                    
                        $stmtDue = $conn->prepare($sqlDue);
                        $stmtDue->execute();
                        $resultDue = $stmtDue->get_result();
                    }


                    ?>

                    <?php
                    if ($_SESSION['FK_userRole'] == 1) {
                        // Fetch users
                        $slquser = "SELECT u.*, d.*
							FROM tbl_users u
							INNER JOIN db_userRole d ON u.FK_userRole = d.PK_userRole
							WHERE u.AccountStatus = 'ACTIVE'";

                        $stmtuser = $conn->prepare($slquser);
                        $stmtuser->execute();
                        $userRes = $stmtuser->get_result();

                        // Fetch user ranks
                        // $sqlUserrank = "SELECT u.*, d.*
                        // 	FROM tbl_users u
                        // 	INNER JOIN db_userRole d ON u.FK_userRole = d.PK_userRole
                        // 	WHERE u.AccountStatus = 'ACTIVE'";
                        $sqlUserrank = "SELECT 
                            u.*, d.*, 
                            COUNT(a.PK_activityLogID) AS total_completed, 
                            (SELECT COUNT(*) FROM activity_logs ) AS total_assigned,
                            RANK() OVER (ORDER BY COUNT(a.PK_activityLogID) DESC) AS ranking
                        FROM activity_logs a
                        INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
                        INNER JOIN db_userrole d ON u.FK_userRole = d.PK_userRole
                        WHERE a.status = 'completed'
                        AND (TIMESTAMPDIFF(DAY, a.created_at, a.dateTimeAccomp) <= 5)
                        GROUP BY u.PK_userID
                        ORDER BY total_completed DESC";

                        $stmtUserrank = $conn->prepare($sqlUserrank);
                        $stmtUserrank->execute();
                        $userRank = $stmtUserrank->get_result();



                    }

                    if ($_SESSION['FK_userRole'] == 1) {
                        // Fetch overdue tickets only (dueDate before today)
                        $sqlhistory = "SELECT h.*, u.*
							FROM tbl_history h
							INNER JOIN tbl_users u ON h.FK_userID = u.PK_userID";

                        $stmthistory = $conn->prepare($sqlhistory);
                        $stmthistory->execute();
                        $historyRes = $stmthistory->get_result();
                    }

                    ?>
                    <!-- For Super Admin only -->
                    <?php if ($_SESSION['FK_userRole'] == 1): ?>
                        <!-- Table -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-round">
                                    <div class="card-header">
                                        <div class="card-head-row card-tools-still-right">
                                            <h4 class="card-title">Ticket Status</h4>
                                            <div class="card-tools">
                                                <button class="btn btn-icon btn-link btn-primary btn-xs toggle-card">
                                                    <span class="fa fa-angle-down"></span>
                                                </button>
                                                <button class="btn btn-icon btn-link btn-primary btn-xs btn-refresh-card">
                                                    <span class="fa fa-sync-alt"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="card-category">Overview of Ticket Status: Due Today and Overdue</p>
                                    </div>
                                    <div class="card-body cards">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="filterStatus">Filter By:</label>
                                                <select id="filterStatus" class="form-control">
                                                    <option value="all">All</option>
                                                    <option value="due-today">Due Today</option>
                                                    <option value="overdue">Overdue</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive p-3">
                                                    <table id="basic-datatables"
                                                        class="display table table-striped table-hover hover-table"
                                                        width="100%">
                                                        <thead>
                                                            <tr>
                                                                <th></th>
                                                                <th>Ticket No</th>
                                                                <th>Priority</th>
                                                                <th>Task Title</th>
                                                                <th>Concern</th>
                                                                <th>Handle By</th>
                                                                <th>Due Date</th>
                                                                <th>Remaining Days</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="filteringTable">
                                                            <?php
                                                            $sql = "SELECT * FROM activity_logs WHERE dueDate <= CURDATE() AND status != 'Cancelled' AND status != 'On Hold'";

                                                            $stmt = $conn->prepare($sql);

                                                            $stmt->execute();
                                                            $result = $stmt->get_result();

                                                            if ($result->num_rows > 0): ?>
                                                                <?php while ($row = $result->fetch_assoc()):
                                                                    // Determine background color based on status
                                                                    $bgColors = [
                                                                        'Completed' => '#00D26A',
                                                                        'In Progress' => '#00A6ED',
                                                                        'Cancelled' => '#F92F60',
                                                                        'On Hold' => '#8A95A0',
                                                                        'Open' => '#FCD53F'
                                                                    ];
                                                                    $bgColor = $bgColors[$row["status"]] ?? 'transparent';
                                                                    $dueDate = new DateTime($row["dueDate"]);
                                                                    $currentDate = new DateTime();
                                                                    $interval = $currentDate->diff($dueDate);
                                                                    $remainingDays = $interval->days; // Get the absolute difference in days
                                                        
                                                                    ?>
                                                                    <tr data-id="<?= $row["PK_activityLogID"] ?>"
                                                                        data-ticketNo="<?= $row["ticket_no"] ?>" id="viewBtn"
                                                                        style="cursor: pointer">
                                                                        <td data-bs-toggle="tooltip" data-bs-placement="top"
                                                                            title="<?= $row["status"] ?>"
                                                                            style="background-color: <?= $bgColor ?>; width: 0px;">
                                                                        </td>
                                                                        <td><?= $row["ticket_no"] ?></td>
                                                                        <td><?= $row["priority"] ?></td>
                                                                        <td><?= $row["task_description"] ?></td>
                                                                        <td style="width:20%">
                                                                            <?php
                                                                            $words = explode(' ', $row["concern"]);
                                                                            $limited_text = implode(' ', array_slice($words, 0, 20));
                                                                            echo htmlspecialchars($limited_text);
                                                                            if (count($words) > 20)
                                                                                echo '...'; // Add ellipsis if truncated
                                                                            ?>
                                                                        </td>
                                                                        <td><?= $row["handle_by"] ?></td>
                                                                        <td><?= date("m/d/y", strtotime($row["dueDate"])) ?></td>
                                                                        <td>
                                                                            <?php if ($remainingDays > 0): ?>
                                                                                <?= $remainingDays ?> Days
                                                                            <?php elseif ($remainingDays < 0): ?>
                                                                                <?= $remainingDays ?> Day
                                                                            <?php else: ?>
                                                                                Due Today
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <button type="button"
                                                                                class="btn btn-primary btn-xs view-btn"
                                                                                data-id="<?= $row["PK_activityLogID"] ?>"
                                                                                data-ticketNo="<?= $row["ticket_no"] ?>"
                                                                                style="margin:5px;">
                                                                                <span class="icon-eye"></span>
                                                                            </button>
                                                                            <button type="button"
                                                                                class="btn btn-success btn-xs message-btn"
                                                                                data-id="<?= $row["PK_activityLogID"] ?>"
                                                                                data-ticketNo="<?= $row["ticket_no"] ?>"
                                                                                data-fkuser="<?= $row["FK_userID"] ?>"
                                                                                style="margin:5px;">
                                                                                <span class="fas fa-comment"></span>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            <?php endif;

                                                            $stmt->close();
                                                            $conn->close();
                                                            ?>


                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <th></th>
                                                                <th>Ticket No</th>
                                                                <th>Priority</th>
                                                                <th>Task Title</th>
                                                                <th>Concern</th>
                                                                <th>Handle By</th>
                                                                <th>Due Date</th>
                                                                <th>Remaining Days</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- User and History -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card card-round">
                                    <div class="card-body">
                                        <div class="card-head-row card-tools-still-right">
                                            <div class="card-title">Users</div>
                                            <div class="card-tools">
                                                <div class="dropdown">
                                                    <button class="btn btn-icon btn-clean me-0" type="button"
                                                        data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="#">Action</a>
                                                        <a class="dropdown-item" href="#">Another action</a>
                                                        <a class="dropdown-item" href="#">Something else here</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-list py-4">
                                            <?php while ($row = $userRes->fetch_assoc()): ?>
                                                <div class="item-list">
                                                    <div class="avatar">
                                                        <?php if (!empty($row['profile'])): ?>
                                                            <img src="<?= htmlspecialchars($row['profile']); ?>"
                                                                class="avatar-img rounded-circle">
                                                        <?php else: ?>
                                                            <span
                                                                class="avatar-title rounded-circle border border-white bg-secondary">
                                                                <?= strtoupper(substr($row['FirstName'], 0, 1)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="info-user ms-3">
                                                        <div class="username"><?= htmlspecialchars($row['FirstName']); ?></div>
                                                        <div class="status"><?= htmlspecialchars($row['Type']); ?> /
                                                            <?= htmlspecialchars($row['class']); ?>
                                                        </div>

                                                    </div>
                                                    <button class="btn btn-icon btn-link op-8 me-1"><i
                                                            class="far fa-envelope"></i></button>
                                                    <button class="btn btn-icon btn-link btn-danger op-8"><i
                                                            class="fas fa-ban"></i></button>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-round">
                                    <div class="card-body">
                                        <div class="card-head-row card-tools-still-right">
                                            <div class="card-title">User Rank</div>
                                            <div class="card-tools">
                                                <div class="dropdown">
                                                    <button class="btn btn-icon btn-clean me-0" type="button"
                                                        id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Action</a>
                                                        <a class="dropdown-item" href="#">Another action</a>
                                                        <a class="dropdown-item" href="#">Something else here</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-list py-4">
                                            <?php while ($row = $userRank->fetch_assoc()):
                                                $totalCompleted = $row['total_completed'];
                                                $totalAssigned = $row['total_assigned'];
                                                $progressPercentage = ($totalAssigned > 0) ? min(100, ($totalCompleted / $totalAssigned) * 100) : 0;
                                                ?>
                                                <div class="item-list">
                                                    <div class="avatar">
                                                        <?php if (!empty($row['profile'])): ?>
                                                            <img src="<?= htmlspecialchars($row['profile']); ?>"
                                                                class="avatar-img rounded-circle">
                                                        <?php else: ?>
                                                            <span
                                                                class="avatar-title rounded-circle border border-white bg-secondary">
                                                                <?= strtoupper(substr($row['FirstName'], 0, 1)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="info-user ms-3">
                                                        <div class="username"><?= htmlspecialchars($row['FirstName']); ?></div>
                                                        <div class="status"><?= htmlspecialchars($row['class']); ?></div>
                                                        <div class="completed-tickets">Completed: <?= $totalCompleted; ?> /
                                                            <?= $totalAssigned; ?> tickets
                                                        </div>
                                                        <div class="progress progress-sm">
                                                            <div class="progress-bar bg-info" role="progressbar"
                                                                style="width: <?= $progressPercentage; ?>%;"
                                                                aria-valuenow="<?= $progressPercentage; ?>" aria-valuemin="0"
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button class="btn btn-icon btn-link op-8 me-1"><i
                                                            class="far fa-envelope"></i></button>
                                                    <button class="btn btn-icon btn-link btn-danger op-8"><i
                                                            class="fas fa-ban"></i></button>
                                                </div>
                                            <?php endwhile; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="card card-round">
                                    <div class="card-header">
                                        <div class="card-head-row card-tools-still-right">
                                            <div class="card-title">Ticket History</div>
                                            <div class="card-tools">
                                                <div class="dropdown">
                                                    <button class="btn btn-icon btn-clean me-0" type="button"
                                                        id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <a class="dropdown-item" href="#">Action</a>
                                                        <a class="dropdown-item" href="#">Another action</a>
                                                        <a class="dropdown-item" href="#">Something else here</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="card-body p-0">
                                        <div class="table-responsive p-3">
                                            <div class="col-md-4">
                                                <button class="btn btn-primary btn-xs d-flex align-items-center gap-2"
                                                    id="filterBtn">
                                                    <i class="fas fa-filter"></i> <span>Apply Filter</span>
                                                </button>
                                            </div>
                                            <div class="d-flex justify-content-between">


                                                <div class="col-md-4">
                                                    <label for="dateRangeFilterStart">Filter by Date Range:</label>
                                                    <div class="d-flex align-items-center">
                                                        <input type="date" id="dateRangeFilterStart"
                                                            class="form-control me-2">
                                                        <span class="mx-2">-</span>
                                                        <input type="date" id="dateRangeFilterEnd" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <?php
                                                    include 'inc/conn.php';
                                                    $sql = "SELECT * FROM tbl_users WHERE AccountStatus = 'ACTIVE'";
                                                    $result = $conn->query($sql);
                                                    ?>
                                                    <label for="usernameSelect">Select Username:</label>
                                                    <select class="form-control form-control-sm" id="usernameSelect">
                                                        <option value="">Select Username</option>
                                                        <?php
                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                echo '<option value="' . htmlspecialchars($row['PK_userID']) . '">' . htmlspecialchars($row['Username']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>


                                            <br>
                                            <!-- Projects table -->
                                            <table class="table align-items-center mb-0" id="dataTable">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th scope="col">Ticket No</th>
                                                        <th scope="col">Changes</th>
                                                        <th scope="col" class="text-end">Transaction Date</th>
                                                        <th scope="col" class="text-end">User ID</th>
                                                        <th scope="col" class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="ticket">
                                                    <?php while ($row = $historyRes->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($row['ticket_no']); ?></td>
                                                            <td>
                                                                <strong>Old:</strong>
                                                                <?php echo htmlspecialchars($row['old_value']); ?> <br>
                                                                <strong>New:</strong>
                                                                <?php echo htmlspecialchars($row['new_value']); ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <?php echo date("M d, Y, h:i A", strtotime($row['transaction_date'])); ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <?php echo htmlspecialchars($row['Username']); ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <?php
                                                                $action = htmlspecialchars($row['action']); // Get action value
                                                                $badgeClass = '';

                                                                // Assign badge color based on action
                                                                switch ($action) {
                                                                    case 'ADD':
                                                                        $badgeClass = 'badge-info'; // Blue for ADD
                                                                        break;
                                                                    case 'UPDATE':
                                                                        $badgeClass = 'badge-warning'; // Yellow for UPDATE
                                                                        break;
                                                                    case 'DELETE':
                                                                        $badgeClass = 'badge-danger'; // Red for DELETE
                                                                        break;
                                                                    default:
                                                                        $badgeClass = 'badge-secondary'; // Default gray for other actions
                                                                        break;
                                                                }
                                                                ?>
                                                                <span
                                                                    class="badge <?php echo $badgeClass; ?>"><?php echo $action; ?></span>
                                                            </td>

                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                    <!-- For HD only -->
                    <?php if ($_SESSION['class'] == 'HD'): ?>
                        <!-- Table -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-round">
                                    <div class="card-header">
                                        <div class="card-head-row card-tools-still-right">
                                            <h4 class="card-title">Ticket Unassigned</h4>
                                            <div class="card-tools">
                                                <button class="btn btn-icon btn-link btn-primary btn-xs toggle-card">
                                                    <span class="fa fa-angle-down"></span>
                                                </button>
                                                <button class="btn btn-icon btn-link btn-primary btn-xs btn-refresh-card">
                                                    <span class="fa fa-sync-alt"></span>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="card-category">Overview of Ticket Unassigned</p>
                                    </div>
                                    <div class="card-body cards">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive">
                                                    <table id="ticketTable" class="table table-striped hover-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Ticket No</th>
                                                                <th>Priority</th>
                                                                <th>Task Title</th>
                                                                <th>Concern</th>
                                                                <th>Created At</th>
                                                                <th>Due Date</th>
                                                                <th>Remaining Days</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="filteringTable">
                                                            <?php
                                                            $sql = "SELECT * FROM activity_logs WHERE handle_by IS NULL";
                                                            $result = $conn->query($sql);

                                                            if ($result && $result->num_rows > 0):
                                                                while ($row = $result->fetch_assoc()):
                                                                    // Convert dates to DateTime objects
                                                                    $dueDate = new DateTime($row["dueDate"]);
                                                                    $created_at = new DateTime($row["created_at"]);
                                                                    $currentDate = new DateTime(); // Get today's date
                                                        
                                                                    // Calculate days left
                                                                    $interval = $currentDate->diff($dueDate);
                                                                    $daysLeft = $interval->days;

                                                                    // Determine status (overdue, due today, or upcoming)
                                                                    if ($currentDate > $dueDate) {
                                                                        $status = "overdue";
                                                                        $dueMessage = "Due date has passed by {$daysLeft} days.";
                                                                    } elseif ($currentDate == $dueDate) {
                                                                        $status = "due-today";
                                                                        $dueMessage = "Today is the due date.";
                                                                    } else {
                                                                        $status = "upcoming";
                                                                        $dueMessage = "There are {$daysLeft} days left until the due date.";
                                                                    }

                                                                    // Format due date for display
                                                                    $formattedDate = $dueDate->format("F d, Y");
                                                                    $formatCreated = $created_at->format("F d, Y");

                                                                    ?>
                                                                    <tr class="<?= $status; ?>"
                                                                        data-id="<?= $row["PK_activityLogID"] ?>">
                                                                        <td><?= htmlspecialchars($row["ticket_no"]) ?></td>
                                                                        <td><?= htmlspecialchars($row["priority"]) ?></td>
                                                                        <td><?= htmlspecialchars($row["task_description"]) ?></td>
                                                                        <td style="width:30%">
                                                                            <?php
                                                                            $words = explode(' ', $row["concern"]);
                                                                            $limited_text = implode(' ', array_slice($words, 0, 20));
                                                                            echo htmlspecialchars($limited_text);
                                                                            if (count($words) > 20)
                                                                                echo '...';
                                                                            ?>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($formatCreated) ?></td>
                                                                        <td><?= htmlspecialchars($formattedDate) ?></td>
                                                                        <td><?= htmlspecialchars($dueMessage) ?></td>
                                                                        <td>
                                                                            <a href="view_ticket.php?id=<?= $row['PK_activityLogID'] ?>"
                                                                                class="btn btn-info btn-xs edit-btn">
                                                                                <span class="icon-pencil"></span>
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                    <?php
                                                                endwhile;
                                                            endif;

                                                            $result->free();
                                                            $conn->close();
                                                            ?>
                                                        </tbody>

                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    <?php endif; ?>

                    <!-- Calendar -->
                    <?php if ($_SESSION['FK_userRole'] == 2 || $_SESSION['FK_userRole'] == 3): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">Ticket Schedule</div>
                                        <div class="card-category"></div>
                                    </div>
                                    <div class="card-body">
                                        <div id="calendar" class="fc fc-unthemed fc-ltr">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    MIS Department
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    Help
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    Licenses
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="copyright ms-auto">
                        2024, made with <i class="fa fa-heart heart text-danger"></i> by <a
                            href="http://www.themekita.com">ThemeKita</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- data-backdrop="static" -->
    <div class="modal" id="ViewModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header ModalHeaderDesign">

                    <h4 class="modal-title">
                        <b style="color:white">VIEW DATA</b>
                    </h4>

                    <button type="button" class="close" id="closeBtn" data-bs-dismiss="modal"
                        style="color:white">&times;</button>

                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <form id="ViewForm">
                        <div class="row">
                            <div class="col-md-3">

                                <h4>Ticket Number: <b id="ViewNumber" style="color:#146734; font-size: medium;"></b>
                                </h4>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <select class="form-control" id="ViewTaskCategory" name="ViewTaskCategory" disabled>
                                        <option disabled selected value="">Please Select</option>
                                        <option value="Hardware">Hardware</option>
                                        <option value="Software">Software</option>
                                        <option value="Graphics">Graphics</option>
                                        <option value="Others">Others</option>
                                    </select>
                                    <label class="floating-label">Task Category<b style="color:red">*</b></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" list="TaskDescriptionSuggestion" name="ViewTaskDescription"
                                        id="ViewTaskDescription" class="form-control" readonly>
                                    <label class="floating-label">Task Title<b style="color:red">*</b></label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="datetime-local" class="form-control" id="ViewDateAndTimeReq"
                                                name="ViewDateAndTimeReq" step="60" placeholder=" " readonly>
                                            <label class="floating-label">Date & Time Request<b
                                                    style="color:red">*</b></label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <?php
                                            include 'inc/conn.php';
                                            $sql = "SELECT * FROM db_departmentgroups WHERE is_Active = 1";
                                            $result = $conn->query($sql);
                                            echo '<select  name="ViewRequestorDepartment" id="ViewRequestorDepartment"
                                            class="form-control" disabled>';
                                            echo '<option disabled selected value="">Please Select</option>';

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $Departmentname = $row['Departmentname'];
                                                    $PK_DepartmentID = $row['PK_DepartmentID'];
                                                    echo '<option value="' . $PK_DepartmentID . '">' . $Departmentname . '</option>';
                                                }
                                            }
                                            echo '</select>';
                                            echo '<label class="floating-label">Requestor Department<b style="color:red">*</b></label>';


                                            ?>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="text" id="ViewPosition" name="ViewPosition"
                                                class="form-control" placeholder=" " readonly disabled>
                                            <label class="floating-label">Position<b style="color:red">*</b></label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="text" id="ViewRequestorName" name="ViewRequestorName"
                                                class="form-control" placeholder=" " readonly disabled>
                                            <label class="floating-label">Requestor Name<b
                                                    style="color:red">*</b></label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <select name="ViewPriority" id="ViewPriority" class="form-control" disabled>
                                                <option disabled selected value="">Please Select</option>
                                                <option value="Urgent">Urgent</option>
                                                <option value="High">High</option>
                                                <option value="Medium">Medium</option>
                                                <option value="Low">Low</option>
                                            </select>
                                            <label class="floating-label">Priority<b style="color:red">*</b></label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <select name="ViewStatus" id="ViewStatus" class="form-control" disabled>
                                                <option disabled selected value="">Please Select</option>
                                                <option value="Completed">Completed</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Cancelled">Cancelled</option>
                                                <option value="On Hold">On Hold</option>
                                                <option value="Overdue">Overdue</option>
                                                <option value="Pending Review">Pending Review</option>
                                                <option value="Reopened">Reopened</option>
                                                <option value="Open">Open</option>
                                            </select>
                                            <label class="floating-label">Status<b style="color:red">*</b></label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">

                                        <div class="form-group">
                                            <select name="ViewSeverity" id="ViewSeverity" class="form-control" disabled>
                                                <option disabled selected value="">Please Select</option>
                                                <option value="Visit">Visit</option>
                                                <option value="Remote">Remote</option>
                                                <option value="Call">Call</option>
                                            </select>
                                            <label class="floating-label">Nature of Support<b
                                                    style="color:red">*</b></label>
                                        </div>

                                    </div>


                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <?php
                                            include 'inc/conn.php';
                                            $sql = "SELECT Username FROM tbl_users";
                                            $result = $conn->query($sql);

                                            echo '<select name="ViewAccompBy" id="ViewAccompBy" class="form-control" disabled>';
                                            echo '<option disabled selected value="">Please Select</option>';

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $username = $row['Username'];
                                                    echo '<option value="' . $username . '">' . $username . '</option>';
                                                }
                                            }
                                            echo '</select>';
                                            ?>
                                            <label class="floating-label">Handled By<b style="color:red">*</b></label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="datetime-local" class="form-control" id="ViewDateTimeAccomp"
                                                name="ViewDateTimeAccomp" step="60" readonly>
                                            <label class="floating-label">D/T Accomplished</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea class="form-control" name="ViewConcern" id="ViewConcern" rows="3"
                                                readonly></textarea>
                                            <label class="floating-label">Concern<b style="color:red">*</b></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <textarea class="form-control" name="ViewResolution" id="ViewResolution"
                                                rows="20" readonly></textarea>
                                            <label class="floating-label">Resolution</label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="text" id="createdBy" name="createdBy" class="form-control" placeholder=" "
                                    readonly disabled>
                                <label class="floating-label">Created By<b style="color:red">*</b></label>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="closeBtn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <!-- <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script> -->

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Moment JS -->
    <script src="assets/js/plugin/moment/moment.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>&gt;



    <!-- Fullcalendar -->
    <script src="assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>
    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>
    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- <script src='https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js'></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Select2 JS (for multi-select dropdowns) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- Logout modal -->
    <script>
        // For Logout alert
        $('#alert_demo_2').click(function (e) {
            swal({
                title: "Are you sure you want to logout?",
                text: "This action will end your session.",
                icon: "info", // Displays an info icon
                buttons: {

                    confirm: {
                        text: "Yes",
                        value: true,
                        visible: true,
                        className: 'btn btn-success',
                        closeModal: false, // Keeps the alert open when "Yes" is clicked
                    },

                    cancel: {
                        text: "No",
                        value: null,
                        visible: true,
                        className: 'btn btn-danger',
                        closeModal: true, // Keeps the alert open when "No" is clicked
                    }
                },
                closeOnClickOutside: false, // Prevents closing the alert by clicking outside
            }).then((isConfirm) => {
                if (isConfirm) {
                    // Proceed to logout.php
                    window.location.href = "logout.php";
                }
            });
        });


    </script>

    <!-- CAlendar -->
    <script>
        $(document).ready(function () {
            var $calendar = $('#calendar');

            $calendar.fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay,listWeek'
                },
                selectable: true,
                selectHelper: true,
                events: function (start, end, timezone, callback) {
                    $.ajax({
                        url: 'fetch_events.php',
                        type: 'GET',
                        success: function (response) {
                            var events = JSON.parse(response);
                            callback(events);
                        }
                    });
                },
                select: function (start, end) {
                    // Open SweetAlert modal for event creation
                    // swal({
                    //     title: "Create an Event",
                    //     text: "Enter event title:",
                    //     content: {
                    //         element: "input",
                    //         attributes: {
                    //             placeholder: "Event Title",
                    //             type: "text",
                    //             className: "form-control"
                    //         }
                    //     },
                    //     buttons: {
                    //         cancel: {
                    //             text: "Cancel",
                    //             value: null,
                    //             visible: true,
                    //             className: "btn btn-danger",
                    //             closeModal: true
                    //         },
                    //         confirm: {
                    //             text: "Save",
                    //             value: true,
                    //             visible: true,
                    //             className: "btn btn-success"
                    //         }
                    //     },
                    //     closeOnClickOutside: false // Disable clicking outside the modal
                    // }).then((eventTitle) => {
                    //     if (eventTitle) {
                    //         $.ajax({
                    //             url: 'save_event.php',
                    //             type: 'POST',
                    //             data: {
                    //                 title: eventTitle,
                    //                 start: start.format(), // Convert to MySQL format
                    //                 end: end.format(), // Optional: End date
                    //                 className: "fc-primary"
                    //             },
                    //             success: function (response) {
                    //                 if (response === "success") {
                    //                     $calendar.fullCalendar('refetchEvents'); // Reload events
                    //                     swal("Success!", "Event created successfully.", "success");
                    //                 } else {
                    //                     swal("Error!", "Failed to create event.", "error");
                    //                 }
                    //             }
                    //         });
                    //     }
                    // });

                    $calendar.fullCalendar('unselect'); // Prevent multiple selections
                }
            });
        });
    </script>

    <!-- Top bar icon -->
    <script>
        $(document).ready(function () {
            $(".topbar-icon").on("click", function (e) {
            });
        });
    </script>

    <!-- For up angle and reload the card body -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Expand/Collapse button
            const toggleBtn = document.querySelector(".toggle-card");
            const cardBody = document.querySelector(".cards");

            if (toggleBtn && cardBody) {
                toggleBtn.addEventListener("click", function () {
                    let icon = this.querySelector("span");
                    if (cardBody.style.display === "none") {
                        cardBody.style.display = "block";
                        if (icon) icon.classList.replace("fa-angle-up", "fa-angle-down");
                    } else {
                        cardBody.style.display = "none";
                        if (icon) icon.classList.replace("fa-angle-down", "fa-angle-up");
                    }
                });
            }

            // Refresh button
            const refreshBtn = document.querySelector(".btn-refresh-card");
            if (refreshBtn) {
                refreshBtn.addEventListener("click", function () {
                    location.reload(); // Refresh the page
                });
            }

            // Optional Close button
            // const closeBtn = document.querySelector(".close-card");
            // if (closeBtn) {
            //     closeBtn.addEventListener("click", function () {
            //         document.querySelector(".card").style.display = "none";
            //     });
            // }
        });
    </script>


    <!-- Initialize DataTables -->
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                "pageLength": 10, // Default rows per page
                "lengthMenu": [5, 10, 25, 50, 100], // Dropdown options for row count
                "ordering": true, // Enable sorting
                "searching": true, // Enable search box
                "responsive": true, // Make table responsive
                "language": {
                    "search": "Search records:",
                    "lengthMenu": "Show _MENU_ rows per page"
                }
            });
        });
    </script>

    <!-- Filter btn -->
    <script>
        $(document).ready(function () {
            $("#filterBtn").click(function () {
                let dateRangeFilterStart = $("#dateRangeFilterStart").val();
                let dateRangeFilterEnd = $("#dateRangeFilterEnd").val();
                let usernameSelectval = $("#usernameSelect").val(); // Fix key name

                let date1 = new Date(dateRangeFilterStart);
                let date2 = new Date(dateRangeFilterEnd);

                if (date1 > date2) {
                    swal({
                        icon: 'warning',
                        text: 'Invalid Date Range, "Date To" should not be earlier than "Date From".',
                        title: "Warning"
                    });
                    return;
                }

                $.ajax({
                    url: "ticketHis_filtering.php",
                    type: "POST",
                    data: {
                        dateRangeFilterStart: dateRangeFilterStart,
                        dateRangeFilterEnd: dateRangeFilterEnd,
                        usernameSelectval: usernameSelectval // Match PHP variable
                    },
                    success: function (response) {
                        $("#ticket").html(response);
                    },
                    error: function () {
                        swal({
                            icon: 'error',
                            text: 'Error fetching data. Please try again.',
                            title: "Error"
                        });
                    }
                });
            });
        });
    </script>

    <div
        style="left: -1000px; overflow: scroll; position: absolute; top: -1000px; border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        <div style="border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        </div>
    </div>

    <!-- Pie chart and Bar graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // PIE CHART
            const ctxPie = document.getElementById('pieChart').getContext('2d');
            const pieLabels = chartData.pieChart.map(item => item.status);
            const pieData = chartData.pieChart.map(item => item.total);

            // Define colors based on status
            const statusColors = {
                'completed': '#28a745',
                'cancelled': '#dc3545',
                'open': '#FFAD46',
                'in progress': '#48ABF7',
                'on hold': '#6861CE'
            };

            const pieColors = chartData.pieChart.map(item => statusColors[item.status.toLowerCase()] || '#CCCCCC');

            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieData,
                        backgroundColor: pieColors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: {
                            fontColor: 'rgb(154, 154, 154)',
                            fontSize: 11,
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            });

            // BAR CHART
            const ctxBar = document.getElementById('barChart').getContext('2d');

            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: chartData.barChart.months,
                    datasets: [
                        {
                            label: 'Completed',
                            data: chartData.barChart.completed,
                            backgroundColor: '#28a745'
                        },
                        {
                            label: 'Cancelled',
                            data: chartData.barChart.cancelled,
                            backgroundColor: '#dc3545'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
    <!-- test -->
    <script>
        function loadGraph() {
            setInterval(function () {

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("totalTickets").innerHTML = this.responseText;
                    }
                };
                xhttp.open("GET", "data.php", true);
                xhttp.send();

            }, 1000);
        }
        loadGraph();
    </script>

    <!-- Message button -->
    <script>
        $(document).ready(function () {
            $(".message-btn").on("click", function () {
                let ticketNo = $(this).data("ticketno");
                let fkUser = $(this).data("fkuser");

                swal({
                    title: "Enter Your Message",
                    text: "Type your response below:",
                    content: {
                        element: "input",
                        attributes: {
                            placeholder: "Type your message here...",
                            type: "text",
                        },
                    },
                    buttons: ["Cancel", "Send"],
                }).then((message) => {
                    if (message) {

                        $.ajax({
                            url: "send_message.php",
                            method: "POST",
                            data: {
                                ticketNo: ticketNo, // Fix: Ensure ticketNo is included
                                fkUser: fkUser,
                                message: message,
                            },
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    swal("Success", response.message, "success");
                                } else {
                                    swal("Error", response.message, "error");
                                }
                            },
                            error: function () {
                                swal("Error", "Failed to send the message.", "error");
                            },
                        });
                    }
                });
            });
        });

    </script>

    <!-- Getting real time of total tickets -->
    <script type="text/javascript">
        function loadDoc() {
            setInterval(function () {

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("totalTickets").innerHTML = this.responseText;
                    }
                };
                xhttp.open("GET", "data.php", true);
                xhttp.send();

            }, 1000);
        }
        loadDoc();

        function ticketToday() {
            setInterval(function () {

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("totalToday").innerHTML = this.responseText;
                    }
                };
                xhttp.open("GET", "totalTicketToday.php", true);
                xhttp.send();

            }, 1000);
        }
        ticketToday();

        function ticketMonth() {
            setInterval(function () {

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("totalMonth").innerHTML = this.responseText;
                    }
                };
                xhttp.open("GET", "totalTicketMonth.php", true);
                xhttp.send();

            }, 1000);
        }
        ticketMonth();


        function ticketWeek() {
            setInterval(function () {

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("totalWeek").innerHTML = this.responseText;
                    }
                };
                xhttp.open("GET", "totalTicketWeek.php", true);
                xhttp.send();

            }, 1000);
        }
        ticketWeek();



    </script>

    <!-- Getting real time of new requestTickets -->
    <script type="text/javascript">
        function loadDoc() {
            setInterval(function () {
                // Get the elements once per interval
                var container = document.getElementById("newReqTicketsContainer");
                var icon = document.getElementById("newReqTicketsIcon");
                var ticketText = document.getElementById("newReqTickets");

                // Exit if any of the elements are not found
                if (!container || !icon || !ticketText) {
                    return;
                }

                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var ticketCount = parseInt(this.responseText.trim()); // Ensure it's an integer

                        // Update text
                        ticketText.innerHTML = ticketCount;

                        // Reset classes before applying new ones
                        container.className = "card card-stats card-round text-white";
                        icon.className = ""; // Reset icon class

                        // Update class based on the count
                        if (ticketCount > 0) {
                            container.classList.add("bg-danger", "text-white");
                            icon.classList.add("fas", "fa-exclamation-triangle");
                        } else {
                            container.classList.add("bg-success", "text-white");
                            icon.classList.add("fas", "fa-check-circle");
                        }
                    }
                };
                xhttp.open("GET", "newReqTickets.php", true);
                xhttp.send();
            }, 1000);
        }
        loadDoc();
    </script>


    <!-- Getting real time of messages -->
    <script>
        function loadMessages() {
            setInterval(function () {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var response = JSON.parse(this.responseText);

                        // Update message count
                        var messageCountElement = document.getElementById("messageCount");
                        if (response.total > 0) {
                            messageCountElement.innerText = response.total;
                            messageCountElement.style.display = "inline-block";
                        } else {
                            messageCountElement.style.display = "none";
                        }

                        // Update message dropdown
                        var messageList = document.getElementById("messageDropdownList");
                        messageList.innerHTML = "";

                        if (response.messages.length > 0) {
                            response.messages.forEach(function (msg) {
                                var messageItem = `
                            <a href="#">
                                <div class="notif-img">
                                    <img src="${msg.profile}" width="20px" alt="Profile Image">
                                </div>
                                <div class="notif-content">
                                    <span class="subject">${msg.from_user}</span>
                                    <span class="block">${msg.message}</span>
                                    <span class="time">${msg.time}</span>
                                </div>
                            </a>`;
                                messageList.innerHTML += messageItem;
                            });
                        } else {
                            messageList.innerHTML = '<div class="text-center">No new messages</div>';
                        }
                    }
                };
                xhttp.open("GET", "fetch_messages.php", true);
                xhttp.send();
            }, 1000);
        }

        // Start fetching messages
        loadMessages();
    </script>


    <!-- Getting real time of notifications -->
    <audio src="audio/bell.mp3" id="audioT" preload="auto"></audio>
    <script>
        let previousIds = new Set(); // Track IDs of current notifications
        let initialized = false;

        function loadNotifications() {
            setInterval(function () {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var response = JSON.parse(this.responseText);
                        let audioT = document.getElementById("audioT");

                        var notifCountElement = document.getElementById("notifCount");
                        var notifTitleCountElement = document.getElementById("notifTitleCount");

                        if (response.total > 0) {
                            notifCountElement.innerText = response.total;
                            notifTitleCountElement.innerText = response.total;
                            notifCountElement.style.display = "inline-block";

                            // Compare current IDs with previous
                            let currentIds = new Set(response.notifications.map(n => n.id));
                            let isNewNotification = false;

                            if (initialized) {
                                let isNewNotification = false;

                                for (let id of currentIds) {
                                    if (!previousIds.has(id)) {
                                        isNewNotification = true;
                                        break;
                                    }
                                }

                                if (isNewNotification) {
                                    audioT.play().catch(err => {
                                        console.error("Audio play error:", err);
                                    });
                                }
                            } else {
                                initialized = true;
                            }

                            // Update previousIds to current
                            previousIds = currentIds;

                        } else {
                            notifCountElement.style.display = "none";
                            notifTitleCountElement.innerText = "";
                            previousIds.clear(); // Reset when no notifications
                        }

                        // Update dropdown
                        var notifList = document.getElementById("notifDropdownList");
                        notifList.innerHTML = "";

                        if (response.notifications.length > 0) {
                            response.notifications.forEach(function (notif) {
                                var notifItem = document.createElement("a");
                                notifItem.classList.add("ticketNo");
                                notifItem.href = `view_ticket.php?id=${notif.id}`;
                                notifItem.dataset.id = notif.id;

                                notifItem.innerHTML = `
                                <div class="notif-icon notif-primary">
                                    <i class="fa fa-ticket-alt"></i>
                                </div>
                                <div class="notif-content">
                                    <span class="block">${notif.ticket_no} - ${notif.task_description}</span>
                                    <span class="time">${notif.time}</span>
                                </div>
                            `;

                                notifItem.addEventListener("click", function (e) {
                                    e.preventDefault();
                                    markNotificationAsRead(notif.id, notifItem.href);
                                });

                                notifList.appendChild(notifItem);
                            });
                        } else {
                            notifList.innerHTML = '<div class="text-center">No new notifications</div>';
                        }
                    }
                };
                xhttp.open("GET", "fetch_notification.php", true);
                xhttp.send();
            }, 1000);
        }

        function markNotificationAsRead(id, redirectUrl) {
            var xhttp = new XMLHttpRequest();
            xhttp.open("POST", "update_notification.php", true);
            xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        window.location.href = redirectUrl;
                    } else {
                        alert("Failed to update notification.");
                    }
                }
            };
            xhttp.send("id=" + id);
        }

        loadNotifications();
    </script>

    <!-- Data Table -->
    <script>
        $(document).ready(function () {
            $('#basic-datatables tfoot tr').empty();
            $('#basic-datatables thead th').each(function (index) {
                var title = $(this).text();

                // if (index === 0) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search ' + title + '"  /></td>');
                // }
                // if (index === 1) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search ' + title + '"  list="NameSuggestion"  /></td>');
                // } else if (index === 2) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="date"  /></td>');
                // } else if (index === 3) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="date"   /></td>');
                // } else if (index === 4) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search ' + title + '"  list="PrioritySuggestion"  /></td>');
                // } else if (index === 5) {
                //     /*      $('#basic-datatables tfoot tr').append('<td><input type="text" placeholder="Search ' + title + '"  list="statusSuggestion"  /></td>'); */
                //     //FOR MULTIPLE-SELECT
                //     // Create the select element
                //     const select = $('<select>', {
                //         class: 'status-select',
                //         multiple: 'multiple',
                //         style: 'width: 100%;'
                //     });
                //     <
                //     foreach ($statusOptions as $status) {
                //         echo 'select.append($("<option>", { value: "' . $status . '", text: "' . $status . '" }));';
                //     }
                //     ?>
                //     $('#basic-datatables tfoot tr').append($('<td>').append(select));
                // } else if (index === 6) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search Concern" list="TaskDescriptionSuggestion" /></td>');
                // } else if (index === 7) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search Concern" list="ConcernSuggestion" /></td>');
                // } else if (index === 8) {
                //     $('#basic-datatables tfoot tr').append('<td><input id="search" type="text" placeholder="Search Department" list="DepartmentSuggestion" /></td>');
                // } else {

                // }
            });


            $('#basic-datatables tfoot input').on('keyup change', function () {
                var columnIndex = $(this).closest('td').index();
                table.column(columnIndex).search(this.value).draw();
            });



            // Check if the table is already initialized
            if (!$.fn.dataTable.isDataTable('#basic-datatables')) {
                var table = $('#basic-datatables').DataTable({
                    dom: '<"dt-buttons"Bf><"clear">lirtp',
                    "order": [],
                    "autoWidth": false,
                    columnDefs: [
                        { width: 'auto', targets: '_all' }
                    ],
                    buttons: [
                        {
                            extend: 'colvis',
                            text: '<i class="fas fa-columns" title="Sort" style="font-size:20px;"></i>'
                        },
                        {
                            extend: 'copyHtml5',
                            text: '<i class="far fa-copy" title="Copy" style="font-size:20px;"></i>'
                        },
                        {
                            extend: 'csvHtml5',
                            text: '<i class="far fa-file-alt" title="CSV" style="color:#11734B; font-size:20px;"></i>',
                            exportOptions: {
                                columns: ':not(:eq(5))' // Excludes column index 4 (zero-based index, so column 5 in a human count)
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="far fa-file-pdf" title="PDF" style="color:#B10202; font-size:20px;"></i>',
                            exportOptions: {
                                columns: ':not(:eq(5))' // Excludes column index 4 (zero-based index, so column 5 in a human count)
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print" title="Print" style="color:#B10202; font-size:20px;"></i>',
                            exportOptions: {
                                columns: ':not(:eq(5))' // Excludes column index 4 (zero-based index, so column 5 in a human count)
                            }
                        },
                        {
                            text: '<i class="fas fa-plus" title="Add" style="font-size:20px;"></i>',
                            className: 'custom-button',
                            attr: { title: 'Add', onclick: 'addBtn()' },
                            id: 'addRowBtn',
                            action: function () {
                                // window.open('add_task.php', '_blank'); // Open new tab


                            }
                        }
                    ],
                    initComplete: function (settings, json) {
                        var footer = $("#basic-datatables tfoot tr");
                        $("#basic-datatables thead").append(footer);

                        // Initialize Select2 for the status column
                        $('.status-select').select2({
                            placeholder: "Filter by Status"
                        }).on('change', function () {
                            var selectedStatuses = $(this).val();
                            if (selectedStatuses) {
                                table.column(5).search(selectedStatuses.join('|'), true, false).draw();
                            } else {
                                table.column(5).search('').draw();
                            }
                        });
                    }
                });

                $('#filterDate').on('click', function () {
                    var startDate = $('#startDate').val();
                    var endDate = $('#endDate').val();

                    $.fn.dataTable.ext.search.push(
                        function (settings, data, dataIndex) {
                            var min = new Date(startDate).getTime();
                            var max = new Date(endDate).getTime();
                            var date = new Date(data[3]).getTime(); // use data for the date column

                            if ((isNaN(min) && isNaN(max)) ||
                                (isNaN(min) && date <= max) ||
                                (min <= date && isNaN(max)) ||
                                (min <= date && date <= max)) {
                                return true;
                            }
                            return false;
                        }
                    );
                    table.draw();
                    $.fn.dataTable.ext.search.pop();
                });

                $('#basic-datatables tfoot input').on('keyup change', function () {
                    var columnIndex = $(this).closest('td').index();
                    table.column(columnIndex).search(this.value).draw();
                });
            }
        });
    </script>

    <!-- Request to edit -->
    <script>
        $(document).ready(function () {
            $('#basic-datatables').on('click', '.req-btn', function (event) {
                var id = $(this).data('id');
                var ticketNo = $(this).data('ticketno');

                swal({
                    title: "Request Ticket Edit?",
                    text: "Are you sure you want to request an edit?",
                    icon: "warning",
                    content: {
                        element: "input",
                        attributes: {
                            placeholder: "Type your message here...",
                            type: "text",
                        },
                    },
                    buttons: {
                        cancel: {
                            text: "No",
                            className: 'btn btn-danger',
                            visible: true,
                            closeModal: true
                        },
                        confirm: {
                            text: "Yes",
                            className: 'btn btn-success',
                            closeModal: true
                        }
                    },
                    closeOnClickOutside: false
                }).then((value) => {
                    if (value) { // Ensure value is not null
                        requestEdit(id, ticketNo, value);
                    }
                });
            });

            function requestEdit(id, ticketNo, message) {
                $.ajax({
                    url: "requestEdit.php",
                    type: "POST",
                    data: {
                        id: id,
                        ticketNo: ticketNo,
                        message: message
                    },
                    success: function (response) {
                        console.log("AJAX Success:", response);
                        swal({
                            title: "Success",
                            text: "Your request was sent to the supervisor",
                            icon: "success",
                            buttons: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = 'activity_logs.php';
                        });

                        window.removeEventListener("beforeunload", beforeUnloadWarning);

                        setTimeout(function () {
                            window.close();
                        }, 3000);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", error);
                        swal({
                            title: "Error",
                            text: "Error requesting edit. Check console.",
                            icon: "error"
                        });
                    }
                });
            }
        });
    </script>

    <!-- View data modal -->
    <script>
        $('#basic-datatables').on('click', '.view-btn', function (event) {
            var id = $(this).data('id');
            var ticketNo = $(this).data('ticketno');
            $('#ViewModal').modal('show');
            // alert(ticketNo);


            $.ajax({
                url: "view_data.php",
                data: {
                    id: id
                },
                type: 'post',
                success: function (data) {

                    var json = JSON.parse(data);
                    document.getElementById('ViewNumber').textContent = ticketNo; // Now ticketNo is defined
                    $('#ViewID').val(id);
                    $('#ViewHDPersonnel').val(json.FK_userID);
                    $('#ViewRequestorName').val(json.requestor_name);
                    $('#ViewRequestorDepartment').val(json.requestor_department);
                    $('#ViewDateTimeAccomp').val(json.dateTimeAccomp);
                    $('#ViewDateAndTimeReq').val(json.created_at);
                    $('#ViewTaskDescription').val(json.task_description);
                    $('#ViewTaskCategory').val(json.task_category);
                    $('#ViewStatus').val(json.status);
                    $('#ViewConcern').val(json.concern);
                    $('#ViewPriority').val(json.priority);
                    $('#ViewAccompBy').val(json.handle_by);
                    $('#ViewPosition').val(json.position);
                    $('#ViewResolution').val(json.resolution);
                    $('#createdBy').val(json.created_by);

                }
            });
        });
    </script>

    <!-- Double Click for view the data at table -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tbody = document.querySelector('.hover-table tbody'); // Use tbody as the delegate

            if (tbody) {
                tbody.addEventListener('dblclick', (e) => {
                    const row = e.target.closest('tr');
                    if (row) {
                        const id = row.getAttribute('data-id');
                        const ticketNo = row.getAttribute('data-ticketNo');
                        if (id) {
                            openModal(id, ticketNo); // Pass both ID and ticketNo
                        }
                    }
                });
            }

            function openModal(id, ticketNo) {
                $('#ViewModal').modal('show');

                $.ajax({
                    url: "view_data.php",
                    data: { id: id },
                    type: 'post',
                    success: function (data) {
                        var json = JSON.parse(data);
                        document.getElementById('ViewNumber').textContent = ticketNo;
                        $('#ViewID').val(id);
                        $('#ViewHDPersonnel').val(json.FK_userID);
                        $('#ViewRequestorName').val(json.requestor_name);
                        $('#ViewRequestorDepartment').val(json.requestor_department);
                        $('#ViewDateTimeAccomp').val(json.dateTimeAccomp);
                        $('#ViewDateAndTimeReq').val(json.created_at);
                        $('#ViewTaskDescription').val(json.task_description);
                        $('#ViewTaskCategory').val(json.task_category);
                        $('#ViewStatus').val(json.status);
                        $('#ViewConcern').val(json.concern);
                        $('#ViewPriority').val(json.priority);
                        $('#ViewAccompBy').val(json.handle_by);
                        $('#ViewPosition').val(json.position);
                        $('#ViewResolution').val(json.resolution);
                        $('#createdBy').val(json.created_by);
                    }
                });
            }
        });
    </script>


    <!-- MEssage for overdue ticket -->
    <script>
        $(document).ready(function () {
            $(".message-btn").on("click", function () {
                let ticketNo = $(this).data("ticketno");
                let fkUser = $(this).data("fkuser");

                swal({
                    title: "Enter Your Message",
                    text: "Type your response below:",
                    content: {
                        element: "input",
                        attributes: {
                            placeholder: "Type your message here...",
                            type: "text",
                        },
                    },
                    buttons: ["Cancel", "Send"],
                }).then((message) => {
                    if (message) {

                        $.ajax({
                            url: "send_message.php",
                            method: "POST",
                            data: {
                                ticketNo: ticketNo, // Fix: Ensure ticketNo is included
                                fkUser: fkUser,
                                message: message,
                            },
                            dataType: "json",
                            success: function (response) {
                                if (response.status === "success") {
                                    swal("Success", response.message, "success");
                                } else {
                                    swal("Error", response.message, "error");
                                }
                            },
                            error: function () {
                                swal("Error", "Failed to send the message.", "error");
                            },
                        });
                    }
                });
            });
        });
    </script>
    <script>
        const filterStatus = document.getElementById('filterStatus');

        if (filterStatus) {
            filterStatus.addEventListener('change', function () {
                var filterValue = this.value;
                var rows = document.querySelectorAll('#filteringTable tr');

                rows.forEach(function (row) {
                    var remainingDaysCell = row.querySelector('td:nth-child(8)'); // 8th column is Remaining Days
                    if (remainingDaysCell) {
                        var remainingDaysText = remainingDaysCell.innerText.trim();

                        if (filterValue === 'all') {
                            row.style.display = '';
                        } else if (filterValue === 'due-today' && remainingDaysText === 'Due Today') {
                            row.style.display = '';
                        } else if (filterValue === 'overdue' && remainingDaysText.includes('Day') && !remainingDaysText.includes('Due Today')) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            });
        }
    </script>


</body>

</html>