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
// Fetch notifications for the logged-in user
$sql = "SELECT a.*, u.Username, u.PK_userID
FROM activity_logs a
INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
WHERE a.handle_by = ? AND a.is_viewed = 0  AND a.status != 'Completed'
ORDER BY a.created_at DESC"; // Assuming there is a timestamp column

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['Username']);
$stmt->execute();
$result = $stmt->get_result();

$notifCount = $result->num_rows; // Count total notifications
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
?>

<?php


$handle_by = $_SESSION['Username']; // Get the logged-in username

$data = [];

// Fetch weekly, monthly, and total tickets
$query = "SELECT 
    (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)) AS total_tickets_week,
    (SELECT COUNT(*) FROM activity_logs WHERE handle_by = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())) AS total_tickets_month,
    (SELECT COUNT(*) FROM activity_logs) AS total_tickets";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $handle_by, $handle_by);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$total_tickets = max($data['total_tickets'], 1); // Avoid division by zero

$week_progress = ($data['total_tickets_week'] / $total_tickets) * 100;
$month_progress = ($data['total_tickets_month'] / $total_tickets) * 100;
$total_progress = 100; // Since total tickets in DB is the reference, it is always 100%

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
    <title>MIS Staff</title>
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
            <?php include 'sidebar.php'?>
        </div>
        <!-- End Sidebar -->
        <?php include 'maintenanceModal.php'?>

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

                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="buStton"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-bell"></i>
                                    <?php if ($notifCount > 0): ?>
                                        <span class="notification"><?= $notifCount ?></span>

                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu notif-box animated fadeIn" aria-labelledby="notifDropdown">

                                    <li>
                                        <div class="dropdown-title">You have <span><?= $notifCount ?></span> new
                                            notification(s)</div>
                                    </li>
                                    <li>
                                        <div class="notif-scroll scrollbar-outer">
                                            <div class="notif-center">
                                                <?php
                                                $count = 0; // Counter to track the displayed notifications
                                                if ($notifCount > 0) {
                                                    while ($row = $result->fetch_assoc()) {
                                                        if ($count >= 3)
                                                            break; // Stop loop after displaying 3 notifications
                                                        ?>
                                                        <a class="ticketNo"
                                                            href="view_ticket.php?id=<?= $row['PK_activityLogID'] ?>">
                                                            <div class="notif-icon notif-primary">
                                                                <i class="fa fa-ticket-alt"></i>
                                                            </div>
                                                            <div class="notif-content">
                                                                <span class="block">

                                                                    <input type="hidden" name="id" class="PK_activityLogID"
                                                                        value="<?= htmlspecialchars($row['PK_activityLogID']) ?>">

                                                                    <?= htmlspecialchars($row['ticket_no']) ?>
                                                                    <?= htmlspecialchars($row['task_description']) ?>
                                                                </span>

                                                                <span class="time">
                                                                    <?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?>
                                                                </span>
                                                            </div>
                                                        </a>
                                                        <?php
                                                        $count++; // Increment counter
                                                    }
                                                } else {
                                                    echo '<div class="text-center">No new notifications</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </li>

                                    <?php if ($notifCount > 3) { ?>
                                        <li>
                                            <a class="see-all" href="notifications.php">See all notifications<i
                                                    class="fa fa-angle-right"></i></a>
                                        </li>
                                    <?php } ?>

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


                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#scheduleModal">
                        Open Schedule Form
                    </button>

                    <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered"> <!-- Changed modal-lg to modal-xl -->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="scheduleModalLabel">Employee Weekly Schedule</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form>
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-center">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>EMPLOYEE NAME</th>
                                                        <th>MON</th>
                                                        <th>TUE</th>
                                                        <th>WED</th>
                                                        <th>THU</th>
                                                        <th>FRI</th>
                                                        <th>SAT</th>
                                                        <th>SUN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                placeholder="Employee Name">
                                                        </td>
                                                        <!-- Repeatable Days -->
                                                        <td>
                                                            <select class="form-select shift-select">
                                                                <option value="">Select Shift</option>
                                                                <option value="shift-1">8:00 AM - 4:00 AM</option>
                                                                <option value="shift-2">9:00 AM - 5:00 AM</option>
                                                                <option value="shift-3">10:00 AM - 6:00 AM</option>
                                                            </select>
                                                            
                                                        </td>
                                                        
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">Save Schedule</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div id="updateMessage" style="margin-top: 10px;"></div>
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

                        <!-- JavaScript to Show Toast Every 1 Minute -->
                        <script>
                            function showToast() {
                                var toastElement = new bootstrap.Toast(document.getElementById('dueTaskToast'));
                                toastElement.show();
                            }

                            // Show toast on page load if there are due tasks
                            window.onload = function () {
                                <?php if ($totalDueCount > 0) { ?>
                                    showToast();
                                <?php } ?>
                            };

                            // Show toast every 1 minute (60,000ms)
                            setInterval(function () {
                                showToast();
                            }, 60000);
                        </script>


                    </div>




                    <div class="row">
                        <div class="container mt-4">
                            <?php

                            $today = strtotime(date("Y-m-d"));
                            $yearNow = date("Y", $today);
                            $monthNow = date("F", $today);

                            // Find the start (Monday) and end (Sunday) of the current week
                            $startOfWeek = strtotime("last Monday", $today);
                            if (date('N', $today) == 1) { // If today is Monday, no need to go back
                                $startOfWeek = $today;
                            }
                            $endOfWeek = strtotime("+6 days", $startOfWeek);
                            // Define schedule meanings and corresponding styles
                            $scheduleStyles = [
                                0 => ['bg' => '#C6EFCE', 'text' => '#55A782'], // OFF
                                1 => ['bg' => '#333F4F', 'text' => '#FFFFFF'], // HD
                                2 => ['bg' => '#FFFFFF', 'text' => '#000000'], // O1
                                3 => ['bg' => '#ACB9CA', 'text' => '#000000'], // O2
                                4 => ['bg' => '#FFEB9C', 'text' => '#D3A855'], // O3
                                5 => ['bg' => '#EDEAE2', 'text' => '#000000'], // O4
                                6 => ['bg' => '#FFC7CE', 'text' => '#C25062'], // S
                                7 => ['bg' => '#FFC000', 'text' => '#000000'], // LV
                                8 => ['bg' => '#FFFFFF', 'text' => '#000000'], // BL
                                9 => ['bg' => '#FFFFFF', 'text' => '#000000'], // SH
                                10 => ['bg' => '#FFFFFF', 'text' => '#000000'] // RH
                            ];

                            $scheduleTypes = [
                                0 => "OFF",
                                1 => "HD",
                                2 => "O1",
                                3 => "O2",
                                4 => "O3",
                                5 => "O4",
                                6 => "S",
                                7 => "LV",
                                8 => "BL",
                                9 => "SH",
                                10 => "RH"
                            ];


                            // Fetch employee schedules
                            $sql = "SELECT e.*, u.* , t.*, t.timeSchedSatIn, t.timeSchedSatOut, t.timeSchedSunIn, t.timeSchedSunOut
                            FROM employe_schedules e 
                            INNER JOIN tbl_users u ON e.FK_userID = u.PK_userID
                            INNER JOIN employe_time t ON t.FK_userID = u.PK_userID";
                            $result = $conn->query($sql);


                            ?>

                            <div class="text-center mb-3">
                                <h2>ALLIED CARE EXPERTS MEDICAL CENTER - BAYPOINTE</h2>
                                <p>Block & Lot 1A and 1B Dewey Avenue, Subic Bay Freeport Zone 2222</p>
                                <p>Tel. No. (047) 250-6070, Fax (047) 250-0534</p>
                                <h4>Management Information System (MIS) Work Schedule -
                                    <span><?php echo $monthNow . " " . $yearNow; ?></span>
                                </h4>
                            </div>

                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th rowspan="2">ID No.</th>
                                        <th rowspan="2">Employee Name</th>
                                        <?php for ($i = 0; $i < 7; $i++): ?>
                                            <th><?= date('D', strtotime("+$i days", $startOfWeek)); ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <?php for ($i = 0; $i < 7; $i++): ?>
                                            <th><?= date('j', strtotime("+$i days", $startOfWeek)); ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $row['id_no']; ?></td>
                                                <td><?= $row['FirstName'] . " " . $row['MiddleName'] . " " . $row['LastName']; ?>
                                                </td>
                                                <?php
                                                $days = ['is_Monday', 'is_Tuesday', 'is_Wednesday', 'is_Thursday', 'is_Friday', 'is_Saturday', 'is_Sunday'];

                                                foreach ($days as $day) {
                                                    $scheduleType = $row[$day];
                                                    $bgColor = $scheduleStyles[$scheduleType]['bg'];
                                                    $textColor = $scheduleStyles[$scheduleType]['text'];

                                                    if ($day === 'is_Saturday' && $scheduleType == 0) { // If Saturday is OFF, display null
                                                        $displayText = "OFF";
                                                    } elseif ($day === 'is_Sunday' && $scheduleType == 0) { // If Sunday is OFF, display null
                                                        $displayText = "OFF";
                                                    } elseif ($day === 'is_Saturday' && $scheduleType != 0) { // If Saturday is NOT OFF, display time in/out
                                                        $timeIn = date("g:i A", strtotime($row['timeSchedSatIn']));
                                                        $timeOut = date("g:i A", strtotime($row['timeSchedSatOut']));
                                                        $displayText = "$timeIn - $timeOut";
                                                    } elseif ($day === 'is_Sunday' && $scheduleType != 0) { // If Sunday is NOT OFF, display time in/out
                                                        $timeIn = date("g:i A", strtotime($row['timeSchedSunIn']));
                                                        $timeOut = date("g:i A", strtotime($row['timeSchedSunOut']));
                                                        $displayText = "$timeIn - $timeOut";
                                                    } elseif (in_array($scheduleType, [0, 6, 7, 8, 9, 10])) { // If OFF, S, LV, BL, SH, RH, display type
                                                        $displayText = $scheduleTypes[$scheduleType];
                                                    } else { // For regular workdays
                                                        $timeIn = date("g:i A", strtotime($row['sched_timeIn']));
                                                        $timeOut = date("g:i A", strtotime($row['sched_timeOut']));
                                                        $displayText = "$timeIn - $timeOut";
                                                    }
                                                    ?>
                                                    <td style="background-color: <?= $bgColor; ?>; color: <?= $textColor; ?>;">
                                                        <?= $displayText; ?>
                                                    </td>
                                                <?php } ?>


                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No schedules found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <div class="mt-4">
                                <h5>Legend:</h5>
                                <ul>
                                    <li class="off-day">Rest Day</li>
                                    <li class="regular-holiday">Regular Holiday</li>
                                    <li class="special-holiday">Special Holiday</li>
                                    <li class="shift-1">Shift 1 (Helpdesk)</li>
                                    <li class="shift-2">Shift 2 (Operations)</li>
                                    <li class="shift-3">Shift 3 (Supervisor)</li>
                                </ul>
                            </div>
                        </div>

                    </div>








                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="http://www.themekita.com">
                                    ThemeKita
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
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <!-- <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script> -->

    <!-- Moment JS -->
    <script src="assets/js/plugin/moment/moment.min.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>&gt;
    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
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
    <script>
        $(document).ready(function () {
            $(".topbar-icon").on("click", function (e) {
            });
        });
    </script>
    <!-- Notif -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let ticketButtons = document.querySelectorAll(".ticketNo"); // Select by class

            ticketButtons.forEach(function (ticketButton) {
                ticketButton.addEventListener("click", function (event) {
                    event.preventDefault(); // Prevent default navigation behavior

                    let hiddenInput = this.querySelector(".PK_activityLogID"); // Find hidden input inside the clicked element

                    if (!hiddenInput || !hiddenInput.value.trim()) {
                        alert("Error: Activity Log ID is missing!");
                        return;
                    }

                    let hiddenValue = hiddenInput.value.trim();

                    $.ajax({
                        url: "mark_notification.php",
                        type: "POST",
                        data: { id: hiddenValue },
                        dataType: "json", // Expecting JSON response
                        success: function (response) {
                            console.log("Notification marked as read:", response);
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Error:", error);
                            alert("An error occurred while updating the ticket.");
                        }
                    });

                    // Redirect to ticket page after AJAX call
                    window.location.href = this.href;
                });
            });
        });


    </script>

    <!-- check notif -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Function to fetch notification count
            // function fetchNotifications() {
            $.ajax({
                url: "check_notifications.php",
                type: "GET",
                dataType: "json",
                success: function (response) {
                    let notifCount = response.count;
                    let notifElement = document.querySelector(".notification");

                    if (notifCount > 0) {
                        notifElement.textContent = notifCount;
                        notifElement.style.display = "inline"; // Show notification
                    } else {
                        // notifElement.style.display = "none"; // Hide if zero
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching notifications:", error);
                }
            });
            // }

            // Fetch notifications every 5 seconds (5000ms)
            // setInterval(fetchNotifications, 1000);

            // Initial fetch when page loads
            // fetchNotifications();
        });
    </script>






    <div
        style="left: -1000px; overflow: scroll; position: absolute; top: -1000px; border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        <div style="border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $("#updateScheduleBtn").click(function () {
                if (confirm("Are you sure you want to update the schedule for this week?")) {
                    $.ajax({
                        url: "update_schedule.php", // Calls the PHP script
                        type: "POST",
                        success: function (response) {
                            $("#updateMessage").html("<div class='alert alert-success'>" + response + "</div>");
                        },
                        error: function () {
                            $("#updateMessage").html("<div class='alert alert-danger'>Error updating schedule.</div>");
                        }
                    });
                }
            });
        });
    </script>


</body>

</html>