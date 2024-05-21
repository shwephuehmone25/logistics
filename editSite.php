<?php
session_start(); 

include 'database.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $number_of_vehicles = filter_input(INPUT_POST, 'number_of_vehicles', FILTER_VALIDATE_INT);

    if (!$id || !$name || !$address || $number_of_vehicles === false) {
        $_SESSION['error_message'] = "Invalid input data provided.";
        header("Location: sites.php");
        exit();
    }

    $sql = "UPDATE sites SET name = ?, address = ?, number_of_vehicles = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['error_message'] = "Error preparing the statement: " . $conn->error;
        header("Location: sites.php");
        exit();
    }

    $stmt->bind_param("ssii", $name, $address, $number_of_vehicles, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Site is updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: sites.php"); 
    exit();
} else {
    header("Location: sites.php");
    exit();
}
?>
