<?php
session_start();
require 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $good_weight = filter_input(INPUT_POST, 'good_weight', FILTER_VALIDATE_INT);
    $good_size = filter_input(INPUT_POST, 'good_size', FILTER_VALIDATE_INT);
    $hazardous = isset($_POST['hazardous']) ? 1 : 0;
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $deadline = filter_input(INPUT_POST, 'deadline', FILTER_SANITIZE_STRING);
    $origin_site_id = filter_input(INPUT_POST, 'origin_site_id', FILTER_VALIDATE_INT);
    $destination_site_id = filter_input(INPUT_POST, 'destination_site_id', FILTER_VALIDATE_INT);

    if (!$job_id || !$good_weight || !$good_size || !$start_date || !$deadline || !$origin_site_id || !$destination_site_id) {
        $_SESSION['error_message'] = "Invalid input data provided.";
        header("Location: jobs.php");
        exit();
    }

    $sql = "UPDATE jobs SET good_weight = ?, good_size = ?, hazardous = ?, start_date = ?, deadline = ?, origin_site_id = ?, destination_site_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $_SESSION['error_message'] = "Error preparing the statement: " . $conn->error;
        header("Location: jobs.php");
        exit();
    }

    $stmt->bind_param("iiissiii", $good_weight, $good_size, $hazardous, $start_date, $deadline, $origin_site_id, $destination_site_id, $job_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Job is updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating job: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: jobs.php");
    exit();
} else {
    header("Location: jobs.php");
    exit();
}
?>
