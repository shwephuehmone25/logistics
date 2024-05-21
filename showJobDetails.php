<?php
require 'database.php'; 

if (isset($_GET['id'])) {
    $jobId = intval($_GET['id']); 

    $stmt = $conn->prepare("SELECT id, good_weight, good_size, hazardous, start_date, deadline,  origin_site_id,  destination_site_id,  vehicle_id, status FROM jobs WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['error' => 'Preparation error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($job = $result->fetch_assoc()) {
        echo json_encode($job);  
    } else {
        echo json_encode(['error' => 'No job found']);
    }
    $stmt->close();
}
$conn->close();
?>
