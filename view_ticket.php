<?php
include('inc/conn.php');

// // Ensure session security
// ini_set('session.cookie_secure', '1');  // Ensures cookie is sent only over HTTPS
// ini_set('session.cookie_httponly', '1');  // Ensures cookie is not accessible via JavaScript
// ini_set('session.use_only_cookies', '1');  // Ensures that session IDs are only passed via cookies

session_start();

// Regenerate session ID after login to prevent session fixation
session_regenerate_id(true);


// Check if the user is logged in
if (!isset($_SESSION['PK_userID']) || (trim($_SESSION['PK_userID']) == '')) {
    header('location:index.php');
    exit();
}

// Fetch the current user's details
$uquery = mysqli_query($conn, "SELECT u.*, d.* FROM `tbl_users` u INNER JOIN `db_departmentgroups` d ON u.FK_Departmentname = d.PK_DepartmentID WHERE PK_userID='" . $_SESSION['PK_userID'] . "'");
$urow = mysqli_fetch_assoc($uquery);

// Check if the ticket ID is set in the URL and is a valid number
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ticketID = (int) $_GET['id']; // Cast to integer for extra safety
    if ($ticketID <= 0) {
        // Invalid ticket ID, exit
        echo "Invalid ticket ID!";
        exit;
    }

    // Fetch ticket details based on the provided ID
    $sql = "SELECT a.*, u.Username 
            FROM activity_logs a
            INNER JOIN tbl_users u ON a.FK_userID = u.PK_userID
            WHERE a.PK_activityLogID = ?";

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticketID); // Bind the ticket ID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        echo "No ticket found!";
        exit;
    }
} else {
    echo "Invalid ticket ID!";
    exit;
}

// Make sure all output is properly sanitized for XSS
// echo htmlspecialchars($ticket['Username'], ENT_QUOTES, 'UTF-8');

date_default_timezone_set('Asia/Manila');
// Optional: Log the access for auditing purposes (if needed)
$logSql = "INSERT INTO logs (FK_userID, action, description, datetime) 
           VALUES (?, ?, ?, ?)";
$logStmt = $conn->prepare($logSql);
$action = "VIEW";
$description = "Ticket No " . $ticket['ticket_no'] . " viewed by " . $_SESSION['Username'];
$date = date('Y-m-d H:i:s');
$logStmt->bind_param("isss", $_SESSION['PK_userID'], $action, $description, $date);
$logStmt->execute();

// Ensure all session data is secure (never store sensitive information like passwords)
$_SESSION['ticket_data'] = $ticket; // Store only the ticket data (not the entire ticket)
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
                        <h3 class="fw-bold mb-3">Edit Status</h3>
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
                                <div class="card-header d-flex justify-content-between">
                                    <h4 class="card-title" id="ticketDisplay">
                                        Ticket No: <?= htmlspecialchars($ticket['ticket_no']) ?>
                                    </h4>

                                    <?php
                                    $ticketNo = $ticket['ticket_no'];
                                    $sql = "SELECT is_transfer FROM activity_logs WHERE ticket_no = '$ticketNo'"; // Fixed the SQL query
                                    
                                    // Execute the query
                                    $result = $conn->query($sql);
                                    $row = $result->fetch_assoc(); // Added missing semicolon
                                    
                                    ?>
                                    <?php if ($row['is_transfer'] === NULL): ?>
                                        <button class="btn btn-secondary" id="transfer" data-bs-toggle="modal"
                                            data-bs-target="#transferModal">
                                            <span class="btn-label">
                                                <i class="fas fa-reply"></i>
                                            </span>
                                            Transfer
                                        </button>
                                    <?php else: ?>
                                        <!-- No button because it is unassigne ticket(s) -->
                                    <?php endif; ?>




                                </div>
                                <div class="card-body">

                                    <form id="AddForm">
                                        <input type="hidden" name="PK_activityLogID"
                                            value="<?= htmlspecialchars($ticket['PK_activityLogID']) ?>">
                                        <input type="hidden" name="handle_by" id="handle_by"
                                            value="<?= htmlspecialchars($ticket['handle_by']) ?>">
                                        <input type="hidden" name="ticket_no"
                                            value="<?= htmlspecialchars($ticket['ticket_no']) ?>">
                                        <input type="hidden" name="dueDate"
                                            value="<?= htmlspecialchars($ticket['dueDate']) ?>">
                                        <div class="modal-body">
                                            <div class="row">

                                                <!-- Left Side: Requestor Department -->
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <input type="text" id="taskDescription" name="taskDescription"
                                                            class="form-control" placeholder=""
                                                            value="<?= htmlspecialchars($ticket['task_description']) ?>"
                                                            required>
                                                        <label for="taskDescription" class="floating-label">Task
                                                            Title<b style="color:red">*</b></label>
                                                        <span class="small" id="taskDescriptionError"
                                                            style="color:red"></span>

                                                    </div>

                                                    <div class="form-group select-wrapper">
                                                        <select id="taskCategory" name="taskCategory"
                                                            class="form-control" required>
                                                            <option value="" disabled selected></option>
                                                            <option value="Hardware"
                                                                <?= ($ticket['task_category'] == 'Hardware') ? 'selected' : '' ?>>Hardware</option>
                                                            <option value="Software"
                                                                <?= ($ticket['task_category'] == 'Software') ? 'selected' : '' ?>>Software</option>
                                                            <option value="Graphics"
                                                                <?= ($ticket['task_category'] == 'Graphics') ? 'selected' : '' ?>>Graphics</option>
                                                            <option value="Others"
                                                                <?= ($ticket['task_category'] == 'Others') ? 'selected' : '' ?>>Others</option>
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

                                                        echo '<select name="AddRequestorDepartment" id="AddRequestorDepartment" class="form-control" required>';
                                                        echo '<option disabled selected value="">Please Select</option>';

                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                $Departmentname = $row['Departmentname'];
                                                                $PK_DepartmentID = $row['PK_DepartmentID'];

                                                                // Check if this option should be selected
                                                                $selected = ($PK_DepartmentID == $ticket['requestor_department']) ? 'selected' : '';

                                                                echo '<option value="' . $PK_DepartmentID . '" ' . $selected . '>' . $Departmentname . '</option>';
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
                                                            placeholder=" " required name="requestorName"
                                                            value="<?= htmlspecialchars($ticket['requestor_name']) ?>">
                                                        <label class="floating-label">Requestor Name<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="requestorNameError"
                                                            style="color:red"></span>

                                                    </div>
                                                    <div class="form-group">
                                                        <input type="text" id="position" class="form-control"
                                                            placeholder=" " required name="position"
                                                            value="<?= htmlspecialchars($ticket['position']) ?>">
                                                        <label class="floating-label">Position<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="positionError" style="color:red"></span>

                                                    </div>

                                                    <div class="form-group select-wrapper">
                                                        <select name="ViewPriority" id="ViewPriority"
                                                            class="form-control">
                                                            <option disabled value="">Please Select</option>
                                                            <option value="Urgent" <?= ($ticket['priority'] == 'Urgent') ? 'selected' : '' ?>>🔴 Urgent</option>
                                                            <option value="High" <?= ($ticket['priority'] == 'High') ? 'selected' : '' ?>>🟠 High</option>
                                                            <option value="Medium" <?= ($ticket['priority'] == 'Medium') ? 'selected' : '' ?>>🟡 Medium</option>
                                                            <option value="Low" <?= ($ticket['priority'] == 'Low') ? 'selected' : '' ?>>🟢 Low</option>
                                                        </select>

                                                        <label class="floating-label">Priority<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="ViewPriorityError"
                                                            style="color:red"></span>

                                                    </div>
                                                    <div class="form-group select-wrapper">
                                                        <select name="ViewStatus" id="ViewStatus" class="form-control">
                                                            <option value="Completed"
                                                                <?= ($ticket['status'] == 'Completed') ? 'selected' : '' ?>>✅ Completed</option>
                                                            <option value="In Progress" <?= ($ticket['status'] == 'In Progress') ? 'selected' : '' ?>>🔄 In Progress</option>
                                                            <option value="Cancelled"
                                                                <?= ($ticket['status'] == 'Cancelled') ? 'selected' : '' ?>>❌ Cancelled</option>
                                                            <option value="On Hold" <?= ($ticket['status'] == 'On Hold') ? 'selected' : '' ?>>⏸️ On Hold</option>
                                                            <option value="Open" <?= ($ticket['status'] == 'Open') ? 'selected' : '' ?>>📂 Open</option>
                                                        </select>

                                                        <label class="floating-label">Status<b
                                                                style="color:red"></b></label>
                                                        <span class="small" id="ViewStatusError"
                                                            style="color:red"></span>

                                                    </div>

                                                    <div class="form-group">
                                                        <?php
                                                        include 'inc/conn.php';
                                                        $sql = "SELECT Username, class FROM tbl_users";
                                                        $result = $conn->query($sql);

                                                        if ($_SESSION['PK_userID'] == $_SESSION['PK_userID']) { // This condition seems redundant, consider reviewing
                                                            if ($urow['class'] == 'HD') {
                                                                echo '<select name="UpdateAccomBy" id="UpdateAccomBy" class="form-control" required>';
                                                                echo '<option value="" disabled selected>-- Select a user --</option>';

                                                                if ($result->num_rows > 0) {
                                                                    while ($row = $result->fetch_assoc()) {
                                                                        $username = $row['Username'];
                                                                        $class = $row['class'];
                                                                        $selected = ($ticket['handle_by'] == $username) ? 'selected' : ''; // Check if the user is the selected one
                                                        
                                                                        if ($class == 'HD') {
                                                                            echo '<option value="' . htmlspecialchars($username) . '" ' . $selected . '>' . htmlspecialchars($username) . ' (Me)</option>';
                                                                        } else {
                                                                            echo '<option value="' . htmlspecialchars($username) . '" ' . $selected . '>' . htmlspecialchars($username) . '</option>';
                                                                        }
                                                                    }
                                                                } else {
                                                                    echo '<option disabled>Choose handle</option>';
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
                                                        // if ($urow['class'] == 'HD') {
                                                        echo '
                                                                <div class="form-group select-wrapper">
                                                                <select name="AddSeverity" id="AddSeverity" class="form-control"
                                                                required>';
                                                        echo '<option disabled selected value="">Please Select</option>';
                                                        echo '
                                                            <option value="Visit" ' . (($ticket["severity"] == "Visit") ? "selected" : "") . '>Visit</option>
                                                                 <option value="On Site" ' . (($ticket["severity"] == "On Site") ? "selected" : "") . '>On Site</option>

                                                                 <option value="Remote" ' . (($ticket["severity"] == "Remote") ? "selected" : "") . '>Remote</option>
                                                                <option value="Call" ' . (($ticket["severity"] == "Call") ? "selected" : "") . '>Call</option>
                                                                </select>
                                                                 <label class="floating-label">Nature of Support<b
                                                                style="color:red">*</b></label>
                                                                 </div>';

                                                        // } else {
                                                        //     echo '';
                                                        // }
                                                    }
                                                    ?>
                                                </div>

                                                <!-- Right Side: Other Fields -->
                                                <div class="col-md-9">
                                                    <div class="form-group">
                                                        <textarea id="concern" name="concern" class="form-control"
                                                            placeholder=" " rows="3"
                                                            required><?= htmlspecialchars($ticket['concern']) ?></textarea>
                                                        <label for="concern" class="floating-label">Concern<b
                                                                style="color:red">*</b></label>
                                                        <span class="small" id="concernError" style="color:red"></span>

                                                    </div>

                                                    <?php if ($ticket['status'] == 'On Hold'): ?>
                                                        <div id="withMessage">
                                                            <div class="form-group">
                                                                <textarea id="resolution" name="resolution"
                                                                    class="form-control" required placeholder=" "
                                                                    rows="10"><?= htmlspecialchars($ticket['resolution']) ?></textarea>
                                                                <label for="resolution" class="floating-label"
                                                                    style="top: 1px">Resolution
                                                                    <b style="color:red">*</b></label>
                                                            </div>
                                                            <div class="form-group">
                                                                <textarea id="message" name="message" class="form-control"
                                                                    required
                                                                    placeholder="Please provide a reason why this ticket is on hold."
                                                                    rows="5"></textarea>
                                                                <label for="message" class="floating-label"
                                                                    style="top: 1px">Message
                                                                    <b style="color:red">*</b></label>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="form-group" id="resolutionGroup">
                                                            <textarea id="resolution" name="resolution" class="form-control"
                                                                required placeholder=" "
                                                                rows="17"><?= htmlspecialchars($ticket['resolution']) ?></textarea>
                                                            <label for="resolution" class="floating-label"
                                                                style="top: 1px">Resolution
                                                                <b style="color:red">*</b></label>
                                                        </div>
                                                    <?php endif; ?>

                                                </div>
                                            </div>

                                        </div>


                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-secondary" id="closeBtn"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" id="saveBtn">Update</button>
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

    <!-- real message notif -->
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

    <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">Transfer Ticket:
                        <span><?= htmlspecialchars($ticket['ticket_no']) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="transferTicket">
                        <input type="hidden" name="PK_activityLogID"
                            value="<?= htmlspecialchars($ticket['PK_activityLogID']) ?>">
                        <input type="hidden" name="ticket_no" value="<?= htmlspecialchars($ticket['ticket_no']) ?>">
                        <div class="form-group">
                            <?php
                            include 'inc/conn.php';

                            $currentUser = $_SESSION['Username'] ?? ''; // Get the current session username safely
                            
                            if (!empty($currentUser)) {
                                $sql = "SELECT Username, class FROM tbl_users WHERE AccountStatus = 'ACTIVE' AND Username != ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $currentUser);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                echo '<select name="transfertoUsername" id="transfertoUsername" class="form-control" required>';
                                echo '<option disabled selected value="">Please Select</option>';

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $username = htmlspecialchars($row['Username'], ENT_QUOTES, 'UTF-8');
                                        echo '<option value="' . $username . '">' . $username . '</option>';
                                    }
                                }

                                echo '</select>';
                                echo '<label class="floating-label">Handled By<b style="color:red">*</b></label>';

                                // Close statement
                                $stmt->close();
                            } else {
                                echo '<p style="color:red;">Session Username is not set.</p>';
                            }

                            // Close connection
                            $conn->close();
                            ?>
                            <span class="small" id="transfertoUsernameError" style="color:red"></span>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" id="closeBtn"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="saveBtn">Send</button>
                        </div>
                    </form>

                </div>

            </div>
        </div>
    </div>

    <!-- Updaye and Transfer -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let isSubmitting = false;

            // ✅ Define once and globally
            function beforeUnloadWarning(e) {
                e.preventDefault();
                e.returnValue = "Warning! Do not close, save first";
                return e.returnValue;
            }

            // ✅ Attach once globally
            window.addEventListener("beforeunload", beforeUnloadWarning);

            // =====================
            // 🚀 Form 1: Add/Update Ticket
            // =====================
            const addForm = document.getElementById("AddForm");
            if (addForm) {
                addForm.addEventListener("submit", function (event) {
                    event.preventDefault();
                    if (isSubmitting) return;
                    saveTicket(this);
                });
            }

            function saveTicket(form) {
                isSubmitting = true;
                let formData = new FormData(form);

                $.ajax({
                    url: "update_ticket.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        window.removeEventListener("beforeunload", beforeUnloadWarning);

                        swal({
                            title: "Success",
                            text: "Ticket updated successfully!",
                            icon: "success",
                            buttons: false,
                            timer: 2000
                        }).then(() => {
                            window.removeEventListener("beforeunload", beforeUnloadWarning);
                            window.location.href = 'activity_logs.php';
                        });
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", error);

                        swal({
                            title: "Error",
                            text: "Error saving ticket. Check console.",
                            icon: "error"
                        });

                        isSubmitting = false;
                    }
                });
            }

            // =====================
            // 🔁 Form 2: Transfer Ticket
            // =====================
            const transferForm = document.getElementById("transferTicket");
            if (transferForm) {
                transferForm.addEventListener("submit", function (event) {
                    event.preventDefault();
                    let formData = new FormData(this);

                    $.ajax({
                        url: "transferTo.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: "json",
                        success: function (response) {
                            window.removeEventListener("beforeunload", beforeUnloadWarning);

                            if (response.success) {
                                swal({
                                    title: "Success",
                                    text: "Ticket transferred successfully!",
                                    icon: "success",
                                    buttons: false,
                                    timer: 2000
                                }).then(() => {
                                    window.removeEventListener("beforeunload", beforeUnloadWarning);
                                    window.location.href = 'activity_logs.php';
                                });
                            } else {
                                alert("Error: " + response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Error:", error);
                            alert("An error occurred while updating the ticket.");
                        }
                    });
                });
            }

            // =====================
            // ❌ Close Button Logic
            // =====================
            const closeBtn = document.getElementById("closeBtn");
            if (closeBtn) {
                closeBtn.addEventListener("click", function (event) {
                    event.preventDefault();

                    swal({
                        title: "Are you sure?",
                        text: "Any unsaved changes will be lost.",
                        icon: "warning",
                        buttons: {
                            confirm: {
                                text: "Yes",
                                value: true,
                                visible: true,
                                className: 'btn btn-success',
                                closeModal: false
                            },
                            cancel: {
                                text: "No",
                                value: null,
                                visible: true,
                                className: 'btn btn-danger',
                                closeModal: true
                            }
                        },
                        closeOnClickOutside: false,
                    }).then((isConfirm) => {
                        if (isConfirm) {
                            window.removeEventListener("beforeunload", beforeUnloadWarning);
                            window.location.href = "activity_logs.php";
                        }
                    });
                });
            }
        });
    </script>





</body>

</html>