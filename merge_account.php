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

// Fetch the latest ticket only if not already stored in session
if (!isset($_SESSION['ticket_no'])) {
    $sql = "SELECT ticket_no FROM activity_logs ORDER BY created_at DESC LIMIT 1;";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $latestTicket = $result->fetch_assoc();
        $_SESSION['ticket_no'] = $latestTicket['ticket_no'];
    } else {
        $_SESSION['ticket_no'] = "No Ticket";
    }
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
<!-- Getting HD Schedule -->
<?php
date_default_timezone_set('Asia/Manila');
$sqlSched = "SELECT time_in, time_out FROM tbl_users WHERE class = 'HD'";

$stmtSched = $conn->prepare($sqlSched);
$stmtSched->execute();
$resultShed = $stmtSched->get_result();


?>
<script>
    sessionStorage.clear();
</script>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Merge Account</title>
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
            background: rgba(29, 31, 32, 0.904) radial-gradient(rgba(255, 255, 255, 0.712) 10%, transparent 1%);
            background-size: 11px 11px;
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
                                        <div class="dropdown-title">
                                            You have <span id="notifTitleCount">0</span> new notification(s)
                                        </div>
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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Merge Account</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="home.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Patients Container -->
                <form id="AddForm">
                    <div id="patientsContainer">
                        <!-- Patient 1 -->
                        <div class="patient-section mb-4 border p-3 rounded bg-light">
                            <h5 class="mb-3" id="numpatient">1st Patient Account</h5>
                            <!-- Retain Account -->
                            <div class="account-box bg-white p-3 rounded mb-2">
                                <div class="account-header text-primary fw-bold mb-2">Retain Account</div>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" placeholder="Patient ID"
                                            name="retainID[]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" placeholder="First Name"
                                            name="retainFirstName[]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" placeholder="Middle Name"
                                            name="retainMiddleName[]" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" placeholder="Last Name"
                                            name="retainLastName[]" required>
                                    </div>
                                </div>
                            </div>

                            <div class="MultipleRecord">
                                <div class="row duplicate-row">
                                    <!-- Duplicate Accounts -->
                                    <div class="duplicateAccounts">
                                        <div class="account-box bg-white p-3 rounded mb-2">
                                            <div class="account-header text-danger fw-bold mb-2">Duplicate Account</div>
                                            <div class="row g-2">
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" placeholder="Patient ID"
                                                        name="duplicateID[0][]" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" placeholder="First Name"
                                                        name="duplicateFirstName[0][]" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" placeholder="Middle Name"
                                                        name="duplicateMiddleName[0][]" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" class="form-control" placeholder="Last Name"
                                                        name="duplicateLastName[0][]" required>
                                                </div>
                                                <div class="col-md-1 d-flex align-items-center justify-content-start">
                                                    <button type="button" class="btn btn-danger btn-sm remove-btn"
                                                        style="display: none;">
                                                        <i class="fas fa-times"></i> <!-- Font Awesome "X" icon -->
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="addDuplicate">+Add
                                    Duplicate Account</button>
                            </div>
                        </div>

                    </div>
                    <div class="text-end m-3">
                        <button type="submit" class="btn btn-outline-primary mb-4" id="saveBtn">Save</button>
                        <button type="button" class="btn btn-outline-secondary mb-4">Save and Create New</button>
                    </div>
                </form>



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
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

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
    <!-- <script src="assets/js/demo.js"></script> -->
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

    <datalist id="TaskTitleSuggestion">
        <?php
        $sql = "SELECT DISTINCT task_description FROM activity_logs GROUP BY task_description";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<option value="' . $row["task_description"] . '">';
            }
        }

        ?>
    </datalist>

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
    <script>
        function loadNotifications() {
            setInterval(function () {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var response = JSON.parse(this.responseText);

                        // Update notification count
                        var notifCountElement = document.getElementById("notifCount");
                        var notifTitleCountElement = document.getElementById("notifTitleCount");

                        if (notifCountElement && notifTitleCountElement) { // Ensure elements exist
                            if (response.total > 0) {
                                notifCountElement.innerText = response.total;
                                notifTitleCountElement.innerText = response.total;
                                notifCountElement.style.display = "inline-block";
                                notifTitleCountElement.style.display = "inline-block";

                            } else {
                                notifCountElement.style.display = "none";
                                notifTitleCountElement.innerText = ""; // Use "0" instead of an empty string for consistency
                            }
                        }

                        // Update notification dropdown
                        var notifList = document.getElementById("notifDropdownList");
                        notifList.innerHTML = "";

                        if (response.notifications.length > 0) {
                            response.notifications.forEach(function (notif) {
                                var notifItem = document.createElement("a");
                                notifItem.classList.add("ticketNo");
                                notifItem.href = `view_ticket.php?id=${notif.id}`;
                                notifItem.dataset.id = notif.id; // Store ticket ID

                                notifItem.innerHTML = `
                            <div class="notif-icon notif-primary">
                                <i class="fa fa-ticket-alt"></i>
                            </div>
                            <div class="notif-content">
                                <span class="block">${notif.ticket_no} - ${notif.task_description}</span>
                                <span class="time">${notif.time}</span>
                            </div>
                        `;

                                // Add event listener to mark as viewed
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

        // Function to mark notification as read
        function markNotificationAsRead(id, redirectUrl) {
            var xhttp = new XMLHttpRequest();
            xhttp.open("POST", "update_notification.php", true);
            xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        window.location.href = redirectUrl; // Redirect after updating
                    } else {
                        alert("Failed to update notification.");
                    }
                }
            };
            xhttp.send("id=" + id);
        }

        // Start fetching notifications
        loadNotifications();
    </script>



    <!-- Script to add new row -->
    <script>
        $(document).ready(function () {
            // Add new duplicate row

            $("#addDuplicate").click(function () {
                // Clone the last .duplicate-row and append to the .MultipleRecord container
                var newRow = $(".MultipleRecord .duplicate-row:last-child").clone();

                // Clear the values of the inputs in the cloned row
                newRow.find('input').val('');

                // Update the name attributes to avoid conflicts
                newRow.find('input').each(function () {
                    var name = $(this).attr('name');
                    // Update the array index dynamically (increment last index)
                    name = name.replace(/\[\d+\]\[\]/, '[' + ($(".MultipleRecord .duplicate-row").length) + '][]');
                    $(this).attr('name', name);
                });

                // Show the remove button for the newly added row
                newRow.find(".remove-btn").show();

                // Add the remove button functionality for the new row
                newRow.find(".remove-btn").click(function () {
                    var inputs = newRow.find('input');

                    // Check if any input in the row has data
                    var hasData = false;
                    inputs.each(function () {
                        if ($(this).val() !== "") {
                            hasData = true;
                        }
                    });

                    // If the row has data, show a SweetAlert (swal)
                    if (hasData) {
                        swal({
                            title: "Are you sure?",
                            text: "This row has data. Are you sure you want to remove it?",
                            icon: "warning",
                            buttons: true,
                            dangerMode: true,
                        }).then((willDelete) => {
                            if (willDelete) {
                                newRow.remove();  // Remove the entire row
                            }
                        });
                    } else {
                        newRow.remove();  // Remove the entire row if no data
                    }
                });

                // Append the new row to the MultipleRecord container
                $(".MultipleRecord").append(newRow);
            });

            // Prevent removal of the first row
            $(".remove-btn").first().hide();

            // Remove row functionality for existing rows
            $(".remove-btn").click(function () {
                // Prevent deletion of the first row
                if ($(this).closest(".duplicate-row").index() > 0) {
                    var row = $(this).closest(".duplicate-row");
                    var inputs = row.find('input');

                    // Check if any input in the row has data
                    var hasData = false;
                    inputs.each(function () {
                        if ($(this).val() !== "") {
                            hasData = true;
                        }
                    });

                    // If the row has data, show a SweetAlert (swal)
                    if (hasData) {
                        swal({
                            title: "Are you sure?",
                            text: "This row has data. Are you sure you want to remove it?",
                            icon: "warning",
                            buttons: true,
                            dangerMode: true,
                        }).then((willDelete) => {
                            if (willDelete) {
                                row.remove();  // Remove the entire row
                            }
                        });
                    } else {
                        row.remove();  // Remove the entire row if no data
                    }
                }
            });
        });
    </script>

    <!--Script for adding data to database  -->
    

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let isSubmitting = false; // Prevent multiple submissions

            document.getElementById('AddForm').addEventListener('submit', function (event) {
                event.preventDefault();
                if (isSubmitting) return; // Prevent duplicate submissions

                isSubmitting = true;

                // Get all the values of the input fields
                const retainID = document.querySelectorAll('input[name="retainID[]"]');
                const retainFirstName = document.querySelectorAll('input[name="retainFirstName[]"]');
                const retainMiddleName = document.querySelectorAll('input[name="retainMiddleName[]"]');
                const retainLastName = document.querySelectorAll('input[name="retainLastName[]"]');

                // Initialize data object to store input values
                let formData = "";
                let hasData = false; // Flag to check if there is any data entered
                let patientsData = []; // Array to hold patient data to send to the server

                // Loop through each patient's inputs and collect the data
                retainID.forEach((input, index) => {
                    if (input.value.trim() || retainFirstName[index].value.trim() || retainMiddleName[index].value.trim() || retainLastName[index].value.trim()) {
                        hasData = true; // Set flag if any field has data

                        // Add the patient data to the array for database insertion
                        patientsData.push({
                            p_ID: input.value.trim(),
                            p_FirstName: retainFirstName[index].value.trim(),
                            p_MiddleName: retainMiddleName[index].value.trim(),
                            p_LastName: retainLastName[index].value.trim(),
                            CreatedAt: new Date().toISOString(), // You can adjust the date format if needed
                            UpdatedAt: new Date().toISOString(),
                            encoded_by: 'Admin' // Set this as per your logic (for example, logged-in user)
                        });
                    }
                });

                // Handle duplicates
                const duplicateID = document.querySelectorAll('input[name^="duplicateID"]');
                const duplicateFirstName = document.querySelectorAll('input[name^="duplicateFirstName"]');
                const duplicateMiddleName = document.querySelectorAll('input[name^="duplicateMiddleName"]');
                const duplicateLastName = document.querySelectorAll('input[name^="duplicateLastName"]');

                duplicateID.forEach((input, index) => {
                    if (input.value.trim() || duplicateFirstName[index].value.trim() || duplicateMiddleName[index].value.trim() || duplicateLastName[index].value.trim()) {
                        hasData = true; // Set flag if any field has data

                        // Add the duplicate data to the array for database insertion
                        patientsData.push({
                            p_ID: input.value.trim(),
                            p_FirstName: duplicateFirstName[index].value.trim(),
                            p_MiddleName: duplicateMiddleName[index].value.trim(),
                            p_LastName: duplicateLastName[index].value.trim(),
                            CreatedAt: new Date().toISOString(), // You can adjust the date format if needed
                            UpdatedAt: new Date().toISOString(),
                            encoded_by: 'Admin' // Set this as per your logic (for example, logged-in user)
                        });
                    }
                });

                // Display the collected data in SweetAlert only if there is data
                if (hasData) {
                    swal({
                        title: "Form Data Submitted",
                        icon: "success",
                        buttons: "OK"
                    }).then(() => {
                        // Send data to the server using AJAX (jQuery)
                        $.ajax({
                            url: 'save_patient_data.php', // PHP script to handle data submission
                            method: 'POST',
                            dataType: 'json', // Expect a JSON response
                            contentType: 'application/json', // Send data as JSON
                            data: JSON.stringify({ patientsData }), // Convert the patientsData array to JSON
                            success: function (data) {
                                if (data.success) {
                                    swal("Success", "Data has been saved successfully!", "success");
                                } else {
                                    swal("Error", data.message || "There was an issue saving the data.", "error");
                                }
                            },
                            error: function (xhr, status, error) {
                                swal("Error", "Failed to submit data: " + error, "error");
                            },
                            complete: function () {
                                isSubmitting = false; // Reset the submitting state after the request is completed
                            }
                        });
                    });
                } else {
                    // Optionally, show a message if no data was entered
                    swal({
                        title: "No Data Entered",
                        text: "Please fill in at least one field before submitting.",
                        icon: "warning",
                        buttons: "OK"
                    }).then(() => {
                        isSubmitting = false; // Reset the submitting state
                    });
                }
            });
        });
    </script>


</body>

</html>