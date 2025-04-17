<?php
session_start();
include 'inc/conn.php';

if (isset($_SESSION['Username'])) {
    $username = $_SESSION['Username'];
    $conditions = ["a.handle_by = ?"];
    $params = [$username];
    $types = "s";

    if (!empty($_POST['requestDate1']) && !empty($_POST['requestDate2'])) {
        $conditions[] = "DATE(a.created_at) BETWEEN ? AND ?";
        $params[] = $_POST['requestDate1'];
        $params[] = $_POST['requestDate2'];
        $types .= "ss";
    }

    if (!empty($_POST['departmentSelect'])) {
        $conditions[] = "requestor_department = ?";
        $params[] = $_POST['departmentSelect'];
        $types .= "i";
    }

    if (!empty($_POST['statusSelect'])) {
        $conditions[] = "a.status = ?";
        $params[] = $_POST['statusSelect'];
        $types .= "s";
    }

    ?>
    <div class="table-responsive">
        <table id="basic-datatables" class="display table table-striped table-hover hover-table" width="100%">
            <thead>
                <tr>
                    <th></th>
                    <th>Ticket No</th>
                    <th>Priority</th>
                    <th>Task Title</th>
                    <th>Concern</th>
                    <th>Time Duration</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build SQL query with dynamic conditions
                $sql = "SELECT t.*, a.* 
                        FROM ticket_status t
                        INNER JOIN activity_logs a ON t.FK_activityLogID = a.PK_activityLogID 
                        WHERE " . implode(" AND ", $conditions);

                // Prepare the statement
                $stmt = $conn->prepare($sql);

                // Dynamically bind the parameters
                $stmt->bind_param($types, ...$params);

                // Execute the statement
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $bgColors = [
                            'In Progress' => '#00A6ED',
                            'On Hold' => '#8A95A0',
                        ];
                        $bgColor = $bgColors[$row["status"]] ?? 'transparent';
                        ?>
                        <tr data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>" class="viewBtn"
                            style="cursor: pointer">
                            <td data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($row["status"]) ?>"
                                style="background-color: <?= $bgColor ?>; width: 5px;"></td>
                            <td><?= htmlspecialchars($row["ticket_no"]) ?></td>
                            <td><?= htmlspecialchars($row["priority"]) ?></td>
                            <td><?= htmlspecialchars($row["task_description"]) ?></td>
                            <td style="width:20%">
                                <?php
                                $words = explode(' ', $row["concern"]);
                                echo htmlspecialchars(implode(' ', array_slice($words, 0, 20))) . (count($words) > 20 ? '...' : '');
                                ?>
                            </td>

                            <td>
                                <?php if ($row['status'] == 'Completed'): ?>
                                    <?php
                                    $timeParts = [];

                                    if ($row["days"] > 0) {
                                        $timeParts[] = $row["days"] . " day" . ($row["days"] > 1 ? "s" : "");
                                    }
                                    if ($row["hours"] > 0) {
                                        $timeParts[] = $row["hours"] . " hour" . ($row["hours"] > 1 ? "s" : "");
                                    }
                                    if ($row["minutes"] > 0) {
                                        $timeParts[] = $row["minutes"] . " minute" . ($row["minutes"] > 1 ? "s" : "");
                                    }
                                    if ($row["seconds"] > 0) {
                                        $timeParts[] = $row["seconds"] . " second" . ($row["seconds"] > 1 ? "s" : "");
                                    }

                                    echo htmlspecialchars(implode(", ", $timeParts));
                                    ?>
                                <?php endif; ?>

                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-xs view-btn"
                                    data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>"
                                    style="margin:5px;">
                                    <span class="icon-eye"></span>
                                </button>

                                <button type="button" class="btn btn-success btn-xs message-btn" style="margin:5px;"
                                    data-bs-toggle="modal" data-bs-target="#viewTicketModal"
                                    data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>">
                                    <span class="fas fa-reply"></span>
                                </button>

                            </td>
                        </tr>
                    <?php endwhile; endif;

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

                    <th>Time Duration</th>
                    <th>Action</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}
?>



<!-- Data Table -->
<script>
    $(document).ready(function () {
        $('#basic-datatables tfoot tr').empty();
        $('#basic-datatables thead th').each(function (index) {
            var title = $(this).text();
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

<!-- View data modal -->
<script>
    $('#basic-datatables').on('click', '.view-btn', function (event) {
        var id = $(this).data('id');
        var ticketNo = $(this).data('ticketno');
        $('#ViewModal').modal('show');
        // alert(ticketNo);


        $.ajax({
            url: "view_data_ticketStates.php",
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
                $('#ViewMessages').val(json.messages);

            }
        });
    });
</script>

<script>
    $('#basic-datatables').on('click', '.message-btn', function (event) {
        var id = $(this).data('id');
        var ticketNo = $(this).data('ticketno');
        $('#viewTicketModal').modal('show');

        $.ajax({
            url: "view_ticket_status_messages.php",
            data: { id: id },
            type: 'post',
            success: function (data) {
                var json = JSON.parse(data);
                $('#ViewID').val(id); // assuming you have a hidden input with id="ViewID"
                $('#ViewTicketNo').val(ticketNo); // new input field
                $('#FKactivityLogID').val(json.FK_activityLogID);
                $('#ViewMessages').val(json.messages);
                // Check if new_messages is null or empty
                var messageToShow = (json.new_messages === null || json.new_messages === "")
                    ? json.messages
                    : json.new_messages;

                $('#ViewOldMessages').val(messageToShow);
            }
        });
    });

</script>

<!-- Double Click for view the data at table -->
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
                url: "view_data_ticketStates.php",
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
                    $('#ViewMessages').val(json.messages);

                }
            });
        }
    });

</script>