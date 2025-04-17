<?php
session_start();
include 'inc/conn.php';

if (isset($_SESSION['Username'])) {
    $conditions = [];
    $params = [];
    $types = "";

    if (!empty($_POST['requestDate1']) && !empty($_POST['requestDate2'])) {
        $conditions[] = "DATE(created_at) BETWEEN ? AND ?";
        $params[] = $_POST['requestDate1'];
        $params[] = $_POST['requestDate2'];
        $types .= "ss";
    }

    if (!empty($_POST['dateAccomp1']) && !empty($_POST['dateAccomp2'])) {
        $conditions[] = "DATE(dateTimeAccomp) BETWEEN ? AND ?";
        $params[] = $_POST['dateAccomp1'];
        $params[] = $_POST['dateAccomp2'];
        $types .= "ss";
    }

    if (!empty($_POST['departmentSelect'])) {
        $conditions[] = "requestor_department = ?";
        $params[] = $_POST['departmentSelect'];
        $types .= "s";
    }

    if (!empty($_POST['statusSelect'])) {
        $conditions[] = "status = ?";
        $params[] = $_POST['statusSelect'];
        $types .= "s";
    }

    if (!empty($_POST['usernameSelect'])) {
        $conditions[] = "handle_by = ?";
        $params[] = $_POST['usernameSelect'];
        $types .= "s";
    }


    $sql = "SELECT * FROM activity_logs";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY 
                CASE 
                    WHEN priority = 'Urgent' AND status != 'Completed' THEN 0
                    WHEN priority = 'High' AND status != 'Completed' THEN 2
                    WHEN priority = 'Medium' AND status != 'Completed' THEN 3
                    WHEN priority = 'Low' AND status != 'Completed' THEN 4
                    ELSE 5
                END";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
?>

<div class="table-responsive">
    <table id="basic-datatables" class="display table table-striped table-hover hover-table" width="100%">
        <thead>
            <tr>
                <th></th>
                <th>Ticket No</th>
                <th>Ticket Created</th>
                <th>Priority</th>
                <th>Task Title</th>
                <th>Concern</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php
            $bgColors = [
                'Completed' => '#00D26A',
                'In Progress' => '#00A6ED',
                'Cancelled' => '#F92F60',
                'On Hold' => '#8A95A0',
                'Open' => '#FCD53F'
            ];

            while ($row = $result->fetch_assoc()):
                $bgColor = $bgColors[$row["status"]] ?? 'transparent';
                $words = explode(' ', $row["concern"]);
                $limited_text = implode(' ', array_slice($words, 0, 20));
                $isCompleted = $row["status"] === "Completed";
            ?>
            <tr data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>" style="cursor: pointer">
                <td style="background-color: <?= $bgColor ?>; width: 0px;" title="<?= $row["status"] ?>"></td>
                <td><?= $row["ticket_no"] ?></td>
                <td><?= date("F j, Y g:i A", strtotime($row["created_at"])) ?></td>
                <td><?= $row["priority"] ?></td>
                <td><?= $row["task_description"] ?></td>
                <td style="width:50%">
                    <?= htmlspecialchars($limited_text) ?>
                    <?= count($words) > 20 ? '...' : '' ?>
                </td>
                <td>
                    <button type="button" class="btn btn-primary btn-xs view-btn" data-id="<?= $row["PK_activityLogID"] ?>" data-ticketNo="<?= $row["ticket_no"] ?>" style="margin:5px;">
                        <span class="icon-eye"></span>
                    </button>
                    <a href="view_ticket.php?id=<?= $row["PK_activityLogID"] ?>"
                       class="btn btn-info btn-xs edit-btn"
                       style="margin:5px; <?= $isCompleted ? 'pointer-events: none; opacity: 0.6; cursor: default;' : '' ?>">
                        <span class="icon-pencil"></span>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" class="text-center">No records found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
    $stmt->close();
    $conn->close();
} else {
?>
    <tr><td colspan="6" class="text-center">Invalid request</td></tr>
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

<!-- Double Click for view the data at table -->
<script>
$(document).ready(function () {
    $('#basic-datatables').on('dblclick', 'tr', function () {
        const row = $(this);
        const id = row.data('id');
        const ticketNo = row.data('ticketno'); // jQuery auto-converts data-ticketNo to data('ticketno')

        if (id) {
            openModal(id, ticketNo);
        }
    });

    function openModal(id, ticketNo) {
        $('#ViewModal').modal('show');

        $.ajax({
            url: "view_data.php",
            method: "POST",
            data: { id: id },
            success: function (response) {
                let json = JSON.parse(response);

                $('#ViewNumber').text(ticketNo);
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
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error);
            }
        });
    }
});
</script>