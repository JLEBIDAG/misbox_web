<?php
date_default_timezone_set("Asia/Manila"); // Set timezone to Philippines

function getWeekOfMonth($date) {
    $firstDayOfMonth = strtotime(date("Y-m-01", strtotime($date))); // First day of the month
    $currentDay = strtotime($date);
    
    $weekNumber = ceil((date("j", $currentDay) + date("N", $firstDayOfMonth) - 1) / 7);
    
    return "Week " . $weekNumber;
}

echo getWeekOfMonth(date("Y-m-d")); // Outputs: Week X based on the Philippine time
?>
