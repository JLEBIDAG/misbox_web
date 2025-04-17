<?php
// Display errors to diagnose issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'inc/conn.php'; // Make sure this file contains your DB connection

// Query to get the tickets data with completion time calculation
$sql = "SELECT 
            `ticket_no`, `requestor_name`, `priority`, `status`, `issue_reported`, 
            `resolution`, `remarks`, `dueDate`, `created_at`, `dateTimeAccomp`, `handle_by`,
            TIMESTAMPDIFF(SECOND, `created_at`, `dateTimeAccomp`) AS completion_time
        FROM `activity_logs`
        WHERE `dateTimeAccomp` IS NOT NULL
        ORDER BY completion_time ASC";  // Sorting by completion time (fastest completion first)
$result = $conn->query($sql);

// Check if query returned any data
if ($result->num_rows > 0) {
    // Data found
} else {
    echo "No data found!";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Report - Ranked by Completion Time</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Ticket Report - Ranked by Completion Time</h2>

    <!-- Select for filtering the rank -->
    <div class="mb-4">
        <label for="rankFilter" class="form-label">Filter by Completion Time</label>
        <select id="rankFilter" class="form-select" onchange="filterByRank()">
            <option value="all">All Tickets</option>
            <option value="top5">Top 5 Fastest Completed Tickets</option>
            <option value="top10">Top 10 Fastest Completed Tickets</option>
        </select>
    </div>

    <!-- Table to display the tickets -->
    <table class="table table-bordered table-striped mt-4" id="ticketTable">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Ticket No</th>
                <th>Requestor Name</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Issue Reported</th>
                <th>Resolution</th>
                <th>Remarks</th>
                <th>Due Date</th>
                <th>Completion Time (Seconds)</th>
                <th>Handled By</th>  <!-- New column for "Handled By" -->
                <th>Created By</th>  <!-- New column for "Handled By" -->
                <th>Date Time Accomp</th>  <!-- New column for "Handled By" -->

            </tr>
        </thead>
        <tbody>
            <?php
            $rank = 1;  // Start the rank from 1
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $rank . "</td>";
                    echo "<td>" . $row["ticket_no"] . "</td>";
                    echo "<td>" . $row["requestor_name"] . "</td>";
                    echo "<td>" . $row["priority"] . "</td>";
                    echo "<td>" . $row["status"] . "</td>";
                    echo "<td>" . $row["issue_reported"] . "</td>";
                    echo "<td>" . $row["resolution"] . "</td>";
                    echo "<td>" . $row["remarks"] . "</td>";
                    echo "<td>" . $row["dueDate"] . "</td>";
                    echo "<td>" . $row["completion_time"] . "</td>";
                    echo "<td>" . $row["handle_by"] . "</td>";  
                    echo "<td>" . $row["created_at"] . "</td>";  
                    echo "<td>" . $row["dateTimeAccomp"] . "</td>";  

                    echo "</tr>";
                    $rank++;  // Increment the rank
                }
            } else {
                echo "<tr><td colspan='11'>No tickets found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

<script>
    // Function to filter the tickets based on the selected rank filter
    function filterByRank() {
        var filter = document.getElementById('rankFilter').value;
        var table = document.getElementById('ticketTable');
        var rows = table.getElementsByTagName('tr');
        var rankLimit;

        // Set rank limit based on filter
        if (filter === 'top5') {
            rankLimit = 5;
        } else if (filter === 'top10') {
            rankLimit = 10;
        } else {
            rankLimit = rows.length - 1;  // Show all tickets
        }

        // Loop through all rows and hide those that don't meet the rank criteria
        for (var i = 1; i < rows.length; i++) {  // Start from 1 to skip header row
            var row = rows[i];
            var rankCell = row.cells[0]; // The rank is in the first column
            if (rankCell) {
                var rank = parseInt(rankCell.innerText);
                if (rank > rankLimit) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            }
        }
    }
</script>

</body>
</html>

<?php
$conn->close();
?>
