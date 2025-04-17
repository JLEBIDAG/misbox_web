<?php
// Function to start a XAMPP service
function startService($service) {
    return shell_exec("xampp_start.exe " . escapeshellarg($service));
}

// Function to stop a XAMPP service
function stopService($service) {
    return shell_exec("xampp_stop.exe " . escapeshellarg($service));
}

// Handle GET requests
if (isset($_GET['action']) && isset($_GET['service'])) {
    $action = $_GET['action'];
    $service = $_GET['service'];

    if ($action == "start") {
        startService($service);
    } elseif ($action == "stop") {
        stopService($service);
    }
}

// Check if a service is running
function isServiceRunning($service) {
    $status = shell_exec("tasklist | findstr " . escapeshellarg($service));
    return !empty($status);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP Control Panel</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .service { margin: 10px; padding: 10px; border: 1px solid #ddd; display: inline-block; width: 200px; }
        .running { color: green; }
        .stopped { color: red; }
    </style>
</head>
<body>

    <h2>XAMPP Web Control Panel</h2>

    <?php
    $services = ["apache", "mysql"];
    foreach ($services as $service) {
        $isRunning = isServiceRunning($service);
        echo "<div class='service'>";
        echo "<h3>$service</h3>";
        echo "<p class='" . ($isRunning ? "running" : "stopped") . "'>" . ($isRunning ? "Running" : "Stopped") . "</p>";
        echo "<a href='?action=start&service=$service'>Start</a> | ";
        echo "<a href='?action=stop&service=$service'>Stop</a>";
        echo "</div>";
    }
    ?>

</body>
</html>
