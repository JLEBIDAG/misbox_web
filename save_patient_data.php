<?php
include 'inc/conn.php';
$data = json_decode(file_get_contents("php://input"), true);

// Prepare your SQL query to insert data into the database
if (isset($data['patientsData']) && is_array($data['patientsData'])) {
    foreach ($data['patientsData'] as $patient) {
        $stmt = $conn->prepare("INSERT INTO tbl_patients (p_ID, p_FirstName, p_MiddleName, p_LastName, CreatedAt, UpdatedAt, encoded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $patient['p_ID'], $patient['p_FirstName'], $patient['p_MiddleName'], $patient['p_LastName'], $patient['CreatedAt'], $patient['UpdatedAt'], $patient['encoded_by']);
        
        if (!$stmt->execute()) {
            // Return error as JSON if insert fails
            echo json_encode(["success" => false, "message" => "Error inserting patient data"]);
            exit;
        }
    }
    
    // Return success as JSON if all data was inserted
    echo json_encode(["success" => true, "message" => "Data inserted successfully"]);
} else {
    // Return error as JSON if no valid data was received
    echo json_encode(["success" => false, "message" => "No data received"]);
}

$conn->close();
?>
