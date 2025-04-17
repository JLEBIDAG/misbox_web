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
<!-- <

$class = $_SESSION['class'];

if($class == 'HD'){
    // Fetch notifications for the logged-in user
    $sql = "SELECT a.*, u.Username, u.PK_userID
    FROM activity_logs a
    INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
    WHERE a.handle_by = ? OR a.handle_by IS NULL
    ORDER BY a.created_at DESC"; // Assuming there is a timestamp column

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['Username']);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifCount = $result->num_rows; // Count total notifications
}
else{
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
}


?> -->
<script>
    sessionStorage.clear();
</script>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Activity Logs</title>
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
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-secondary">
                        <li class="nav-item">
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

                                echo "<span class='badge bg-danger ms-6' id='notifTitleCount'></span>";

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
                                        <div class="dropdown-title">You have <span id="notifTitleCount">0</span> new
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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Activity Logs</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="home.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>


                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title" id="ticketDisplay">
                                        <?php
                                        // echo $_SESSION['ticket_no'];
                                        
                                        ?>
                                        Ticket No: ---
                                    </h4>
                                </div>
                                <div class="card-body">

                                    <form id="AddForm">
                                        <input type="hidden" name="class" id="class" value="<?= $_SESSION['class'] ?>">
                                        <input type="hidden" name="username" id="username"
                                            value="<?= $_SESSION['Username'] ?>">

                                        <span id="time_out" style="display: none;"></span>

                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Left Side: Requestor Department -->
                                                <div class="col-md-3">

                                                    <div class="form-group">
                                                        <input type="text" id="taskDescription" name="taskDescription"
                                                            list="TaskTitleSuggestion" class="form-control"
                                                            placeholder=" " required>
                                                        <label for="taskDescription" class="floating-label">Task
                                                            Title<b style="color:red">*</b></label>
                                                        <span class="small" id="taskDescriptionError"
                                                            style="color:red"></span>

                                                    </div>

                                                    <div class="form-group select-wrapper">
                                                        <select id="taskCategory" name="taskCategory"
                                                            class="form-control" required>
                                                            <option value="" disabled selected>Please Select</option>
                                                            <option value="Hardware">Hardware</option>
                                                            <option value="Software">Software</option>
                                                            <option value="Graphics">Graphics</option>
                                                            <option value="Others">Others</option>
                                                        </select>
                                                        <label for="taskCategory" class="floating-label">Task Category<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="taskCategoryError"
                                                            style="color:red"></span>

                                                    </div>

                                                    <div class="form-group select-wrapper">
                                                        <?php
                                                        include 'inc/conn.php';
                                                        $sql = "SELECT * FROM db_departmentgroups WHERE is_Active = 1";
                                                        $result = $conn->query($sql);
                                                        echo '<select name="AddRequestorDepartment" id="AddRequestorDepartment"
                                            class="form-control" required>';
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
                                                        <span class="small" id="AddRequestorDepartmentError"
                                                            style="color:red"></span>


                                                    </div>
                                                    <div class="form-group">
                                                        <input type="text" id="requestorName" class="form-control"
                                                            placeholder=" " required name="requestorName">
                                                        <label class="floating-label">Requestor Name<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="requestorNameError"
                                                            style="color:red"></span>

                                                    </div>
                                                    <div class="form-group">
                                                        <input type="text" id="position" class="form-control"
                                                            placeholder=" " required name="position">
                                                        <label class="floating-label">Position<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="positionError" style="color:red"></span>

                                                    </div>

                                                    <div class="form-group select-wrapper">
                                                        <select name="ViewPriority" id="ViewPriority"
                                                            class="form-control">
                                                            <option disabled selected value="">Please Select</option>
                                                            <option value="Urgent">🔴 Urgent</option>
                                                            <option value="High">🟠 High</option>
                                                            <option value="Medium">🟡 Medium</option>
                                                            <option value="Low">🟢 Low</option>
                                                        </select>
                                                        <label class="floating-label">Priority<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="ViewPriorityError"
                                                            style="color:red"></span>

                                                    </div>
                                                    <div class="form-group select-wrapper">
                                                        <select name="ViewStatus" id="ViewStatus" class="form-control">
                                                            <!-- <option disabled selected value="">Please Select</option> -->
                                                            <option value="Completed">✅ Completed</option>
                                                            <option value="In Progress">🔄 In Progress</option>
                                                            <option value="Cancelled">❌ Cancelled</option>
                                                            <option value="On Hold">⏸️ On Hold</option>
                                                            <!-- <option value="Overdue">⏳ Overdue</option> -->
                                                            <!-- <option value="Pending Review">📝 Pending Review</option> -->
                                                            <!-- <option value="Reopened">🔁 Reopened</option> -->
                                                            <option selected value="Open">📂 Open</option>
                                                        </select>
                                                        <label class="floating-label">Status<b
                                                                style="color:red"></b></label>
                                                        <span class="small" id="ViewStatusError"
                                                            style="color:red"></span>

                                                    </div>

                                                    <div class="form-group">
                                                        <?php
                                                        include 'inc/conn.php';
                                                        $sql = "SELECT Username, class FROM tbl_users ";
                                                        $result = $conn->query($sql);

                                                        if ($_SESSION['PK_userID'] == $_SESSION['PK_userID']) {
                                                            if ($urow['class'] == 'HD') {
                                                                echo '<select name="UpdateAccomBy" id="UpdateAccomBy" class="form-control" required>';
                                                                echo '<option disabled selected value="">Please Select</option>';


                                                                if ($result->num_rows > 0) {

                                                                    while ($row = $result->fetch_assoc()) {
                                                                        $username = $row['Username'];
                                                                        $class = $row['class'];
                                                                        if ($class == 'HD') {
                                                                            echo '<option value="' . $username . '">' . $username . '  (Me)</option>';
                                                                        } else {
                                                                            echo '<option value="' . $username . '">' . $username . '</option>';
                                                                        }

                                                                    }
                                                                }
                                                                echo '</select>';
                                                                echo '<label class="floating-label">Handled By<b style="color:red">*</b></label>';

                                                            }
                                                            if ($urow['class'] == NULL) {
                                                                echo "";
                                                            }

                                                        }
                                                        ?>
                                                        <span class="small" id="UpdateAccomByError"
                                                            style="color:red"></span>


                                                    </div>

                                                    <?php
                                                    include 'inc/conn.php';
                                                    $sql = "SELECT Username,class FROM tbl_users ";
                                                    $result = $conn->query($sql);

                                                    if ($_SESSION['PK_userID'] == $_SESSION['PK_userID']) {
                                                        if ($urow['class'] == 'HD') {
                                                            echo '
                                                                <div class="form-group select-wrapper">
                                                                <select name="AddIssue" id="AddIssue" class="form-control"
                                                                required>';
                                                            echo '<option disabled selected value="">Please Select</option>';
                                                            echo '<option value="Direct">Direct</option>
                                                            <option value="Email">Email</option>
                                                                <option value="Messenger">Messenger</option>
                                                                <option value="Mobile Call">Mobile Call</option>
                                                                <option value="Tel. (Local)">Tel. (Local)</option>
                                                                <option value="Tel. (Out)">Tel. (Out)</option>
                                                                <option value="Viber">Viber</option>
                                                                </select>
                                                                 <label class="floating-label">Issue reported through<b
                                                                style="color:red">*</b></label>
                                                                 </div>';

                                                        } else {
                                                            echo "";
                                                        }
                                                    }
                                                    ?>

                                                    <?php
                                                    include 'inc/conn.php';
                                                    $sql = "SELECT Username,class FROM tbl_users ";
                                                    $result = $conn->query($sql);

                                                    if ($_SESSION['PK_userID'] == $_SESSION['PK_userID']) {
                                                        if ($urow['class'] != 'HD') {
                                                            echo '
                                                                <div class="form-group select-wrapper">
                                                                <select name="AddSeverity" id="AddSeverity" class="form-control"
                                                                required>';
                                                            echo '<option disabled selected value="">Please Select</option>';
                                                            echo '<option value="Visit">Visit</option>
                                                            <option value="On Site">On Site</option>
                                                                <option value="Remote">Remote</option>
                                                                <option value="Call">Call</option>
                                                                </select>
                                                                 <label class="floating-label">Nature of Support<b
                                                                style="color:red">*</b></label>
                                                                 </div>';

                                                        } else {
                                                            echo '
                                                                <div class="form-group select-wrapper">
                                                                <select name="AddSeverity" id="AddSeverity" class="form-control">';
                                                            echo '<option disabled selected value="">Please Select</option>';
                                                            echo '<option value="Visit">Visit</option>
                                                            <option value="On Site">On Site</option>
                                                                <option value="Remote">Remote</option>
                                                                <option value="Call">Call</option>
                                                                </select>
                                                                 <label class="floating-label">Nature of Support<b
                                                                style="color:red">*</b></label>
                                                                 </div>';
                                                        }
                                                    }
                                                    ?>







                                                </div>

                                                <!-- Right Side: Other Fields -->
                                                <div class="col-md-9">




                                                    <div class="form-group">
                                                        <textarea id="concern" name="concern" class="form-control"
                                                            placeholder=" " rows="1" cols="50" required></textarea>
                                                        <label for="concern" class="floating-label"
                                                            style="top: 1px">Concern<b style="color:red">*</b></label>
                                                        <span class="small" id="concernError" style="color:red"></span>

                                                    </div>

                                                    <div class="form-group mt-3">
                                                        <textarea id="resolution" name="resolution" class="form-control"
                                                            placeholder=" " rows="17"></textarea>
                                                        <label for="resolution" class="floating-label"
                                                            style="top: 1px">Resolution<b style="color:red"></b></label>


                                                    </div>


                                                </div>
                                            </div>

                                        </div>


                                        <div class="d-flex justify-content-end gap-2 m-3">


                                            <button type="button" class="btn btn-secondary" id="closeBtn"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" id="saveBtn">Submit</button>
                                        </div>
                                    </form>
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



    <script>


    </script>
    <!-- Add data -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let isSubmitting = false; // Prevent multiple submissions

            document.getElementById("AddForm").addEventListener("submit", function (event) {
                event.preventDefault();
                if (isSubmitting) return; // Prevent duplicate submissions

                let form = this; // Reference to the form
                let classes = document.getElementById('class').value.trim();

                // Ensure the form has valid input before proceeding
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                HDSched(form, classes);
            });

            function saveTicket(form, isHandled) {
                isSubmitting = true; // Prevent multiple submissions

                let currentYear = new Date().getFullYear();
                let randomNumber = Math.floor(1000 + Math.random() * 9000);
                let ticket_no = `${currentYear}-${randomNumber}`;

                let formData = new FormData(form);
                formData.append('ticket_no', ticket_no);

                // If user clicked "No," send handle_by as null
                if (!isHandled) {
                    formData.append('handle_by', 'NULL');
                }

                $.ajax({
                    url: "dataAdd/save_ticket.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        console.log("AJAX Success:", response);

                        swal({
                            title: "Success",
                            text: "Ticket saved successfully!",
                            icon: "success",
                            buttons: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = 'activity_logs.php'; // Redirect to activity logs
                        });

                        window.removeEventListener("beforeunload", beforeUnloadWarning);
                        setTimeout(() => window.close(), 3000);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", error);
                        swal({
                            title: "Error",
                            text: "Error saving ticket. Check console.",
                            icon: "error"
                        });
                        isSubmitting = false; // Allow retry
                    }
                });
            }

            function saveHandleNul(form) {
                isSubmitting = true; // Prevent multiple submissions

                let currentYear = new Date().getFullYear();
                let randomNumber = Math.floor(1000 + Math.random() * 9000);
                let ticket_no = `${currentYear}-${randomNumber}`;

                let formData = new FormData(form);
                formData.append('ticket_no', ticket_no);
                formData.append('handle_by', 'NULL'); // Ensure handle_by is NULL

                $.ajax({
                    url: "dataAdd/save_ticket1.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        console.log("AJAX Success:", response);

                        swal({
                            title: "Success",
                            text: "Ticket saved successfully!",
                            icon: "success",
                            buttons: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = 'activity_logs.php';
                        });

                        window.removeEventListener("beforeunload", beforeUnloadWarning);
                        setTimeout(() => window.close(), 3000);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", error);
                        swal({
                            title: "Error",
                            text: "Error saving ticket. Check console.",
                            icon: "error"
                        });
                        isSubmitting = false; // Allow retry
                    }
                });
            }

            function HDSched(form, classes) {
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        var responses = JSON.parse(this.responseText);
                        var timeOutValue = responses.time_out; // Assuming "time_out" is returned from PHP
                        const now = new Date();
                        const currentTime = now.toLocaleTimeString();

                       
                        if (classes !== 'HD' && currentTime !== timeOutValue) {
                            saveTicket(form, true);
                            return;
                        }
                        if (classes !== 'HD' && currentTime >= timeOutValue) {

                            // Otherwise, ask for confirmation before proceeding
                            swal({
                                title: "Are you sure?",
                                text: "You want to get this ticket?",
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
                                    saveTicket(form, true);
                                } else {
                                    saveHandleNul(form);
                                }
                            });
                        }

                    }
                };
                xhttp.open("GET", "hd_schedule.php", true);
                xhttp.send();
            }
        });


        document.getElementById("closeBtn").addEventListener("click", function (event) {
            event.preventDefault(); // Prevent default behavior

            swal({
                title: "Are you sure?",
                text: "Any unsaved changes will be lost.",
                icon: "warning", // Displays an info icon
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
                    window.location.href = "activity_logs.php";
                }
            });
            window.removeEventListener("beforeunload", beforeUnloadWarning);

        });

        // Define the beforeunload function separately so we can remove it
        function beforeUnloadWarning(e) {
            e.returnValue = "Warning! Do not close, save first";
            return "Warning! Do not close, save first";
        }

        // Attach the beforeunload warning
        window.addEventListener("beforeunload", beforeUnloadWarning);
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

                        if (response.total > 0) {
                            notifCountElement.innerText = response.total;
                            notifTitleCountElement.innerText = response.total;
                            notifCountElement.style.display = "inline-block";
                        } else {
                            notifCountElement.style.display = "none";
                            notifTitleCountElement.innerText = "";
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


</body>

</html>