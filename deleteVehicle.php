<?php
include 'database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM vehicles WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("location: vehicles.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    
    $stmt->close();
}

$conn->close();
?>
