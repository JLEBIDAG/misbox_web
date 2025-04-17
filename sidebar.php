<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
?>

<div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
        <ul class="nav nav-secondary">
            <li class="nav-item <?php echo ($current_page == 'home.php') ? 'active' : ''; ?>">
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
                    echo "<li class='nav-item " . ($current_page == 'activity_logs.php' ? 'active' : '') . "'>
                            <a href='activity_logs.php' class='d-flex align-items-center text-decoration-none'>
                                <i class='fas fa-file me-2'></i>
                                <p class='mb-0'>Activity Logs</p>
                                <span class='badge bg-danger ms-6' id='notifTitleCount'></span>
                            </a>
                          </li>";

                    echo "<li class='nav-item " . ($current_page == 'bizBox_backup.php' ? 'active' : '') . "'>
                            <a href='bizBox_backup.php'>
                                <i class='fas fa-database'></i>
                                <p>BizBox Backup</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item'>
                            <a href='#forms'>
                                <i class='fas fa-file-alt'></i>
                                <p>Data Encoding</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item " . ($current_page == 'merge_account.php' ? 'active' : '') . "'>
                            <a href='merge_account.php'>
                                <i class='fas fa-user-plus'></i>
                                <p>Merge Account</p>
                            </a>
                          </li>";
                    echo "<li class='nav-item " . ($current_page == 'view_merge_account.php' ? 'active' : '') . "'>
                          <a href='view_merge_account.php'>
                              <i class='fas fa-exchange-alt'></i>
                              <p>Merged Account Overview</p>
                          </a>
                        </li>";

                    echo "<li class='nav-item'>
                            <a href='#maps'>
                                <i class='fas fa-pen-alt'></i>
                                <p>Price Amend</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item'>
                            <a href='#charts'>
                                <i class='fas fa-thermometer-half'></i>
                                <p>Server Temp</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item'>
                            <a href='widgets.html'>
                                <i class='fas fa-user'></i>
                                <p>User</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item " . ($current_page == 'ticket.php' ? 'active' : '') . "'>
                            <a href='ticket.php'>
                                <i class='fas fa-ticket-alt'></i>
                                <p>Tickets</p>
                            </a>
                          </li>";
                    echo "<li class='nav-item " . ($current_page == 'ticket_states.php' ? 'active' : '') . "'>
                          <a href='ticket_states.php'>
                              <i class='fas fa-clipboard-list'></i>
                              <p>Tickets States</p>
                          </a>
                        </li>";
                }

                if ($_SESSION['Username'] == 'JLEBIDAG') {
                    echo "<li class='nav-item " . ($current_page == 'mis.php' ? 'active' : '') . "'>
                            <a target='_blank' href='http://localhost/phpmyadmin/index.php?route=/database/structure&db=mis2025' style='display: flex; align-items: center; gap: 8px;'>
                                <img src='assets/img/favicon.ico' width='20px' height='20px' class='mb-2'>
                                <p>PHP MYADMIN</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item'>
                            <a href='#' data-bs-toggle='modal' data-bs-target='#maintenanceModal' style='display: flex; align-items: center; gap: 8px;'>
                                <i class='fas fa-screwdriver'></i>
                                <p>Maintenance</p>
                            </a>
                          </li>";
                }

                if ($_SESSION['class'] == 'HD') {
                    echo "<li class='nav-item " . ($current_page == 'ticket_status.php' ? 'active' : '') . "'>
                            <a href='ticket_status.php'>
                                <i class='fas fa-check-circle'></i>
                                <p>Ticket Status</p>
                            </a>
                          </li>";
                }

                if ($_SESSION['Username'] == 'LLENDEZ') {
                    echo "<li class='nav-section'>
                            <span class='sidebar-mini-icon'>
                                <i class='fa fa-ellipsis-h'></i>
                            </span>
                            <h4 class='text-section'>MIS Dashboard</h4>
                          </li>";

                    echo "<li class='nav-item " . ($current_page == 'mis.php' ? 'active' : '') . "'>
                            <a href='mis.php'>
                                <i class='icon-user'></i>
                                <p>MIS Staff</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item " . ($current_page == 'requests_ticket.php' ? 'active' : '') . "'>
                            <a href='requests_ticket.php'>
                                <i class='fas fa-ticket-alt'></i>
                                <p>Request Ticket</p>";

                    if ($requestTotal > 0) {
                        echo "<span class='badge bg-danger ms-6'>" . $requestTotal . "</span>";
                    }

                    echo "  </a>
                          </li>";
                }

                if ($_SESSION['FK_Departmentname'] == 1003) {
                    echo "<li class='nav-item'>
                            <a href='#tables'>
                                <i class='fas fa-user-plus'></i>
                                <p>Merge Account</p>
                            </a>
                          </li>";
                }

                if ($_SESSION['FK_Departmentname'] == 1032) {
                    echo "<li class='nav-item'>
                            <a href='#forms'>
                                <i class='fas fa-file-alt'></i>
                                <p>Data Encoding</p>
                            </a>
                          </li>";

                    echo "<li class='nav-item'>
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