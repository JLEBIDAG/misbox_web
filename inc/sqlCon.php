<!-- 
// Database connection details
$serverName = "PC-MIS-03"; // Replace with your server name
$dataBase = "LiveDB_BHMCHS8"; // Replace with your database name
$uid = ""; // Replace with your SQL Server username (if using SQL Authentication)
$pass = ""; // Replace with your SQL Server password (if using SQL Authentication)

// Using SQLSRV Connection
try {
    // Connection settings
    $connection = [
        "Database" => $dataBase,
        "UID" => $uid,
        "PWD" => $pass,
        "CharacterSet" => "UTF-8"
    ];

    // Attempt to establish connection
    $sqlconn = sqlsrv_connect($serverName, $connection);
    
    if ($sqlconn) {
        echo "Connection established using SQLSRV.<br>";
    } else {
        // Retrieve detailed error messages
        throw new Exception(print_r(sqlsrv_errors(), true));
    }
} catch (Exception $e) {
    echo "SQLSRV Connection failed: " . $e->getMessage() . "<br>";
}

// Using PDO Connection
try {
    // PDO connection string
    $dsn = "sqlsrv:Server=$serverName;Database=$dataBase";
    
    // Attempt to establish connection
    $pdo = new PDO($dsn, $uid, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Enable exceptions for errors
    ]);
    
    echo "Connection established using PDO.<br>";
} catch (PDOException $e) {
    echo "PDO Connection failed: " . $e->getMessage() . "<br>";
}

// Test for loaded extensions
echo "<br>Extensions loaded:<br>";
echo extension_loaded("sqlsrv") ? "sqlsrv is loaded.<br>" : "sqlsrv is NOT loaded.<br>";
echo extension_loaded("pdo_sqlsrv") ? "pdo_sqlsrv is loaded.<br>" : "pdo_sqlsrv is NOT loaded.<br>";

// Optional: Clean up SQLSRV connection
if ($sqlconn) {
    sqlsrv_close($sqlconn);
    echo "SQLSRV connection closed.<br>";
}
?> -->
