<?php
require 'database.php';

if (isset($_GET['id'])) {
    $vehicleId = intval($_GET['id']);
    $query = "SELECT id, type, max_weight, max_space, site_id FROM vehicles WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $vehicleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Failed to prepare the statement.']);
    }
    $stmt->close();
    $conn->close();
}
?>
