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

if (($_SESSION['class'] != 'O4')) {
    header('location:home.php');
    exit();
}


?>

<?php
// MULTIPLE-SELECT
$statusOptions = [];
$sql = "SELECT Status FROM tbl_activitylogs GROUP BY Status";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusOptions[] = $row["Status"];
    }
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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tickets</title>
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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Request Tickets</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="home.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                        </ul>
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
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title"></h4>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-success btn-xs d-flex align-items-center gap-2"
                                            id="showBtn">
                                            <i id="textFilter" class="fas fa-eye"></i> <span>Show Filter</span>
                                        </button>
                                        <button class="btn btn-primary btn-xs d-flex align-items-center gap-2"
                                            id="filterBtn">
                                            <i class="fas fa-filter"></i> <span>Apply Filter</span>
                                        </button>
                                    </div>
                                    <div class="row" id="filterContent" style="display: none">
                                        <!-- Filters Section (Left) -->
                                        <div class="col-md-6">
                                            <!-- Request Date Range -->
                                            <div class="row mb-2">
                                                <div class="col-md-5">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="requestCheck">
                                                        <label class="form-check-label" for="requestCheck">Request
                                                            Date Range</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="date" class="form-control form-control-sm"
                                                        id="requestDate1" disabled>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="date" class="form-control form-control-sm"
                                                        id="requestDate2" disabled>
                                                </div>
                                            </div>
                                            <!-- Department Filter -->
                                            <div class="row mb-2">
                                                <div class="col-md-5">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="departmentCheck">
                                                        <label class="form-check-label"
                                                            for="departmentCheck">Department</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-7">
                                                    <?php
                                                    include 'inc/conn.php';
                                                    $sql = "SELECT * FROM db_departmentgroups WHERE is_Active = 1";
                                                    $result = $conn->query($sql);
                                                    ?>
                                                    <select class="form-control form-control-sm" id="departmentSelect"
                                                        disabled>
                                                        <option value="">Select Department</option>
                                                        <?php
                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                echo '<option value="' . htmlspecialchars($row['PK_DepartmentID']) . '">' . htmlspecialchars($row['Departmentname']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-md-5">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="statusCheck">
                                                        <label class="form-check-label" for="statusCheck">Status</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-7">
                                                    <select class="form-control form-control-sm statusSelect" id="statusSelect"
                                                        disabled>
                                                        <option value="">Select Status</option>
                                                        <option value="Cancelled">Cancelled</option>
                                                        <option value="Completed">Completed</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="On Hold">On Hold</option>
                                                        <option value="Open">Open</option>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>


                                        <!-- Content Section (Right) -->
                                        <div class="col-md-6 d-flex align-items-center justify-content-center"
                                            style="display:none">
                                            <canvas id="horizontalBarChart" width="400" height="300"></canvas>
                                        </div>
                                    </div>

                                </div>


                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="basic-datatables"
                                            class="display table table-striped table-hover hover-table" width="100%">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Ticket No</th>
                                                    <th>Priority</th>
                                                    <th>Task Title</th>
                                                    <th>Message</th>
                                                    <th>Concern</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="filteringTable">
                                                <?php
                                                $sql = "SELECT r.*, a.*, u.*
                                                FROM tbl_requests r
                                                INNER JOIN activity_logs a ON r.FK_activityLogID = a.PK_activityLogID
                                                INNER JOIN tbl_users u ON r.FK_userID = u.PK_userID
                                                WHERE is_approved = 0";

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
                                                        ?>
                                                        <tr data-id="<?= $row["PK_activityLogID"] ?>"
                                                            data-ticketNo="<?= $row["ticket_no"] ?>" id="viewBtn"
                                                            style="cursor: pointer">
                                                            <td data-bs-toggle="tooltip" data-bs-placement="top"
                                                                title="<?= $row["status"] ?>"
                                                                style="background-color: <?= $bgColor ?>; width: 0px;"></td>
                                                            <td><?= $row["ticket_no"] ?></td>
                                                            <td><?= $row["priority"] ?></td>
                                                            <td><?= $row["task_description"] ?></td>
                                                            <td><?= $row["message"] ?></td>
                                                            <td style="width:50%">
                                                                <?php
                                                                $words = explode(' ', $row["concern"]);
                                                                $limited_text = implode(' ', array_slice($words, 0, 20));
                                                                echo htmlspecialchars($limited_text);
                                                                if (count($words) > 20)
                                                                    echo '...'; // Add ellipsis if truncated
                                                                ?>
                                                            </td>

                                                            <td>
                                                                <button type="button" class="btn btn-primary btn-xs view-btn"
                                                                    data-id="<?= $row["PK_activityLogID"] ?>"
                                                                    data-ticketNo="<?= $row["ticket_no"] ?>"
                                                                    style="margin:5px;">
                                                                    <span class="icon-eye"></span>
                                                                </button>
                                                                <button type="button" class="btn btn-success btn-xs req-btn"
                                                                    id="reqBtn" data-id="<?= $row["PK_activityLogID"] ?>"
                                                                    data-ticketNo="<?= $row["ticket_no"] ?>"
                                                                    style="margin:5px;">
                                                                    <span class="far fa-edit"></span>
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
                                                    <th>Message</th>
                                                    <th>Concern</th>
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
                            <button type="button" class="selected changeLogoHeaderColor" data-color="blue"></button>
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
                            <button type="button" class="changeTopBarColor" data-color="white"></button>
                            <br />
                            <button type="button" class="changeTopBarColor" data-color="dark2"></button>
                            <button type="button" class="selected changeTopBarColor" data-color="blue2"></button>
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
                            <button type="button" class="selected changeSideBarColor" data-color="white"></button>
                            <button type="button" class="changeSideBarColor" data-color="dark"></button>
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
    <!-- <script src="assets/js/core/popper.min.js"></script> -->
    <!-- <script src="assets/js/core/bootstrap.min.js"></script> -->

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script> <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Chart JS -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>


    <!-- jQuery Sparkline -->
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>


    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>


    <!-- jQuery Vector Maps -->
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>
    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo2.js"></script>

    <script src='https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>


    <!-- jQuery -->
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Buttons extension JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <!-- Select2 JS (for multi-select dropdowns) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


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
                            text: '<i class="far fa-file-alt" title="CSV" style="color:#11734B; font-size:20px;"></i>'
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="far fa-file-pdf" title="PDF" style="color:#B10202; font-size:20px;"></i>'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print" title="Print" style="color:#B10202; font-size:20px;"></i>'
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


    <script>
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

        function addBtn() {

            let newTabWindow = window.open('add_task.php', '_blank'); // Open task window
        }

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
                    // console.error("Error fetching notifications:", error);
                }
            });
            // }

            // Fetch notifications every 5 seconds (5000ms)
            // setInterval(fetchNotifications, 1000);

            // // Initial fetch when page loads
            // fetchNotifications();
        });
    </script>


    <!-- CheckBox -->
    <script>

        document.getElementById("requestCheck").addEventListener("change", function () {
            let isChecked = this.checked;
            document.getElementById("requestDate1").disabled = !isChecked;
            document.getElementById("requestDate2").disabled = !isChecked;
        });



        document.getElementById("departmentCheck").addEventListener("change", function () {
            document.getElementById("departmentSelect").disabled = !this.checked;
        });

        document.getElementById("statusCheck").addEventListener("change", function () {
            document.getElementById("statusSelect").disabled = !this.checked;
        });

    </script>

    <!-- <script>
        $(document).ready(function () {
            $("#filterBtn").click(function () {
                let requestDate1val = $("#requestDate1").val();
                let requestDate2val = $("#requestDate2").val();


                let departmentSelectval = $("#departmentSelect").val();
                let statusSelectval = $("#statusSelect").val();



                // Convert strings to Date objects
                let date1 = new Date(requestDate1val);
                let date2 = new Date(requestDate2val);

                // Validate date range
                if (date1 > date2) {
                    swal({
                        icon: 'warning',
                        text: 'Invalid Date Range, "Date To" should not be earlier than "Date From".',
                        title: "Warning"
                    });
                    return; // Stop execution
                }



                // AJAX request
                $.ajax({
                    url: "request_ticketFilter.php",
                    type: "POST",
                    data: {
                        requestDate1: requestDate1val,
                        requestDate2: requestDate2val,
                        departmentSelect: departmentSelectval,
                        statusSelect: statusSelectval,
                    },
                    success: function (response) {
                        $("#filteringTable").html(response); // Update the table body
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
    </script> -->


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
                }
            });
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tbody = document.querySelector('.hover-table tbody'); // Use tbody as the delegate
            const modal = $('#dataModal');

            // Event delegation for new rows - listen to dblclick on any tr in tbody
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

            function openModal(id, ticketNo) {  // Accept ticketNo as a parameter
                $('#ViewModal').modal('show');

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

                    }
                });
            }
        });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <?php
    $sql = "SELECT * FROM activity_logs 
         
        ORDER BY 
            CASE 
                WHEN priority = 'Urgent' AND status != 'Completed' THEN 0
                WHEN priority = 'High' AND status != 'Completed' THEN 1
                WHEN priority = 'Medium' AND status != 'Completed' THEN 2
                WHEN priority = 'Low' AND status != 'Completed' THEN 3
                ELSE 4
            END, created_at DESC"; // Added secondary sorting
    
    $stmt = $conn->prepare($sql);

    $stmt->execute();
    $result = $stmt->get_result();

    $status_counts = ["Completed" => 0, "In Progress" => 0, "Open" => 0, "On Hold" => 0, "Cancelled" => 0];

    while ($row = $result->fetch_assoc()) {
        $status = $row['status'];
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        }
    }

    $json_labels = json_encode(array_keys($status_counts));
    $json_data = json_encode(array_values($status_counts));


    ?>


    <script>
        const labels = <?php echo $json_labels; ?>;
        const dataValues = <?php echo $json_data; ?>;

        const data = {
            labels: labels,
            datasets: [{
                axis: 'y',
                label: 'Ticket Status',
                data: dataValues,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 205, 86, 0.2)',
                    'rgba(201, 203, 207, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgb(75, 192, 192)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(201, 203, 207)',
                    'rgb(255, 99, 132)'
                ],
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',
            data: data,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        position: 'top',
                        reverse: true
                    },
                    y: {
                        position: 'right'
                    }
                }
            }
        };

        const ctx = document.getElementById('horizontalBarChart').getContext('2d');
        new Chart(ctx, config);
    </script>

    <!-- <script>
    setInterval(function() {
        location.reload();
    }, 5000); // 1000ms = 1 second
</script> -->
    <!-- <script>
    setTimeout(function() {
        location.reload();
    }, 5000); // Reloads the page every 5000 milliseconds (5 seconds)
</script> -->

    <script>
        document.getElementById("showBtn").addEventListener("click", function () {
            let textFilter = document.getElementById("textFilter");
            let filterSpan = this.querySelector("span"); // Select the <span> inside the button
            let filterContent = document.getElementById("filterContent");

            if (this.id === "showBtn") {
                // Show the content
                filterContent.style.removeProperty("display"); // Remove 'display: none'
                textFilter.className = "fas fa-eye-slash"; // Change icon
                filterSpan.textContent = "Hide Filter"; // Change text
                this.id = "hideBtn"; // Change button ID
            } else {
                // Hide the content
                filterContent.style.display = "none"; // Hide filter
                textFilter.className = "fas fa-eye"; // Change icon back
                filterSpan.textContent = "Show Filter"; // Change text back
                this.id = "showBtn"; // Revert button ID
            }
        });

    </script>

    <!-- update request -->
    <!-- <script>
        $(document).ready(function () {
            $('#basic-datatables').on('click', '.req-btn', function (event) {
                event.preventDefault(); // Prevent default behavior

                var id = $(this).data('id'); // Get the ID from the clicked button
                var ticketNo = $(this).data('ticketno'); // Get the ticket number from the clicked button

                swal({
                    title: "Approve Request?",
                    text: "Are you sure you want to approve ticket No. " + ticketNo + "?",
                    icon: "warning",
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
                }).then((isConfirm) => {
                    if (isConfirm) {
                        requestEdit(id, ticketNo);
                    }
                });
            });

            function requestEdit(id, ticketNo) {
                $.ajax({
                    url: "approvedRequestEdit.php",
                    type: "POST",
                    data: {
                        id: id,
                        ticketNo: ticketNo
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
                            window.location.href = 'requests_ticket.php';
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
    </script> -->

<!-- 
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".req-btn").forEach(button => {
                button.addEventListener("click", function () {
                    let id = this.getAttribute("data-id");
                    let ticketNo = this.getAttribute("data-ticketNo");

                    swal({
                        title: "Approve Request?",
                        text: "Are you sure you want to approve ticket No. " + ticketNo + "?",
                        icon: "warning",
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
                    }).then((isConfirm) => {
                        if (isConfirm) {
                            $.ajax({
                                url: "approvedRequestEdit.php",
                                type: "POST",
                                data: {
                                    id: id,
                                    ticketNo: ticketNo
                                },
                                dataType: "json",
                                success: function (response) {
                                    if (response.status === "success") {
                                        swal({
                                            title: "Success",
                                            text: response.message,
                                            icon: "success",
                                            buttons: false,
                                            timer: 2000
                                        }).then(() => {
                                            window.location.href = 'requests_ticket.php';
                                        });
                                    } else {
                                        swal({
                                            title: "Error",
                                            text: response.message,
                                            icon: "error"
                                        });
                                    }
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
                });
            });
        });
    </script> -->

    <script type="text/javascript">
        $(document).ready(function () {
            $('#departmentSelect').on('change', function () {
                //var value = $(this).val();
                var value = 43; 
               //alert(value);
                $.ajax({
                   url:"request_ticketFilter.php",
                   type: "POST",
                   data: 'request=' + value,
                   beforeSend:function(){
                    $(".table-responsive").html("<span>Working....</span>");
                   },
                   success:function(data){
                    $(".table-responsive").html(data);
                   }
                });
            }); 
        });
    </script>

    <!-- <script>
        let selectMenu = document.querySelector("#statusSelect");
        
        let container = document.querySelector(".product-wrapper");

        selectMenu.addEventListener("change", function() {
            let categoryName = this.value;
           // heading.innerHTML = this[this.selectedIndex].text;

            let http = new XMLHttpRequest();

            http.open('POST', "script.php");
            http.setRequestHeader("content-type", "application/x-www-form-urlencoded");
            http.send("category="+categoryName);

        })


    </script> -->

</body>

</html>