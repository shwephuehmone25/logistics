<?php
require 'database.php';

$weight = $_POST['weight'];

$sql = "SELECT id, type FROM vehicles WHERE max_weight >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $weight);
$stmt->execute();
$result = $stmt->get_result();

$options = "";
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['type']) . "</option>";
}

echo $options;
$stmt->close();
$conn->close();
?>
