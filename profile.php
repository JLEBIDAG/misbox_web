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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Profile</title>
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Profile</h3>
                            <?php
                            $classMessages = [
                                'HD' => "You are Help Desk this Week",
                                'O1' => "You are Operation 1 this Week",
                                'O2' => "You are Operation 2 this Week",
                                'O3' => "You are PMS Technical this Week",
                                'O4' => "MIS Supervisor"
                            ];

                            if (isset($_SESSION['class']) && array_key_exists($_SESSION['class'], $classMessages)) {
                                echo "<h6 class='op-7 mb-2'>{$classMessages[$_SESSION['class']]}</h6>";
                            }
                            ?>

                        </div>
                        <div class="ms-md-auto py-2 py-md-0">


                        </div>
                    </div>

                    <!-- Toast Notification SQL -->
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
                    <div class="toast-container position-fixed top-0 end-0 p-3">
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

                    <?php
                    $sqlProfile = "SELECT u.*, d.*, r.*
                    FROM tbl_users u
                    INNER JOIN db_departmentgroups d ON u.FK_Departmentname = d.PK_DepartmentID
                    INNER JOIN db_userrole r ON u.FK_userRole = r.PK_userRole
                    WHERE u.Username = ? ";

                    $stmt = $conn->prepare($sqlProfile);
                    $stmt->bind_param("s", $_SESSION['Username']);
                    $stmt->execute();
                    $profileres = $stmt->get_result();
                    ?>

                    <?php while ($row = $profileres->fetch_assoc()): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card text-center border rounded shadow-sm p-3">
                                    <div class="card-body">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">

                                                <?php if (!empty($row['profile'])): ?>
                                                    <img src="<?= htmlspecialchars($row['profile']); ?>"
                                                        class="rounded-circle border" width="100" height="100">
                                                <?php else: ?>
                                                    <span
                                                        class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                                                        style="width: 100px; height: 100px; font-size: 2rem;">
                                                        <?= strtoupper(substr($row['FirstName'], 0, 1)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h5 class="mb-1 fw-bold"> <?= htmlspecialchars($row['FirstName']); ?> </h5>

                                            <?php if ($_SESSION['class'] == 'HD'): ?>
                                                <p class="text-muted mb-1"> Help Desk </p>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['class'] == 'O1'): ?>
                                                <p class="text-muted mb-1"> Operation 1 </p>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['class'] == 'O2'): ?>
                                                <p class="text-muted mb-1"> Operation 2 </p>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['class'] == 'O3'): ?>
                                                <p class="text-muted mb-1"> PMS Technical </p>
                                            <?php endif; ?>
                                            <?php if ($_SESSION['class'] == 'O4'): ?>
                                                <p class="text-muted mb-1">MIS Supervisor </p>
                                            <?php endif; ?>

                                            <p class="text-muted small"> <?= htmlspecialchars($row['Email']); ?></p>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="card border rounded shadow-sm p-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h4 class="card-title">Personal Information</h4>
                                            <a href="#" class="btn btn-outline-primary btn-sm" onclick="changePassword()">
                                                <i class="fas fa-key"></i> Change Password
                                            </a>

                                        </div>
                                        <hr>
                                        <?php
                                        $FullName = $row['FirstName'] . " " . $row['MiddleName'] . " " . $row['LastName'];
                                        ?>
                                        <p class="text-muted mb-1"><strong>Full Name:</strong>
                                            <?= htmlspecialchars($FullName); ?></p>
                                        <p class="text-muted mb-1"><strong>Department:</strong>
                                            <?= htmlspecialchars($row['Departmentname']); ?></p>
                                        <p class="text-muted mb-1"><strong>User Role:</strong>
                                            <?= htmlspecialchars($row['Type']); ?></p>
                                        <p class="text-muted mb-1"><strong>Username:</strong>
                                            <?= htmlspecialchars($row['Username']); ?></p>
                                        <p class="text-muted mb-1"><strong>Account Status:</strong>
                                            <?= htmlspecialchars($row['AccountStatus']); ?></p>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <h2 class="mb-4">Ticket Summary</h2>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>On Hold</th>
                                    <th>Completed</th>
                                    <th>Cancelled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025</td>
                                    <td>March</td>
                                    <td>5</td>
                                    <td>10</td>
                                    <td>2</td>
                                </tr>
                                <tr>
                                    <td>2025</td>
                                    <td>February</td>
                                    <td>3</td>
                                    <td>8</td>
                                    <td>1</td>
                                </tr>
                                <tr>
                                    <td>2025</td>
                                    <td>January</td>
                                    <td>4</td>
                                    <td>12</td>
                                    <td>3</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="row">

                        </div>
                    <?php endwhile; ?>
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
                    swal({
                        title: "Create an Event",
                        text: "Enter event title:",
                        content: {
                            element: "input",
                            attributes: {
                                placeholder: "Event Title",
                                type: "text",
                                className: "form-control"
                            }
                        },
                        buttons: {
                            cancel: {
                                text: "Cancel",
                                value: null,
                                visible: true,
                                className: "btn btn-danger",
                                closeModal: true
                            },
                            confirm: {
                                text: "Save",
                                value: true,
                                visible: true,
                                className: "btn btn-success"
                            }
                        },
                        closeOnClickOutside: false // Disable clicking outside the modal
                    }).then((eventTitle) => {
                        if (eventTitle) {
                            $.ajax({
                                url: 'save_event.php',
                                type: 'POST',
                                data: {
                                    title: eventTitle,
                                    start: start.format(), // Convert to MySQL format
                                    end: end.format(), // Optional: End date
                                    className: "fc-primary"
                                },
                                success: function (response) {
                                    if (response === "success") {
                                        $calendar.fullCalendar('refetchEvents'); // Reload events
                                        swal("Success!", "Event created successfully.", "success");
                                    } else {
                                        swal("Error!", "Failed to create event.", "error");
                                    }
                                }
                            });
                        }
                    });

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

    <!-- For up angle and reload the card body -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Expand/Collapse button
            document.querySelector(".toggle-card").addEventListener("click", function () {
                let cardBody = document.querySelector(".cards");
                let icon = this.querySelector("span");
                if (cardBody.style.display === "none") {
                    cardBody.style.display = "block";
                    icon.classList.replace("fa-angle-up", "fa-angle-down");
                } else {
                    cardBody.style.display = "none";
                    icon.classList.replace("fa-angle-down", "fa-angle-up");
                }
            });

            // Refresh button
            document.querySelector(".btn-refresh-card").addEventListener("click", function () {
                location.reload(); // Refresh the page
            });

            // Close button
            // document.querySelector(".close-card").addEventListener("click", function () {
            // 	document.querySelector(".card").style.display = "none"; // Hide the card
            // });
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

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Old Password</label>
                            <input type="password" class="form-control" id="oldPassword" name="oldPassword"
                                placeholder="Enter old password" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword"
                                placeholder="Enter new password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword"
                                placeholder="Confirm new password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePassword()">Change Password</button>
                </div>
            </div>
        </div>
    </div>




    <script>
        function changePassword() {
            var myModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            myModal.show();
        }

        // Check First if the old password was exist in database
        function savePassword() {
            const newPasswordval = document.getElementById('newPassword').value.trim();
            const confirmPasswordval = document.getElementById('confirmPassword').value.trim();
            const oldPasswordval = document.getElementById('oldPassword').value.trim();

            if (!newPasswordval || !confirmPasswordval || !oldPasswordval) {
                swal("Error", "All password fields are required!", "error");
                return;
            }

            if (newPasswordval !== confirmPasswordval) {
                swal("Error", "Passwords do not match!", "error");
                return;
            }

            $.ajax({
                url: "verify_password.php",
                type: "POST",
                data: { oldPassword: oldPasswordval, newPassword: newPasswordval },
                success: function (response) {
                    console.log("Server Response:", response); // Debugging
                    try {
                        let res = JSON.parse(response);
                        if (res.status === "success") {
                            swal("Success", res.message, "success");

                            setTimeout(function () {
                                window.location.href = "profile.php"; // Redirect after 2 seconds
                            }, 2000);
                        }
                        else {
                            swal("Error", res.message || "Something went wrong!", "error");
                        }
                    } catch (e) {
                        console.error("Invalid JSON Response", response);
                        swal("Error", "Invalid server response. Check console.", "error");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    swal("Error", "Error connecting to server. Please try again.", "error");
                }
            });

        }



    </script>



    <div
        style="left: -1000px; overflow: scroll; position: absolute; top: -1000px; border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        <div style="border: none; box-sizing: content-box; height: 200px; margin: 0px; padding: 0px; width: 200px;">
        </div>
    </div>
</body>

</html>