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
$result1 = $stmt->get_result();

$notifCount = $result1->num_rows; // Count total notifications

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
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        <li class="nav-item active">
                            <a href="home.php">
                                <i class="fas fa-home"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-section">
                            <span class="sidebar-mini-icon">
                                <i class="fa fa-ellipsis-h"></i>
                            </span>
                            <h4 class="text-section">Components</h4>
                        </li>

                        <?php
                        if ($_SESSION['PK_userID'] == $urow['PK_userID']) {
                            if ($_SESSION['FK_Departmentname'] == 39) {
                                echo "<li class='nav-item'>
								<a href='activity_logs.php' class='d-flex align-items-center text-decoration-none'>
									<i class='fas fa-file me-2'></i>
									<p class='mb-0'>Activity Logs</p>";

                                if ($notifCount > 0) {
                                    echo "<span class='badge bg-danger ms-6'>" . $notifCount . "</span>";
                                }

                                echo "   </a>
      							</li>";
                                echo "
									<li class='nav-item'>
										<a href='#sidebarLayouts'>
											<i class='fas fa-database'></i>
											<p>BizBox Backup</p>

										</a>

									</li>
									<li class='nav-item'>
										<a href='#forms'>
											<i class='fas fa-file-alt'></i>
											<p>Data Encoding</p>

										</a>

									</li>
									<li class='nav-item'>
										<a href='#tables'>
											<i class='fas fa-user-plus'></i>
											<p>Merge Account</p>

										</a>

									</li>
									<li class='nav-item'>
										<a href='#maps'>
											<i class='fas fa-pen-alt'></i>
											<p>Price Amend</p>

										</a>

									</li>
									<li class='nav-item'>
										<a href='#charts'>
											<i class='fas fa-thermometer-half'></i>
											<p>Server Temp</p>
										</a>

									</li>
									<li class='nav-item'>
										<a href='widgets.html'>
											<i class='fas fa-user'></i>
											<p>User</p>
											<!-- <span class='badge badge-success'>4</span> -->
										</a>
									</li>";
                            }
                            if ($_SESSION['Username'] == 'LLENDEZ') {
                                echo "<li class='nav-section'>
                                        <span class='sidebar-mini-icon'>
                                            <i class='fa fa-ellipsis-h'></i>
                                        </span>
                                        <h4 class='text-section'>MIS Dashboard</h4>
                                    </li>
                                    
                                    <li class='nav-item'>
										<a href='mis.php'>
											<i class='icon-user'></i>
											<p>MIS Staff</p>
											<!-- <span class='badge badge-success'>4</span> -->
										</a>
									</li> 
                                <li class='nav-item'>
                                    <a href='requests_ticket.php'>
                                        <i class='fas fa-ticket-alt'></i>
                                        <p>Request Ticket</p>
                                        <!-- <span class='badge badge-success'>4</span> -->
                                    </a>
                                </li>
                                
                                <li class='nav-item'>
                                    <a href='ticket.php'>
                                        <i class='fas fa-ticket-alt'></i>
                                        <p>Tickets</p>
                                        <!-- <span class='badge badge-success'>4</span> -->
                                    </a>
                                </li>";
                            }
                            if ($_SESSION['FK_Departmentname'] == 1003) {
                                echo
                                    "<li class='nav-item'>
										<a href='#tables'>
											<i class='fas fa-user-plus'></i>
											<p>Merge Account</p>

										</a>

									</li>";
                            }
                            if ($_SESSION['FK_Departmentname'] == 1032) {
                                echo
                                    "<li class='nav-item'>
										<a href='#forms'>
											<i class='fas fa-file-alt'></i>
											<p>Data Encoding</p>
										</a>
									</li>
									
									<li class='nav-item'>
										<a href='#maps'>
											<i class='fas fa-pen-alt'></i>
											<p>Price Amend</p>

										</a>

									</li>";
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->
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
                            <li class="nav-item topbar-icon dropdown hidden-caret d-flex d-lg-none">
                                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                                    aria-expanded="false" aria-haspopup="true">
                                    <i class="fa fa-search"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-search animated fadeIn">
                                    <form class="navbar-left navbar-form nav-search">
                                        <div class="input-group">
                                            <input type="text" placeholder="Search ..." class="form-control">
                                        </div>
                                    </form>
                                </ul>
                            </li>

                            <li class="nav-item topbar-icon dropdown hidden-caret">
                                <a class="nav-link dropdown-toggle" href="#" id="notifDropdown" role="button"
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
                                                    while ($row = $result1->fetch_assoc()) {
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
                                                        $count++;
                                                    }
                                                } else {
                                                    echo '<div class="text-center">No new notifications</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <a class="see-all" href="javascript:void(0);">See all notifications<i
                                                class="fa fa-angle-right"></i> </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi,</span> <span class="fw-bold">Hizrian</span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg"><img src="assets/img/profile.jpg"
                                                        alt="image profile" class="avatar-img rounded"></div>
                                                <div class="u-text">
                                                    <h4>Hizrian</h4>
                                                    <p class="text-muted">hello@example.com</p><a href="profile.html"
                                                        class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">My Profile</a>
                                            <a class="dropdown-item" href="#">My Balance</a>
                                            <a class="dropdown-item" href="#">Inbox</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Logout</a>
                                        </li>
                                    </div>
                                </ul>
                            </li> -->
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
                            <h6 class="op-7 mb-2">Free Bootstrap 5 Admin Dashboard</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
                            <a href="#" class="btn btn-primary btn-round">Add Customer</a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body ">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Visitors</p>
                                                <h4 class="card-title">1,294</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Subscribers</p>
                                                <h4 class="card-title">1303</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-luggage-cart"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Sales</p>
                                                <h4 class="card-title">$ 1,345</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                                <i class="far fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Order</p>
                                                <h4 class="card-title">576</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">User Statistics</div>
                                        <div class="card-tools">
                                            <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                                                <span class="btn-label">
                                                    <i class="fa fa-pencil"></i>
                                                </span>
                                                Export
                                            </a>
                                            <a href="#" class="btn btn-label-info btn-round btn-sm">
                                                <span class="btn-label">
                                                    <i class="fa fa-print"></i>
                                                </span>
                                                Print
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 375px">
                                        <canvas id="statisticsChart"></canvas>
                                    </div>
                                    <div id="myChartLegend"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-primary card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Daily Sales</div>
                                        <div class="card-tools">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-label-light dropdown-toggle" type="button"
                                                    id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                    aria-haspopup="true" aria-expanded="false">
                                                    Export
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <a class="dropdown-item" href="#">Action</a>
                                                    <a class="dropdown-item" href="#">Another action</a>
                                                    <a class="dropdown-item" href="#">Something else here</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-category">March 25 - April 02</div>
                                </div>
                                <div class="card-body pb-0">
                                    <div class="mb-4 mt-2">
                                        <h1>$4,578.58</h1>
                                    </div>
                                    <div class="pull-in">
                                        <canvas id="dailySalesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="card card-round">
                                <div class="card-body pb-0">
                                    <div class="h1 fw-bold float-end text-primary">+5%</div>
                                    <h2 class="mb-2">17</h2>
                                    <p class="text-muted">Users online</p>
                                    <div class="pull-in sparkline-fix">
                                        <div id="lineChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row card-tools-still-right">
                                        <h4 class="card-title">Users Geolocation</h4>
                                        <div class="card-tools">
                                            <button class="btn btn-icon btn-link btn-primary btn-xs"><span
                                                    class="fa fa-angle-down"></span></button>
                                            <button
                                                class="btn btn-icon btn-link btn-primary btn-xs btn-refresh-card"><span
                                                    class="fa fa-sync-alt"></span></button>
                                            <button class="btn btn-icon btn-link btn-primary btn-xs"><span
                                                    class="fa fa-times"></span></button>
                                        </div>
                                    </div>
                                    <p class="card-category">
                                        Map of the distribution of users around the world</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-responsive table-hover table-sales">
                                                <table class="table">
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/id.png" alt="indonesia">
                                                                </div>
                                                            </td>
                                                            <td>Indonesia</td>
                                                            <td class="text-end">
                                                                2.320
                                                            </td>
                                                            <td class="text-end">
                                                                42.18%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/us.png"
                                                                        alt="united states">
                                                                </div>
                                                            </td>
                                                            <td>USA</td>
                                                            <td class="text-end">
                                                                240
                                                            </td>
                                                            <td class="text-end">
                                                                4.36%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/au.png" alt="australia">
                                                                </div>
                                                            </td>
                                                            <td>Australia</td>
                                                            <td class="text-end">
                                                                119
                                                            </td>
                                                            <td class="text-end">
                                                                2.16%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/ru.png" alt="russia">
                                                                </div>
                                                            </td>
                                                            <td>Russia</td>
                                                            <td class="text-end">
                                                                1.081
                                                            </td>
                                                            <td class="text-end">
                                                                19.65%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/cn.png" alt="china">
                                                                </div>
                                                            </td>
                                                            <td>China</td>
                                                            <td class="text-end">
                                                                1.100
                                                            </td>
                                                            <td class="text-end">
                                                                20%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="flag">
                                                                    <img src="assets/img/flags/br.png" alt="brazil">
                                                                </div>
                                                            </td>
                                                            <td>Brasil</td>
                                                            <td class="text-end">
                                                                640
                                                            </td>
                                                            <td class="text-end">
                                                                11.63%
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mapcontainer">
                                                <div id="world-map" class="w-100" style="height: 300px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-round">
                                <div class="card-body">
                                    <div class="card-head-row card-tools-still-right">
                                        <div class="card-title">New Customers</div>
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
                                        <div class="item-list">
                                            <div class="avatar">
                                                <img src="assets/img/jm_denis.jpg" alt="..."
                                                    class="avatar-img rounded-circle">
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Jimmy Denis</div>
                                                <div class="status">Graphic Designer</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                        <div class="item-list">
                                            <div class="avatar">
                                                <span class="avatar-title rounded-circle border border-white">CF</span>
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Chandra Felix</div>
                                                <div class="status">Sales Promotion</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                        <div class="item-list">
                                            <div class="avatar">
                                                <img src="assets/img/talha.jpg" alt="..."
                                                    class="avatar-img rounded-circle">
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Talha</div>
                                                <div class="status">Front End Designer</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                        <div class="item-list">
                                            <div class="avatar">
                                                <img src="assets/img/chadengle.jpg" alt="..."
                                                    class="avatar-img rounded-circle">
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Chad</div>
                                                <div class="status">CEO Zeleaf</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                        <div class="item-list">
                                            <div class="avatar">
                                                <span
                                                    class="avatar-title rounded-circle border border-white bg-primary">H</span>
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Hizrian</div>
                                                <div class="status">Web Designer</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                        <div class="item-list">
                                            <div class="avatar">
                                                <span
                                                    class="avatar-title rounded-circle border border-white bg-secondary">F</span>
                                            </div>
                                            <div class="info-user ms-3">
                                                <div class="username">Farrah</div>
                                                <div class="status">Marketing</div>
                                            </div>
                                            <button class="btn btn-icon btn-link op-8 me-1">
                                                <i class="far fa-envelope"></i>
                                            </button>
                                            <button class="btn btn-icon btn-link btn-danger op-8">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row card-tools-still-right">
                                        <div class="card-title">Transaction History</div>
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
                                    <div class="table-responsive">
                                        <!-- Projects table -->
                                        <table class="table align-items-center mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th scope="col">Payment Number</th>
                                                    <th scope="col" class="text-end">Date & Time</th>
                                                    <th scope="col" class="text-end">Amount</th>
                                                    <th scope="col" class="text-end">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        Payment from #10231
                                                    </th>
                                                    <td class="text-end">
                                                        Mar 19, 2020, 2.45pm
                                                    </td>
                                                    <td class="text-end">
                                                        $250.00
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success">Completed</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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

        <!-- Custom template | don't include it in your project! -->
        <div class="custom-template">
            <div class="title">Settings</div>
            <div class="custom-content">
                <div class="switcher">
                    <div class="switch-block">
                        <h4>Logo Header</h4>
                        <div class="btnSwitch">
                            <button type="button" class=" selected changeLogoHeaderColor" data-color="dark"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="blue"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="purple"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="light-blue"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="green"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="orange"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="red"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="white"></button>
                            <br />
                            <button type="button" class="changeLogoHeaderColor" data-color="dark2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="blue2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="purple2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="light-blue2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="green2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="orange2"></button>
                            <button type="button" class="changeLogoHeaderColor" data-color="red2"></button>
                        </div>
                    </div>
                    <div class="switch-block">
                        <h4>Navbar Header</h4>
                        <div class="btnSwitch">
                            <button type="button" class="changeTopBarColor" data-color="dark"></button>
                            <button type="button" class="changeTopBarColor" data-color="blue"></button>
                            <button type="button" class="changeTopBarColor" data-color="purple"></button>
                            <button type="button" class="changeTopBarColor" data-color="light-blue"></button>
                            <button type="button" class="changeTopBarColor" data-color="green"></button>
                            <button type="button" class="changeTopBarColor" data-color="orange"></button>
                            <button type="button" class="changeTopBarColor" data-color="red"></button>
                            <button type="button" class="selected changeTopBarColor" data-color="white"></button>
                            <br />
                            <button type="button" class="changeTopBarColor" data-color="dark2"></button>
                            <button type="button" class="changeTopBarColor" data-color="blue2"></button>
                            <button type="button" class="changeTopBarColor" data-color="purple2"></button>
                            <button type="button" class="changeTopBarColor" data-color="light-blue2"></button>
                            <button type="button" class="changeTopBarColor" data-color="green2"></button>
                            <button type="button" class="changeTopBarColor" data-color="orange2"></button>
                            <button type="button" class="changeTopBarColor" data-color="red2"></button>
                        </div>
                    </div>
                    <div class="switch-block">
                        <h4>Sidebar</h4>
                        <div class="btnSwitch">
                            <button type="button" class="changeSideBarColor" data-color="white"></button>
                            <button type="button" class="selected changeSideBarColor" data-color="dark"></button>
                            <button type="button" class="changeSideBarColor" data-color="dark2"></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="custom-toggle">
                <i class="icon-settings"></i>
            </div>
        </div>
        <!-- End Custom template -->

    </div>
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo.js"></script>
    <script src="assets/js/demo.js"></script>
    <script>
        $('#lineChart').sparkline([102, 109, 120, 99, 110, 105, 115], {
            type: 'line',
            height: '70',
            width: '100%',
            lineWidth: '2',
            lineColor: '#177dff',
            fillColor: 'rgba(23, 125, 255, 0.14)'
        });

        $('#lineChart2').sparkline([99, 125, 122, 105, 110, 124, 115], {
            type: 'line',
            height: '70',
            width: '100%',
            lineWidth: '2',
            lineColor: '#f3545d',
            fillColor: 'rgba(243, 84, 93, .14)'
        });

        $('#lineChart3').sparkline([105, 103, 123, 100, 95, 105, 115], {
            type: 'line',
            height: '70',
            width: '100%',
            lineWidth: '2',
            lineColor: '#ffa534',
            fillColor: 'rgba(255, 165, 52, .14)'
        });
    </script>
</body>

</html>