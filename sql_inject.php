<?php

include 'inc/conn.php';

// SQL Injection Payload
$injection_payloads = [
    "' OR '1'='1' -- ",
    "' OR '1'='1' #",
    "' OR '1'='1' /*",
    "' OR 'a'='a' -- ",
    "' OR 1=1 -- ",
    "' OR ''='",
    "admin' --",
    "admin' #",
    "admin'/*",
    "admin' OR '1'='1' --",
    "'; DROP TABLE Users; --" // 🚨 DANGEROUS: DO NOT USE IF YOU HAVE IMPORTANT DATA
];

foreach ($injection_payloads as $payload) {
    $sql = "SELECT * FROM tbl_users WHERE Username = '$payload' AND Password = '$payload'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "🚨 SQL Injection Successful! Vulnerability detected with payload: $payload <br>";
    } else {
        echo "✅ System safe from payload: $payload <br>";
    }
}

$conn->close();
?>
