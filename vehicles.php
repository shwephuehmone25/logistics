<?php
include 'database.php';

function getAllSites($siteId)
{
    global $conn;

    if ($conn === null) {
        echo "Database connection is not available.";
        return "Site Not Found";
    }

    $sql = "SELECT name FROM sites WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        return "Error retrieving site";
    }

    $stmt->bind_param("i", $siteId);
    if (!$stmt->execute()) {
        echo "Error executing query: " . $stmt->error;
        return "Error retrieving site";
    }

    $stmt->bind_result($siteName);
    $stmt->fetch();
    $stmt->close();
    return $siteName;
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $max_weight = $_POST['max_weight'];
    $max_space = $_POST['max_space'];
    $home_site_id = $_POST['site_id'];

    $sql = "INSERT INTO vehicles (type, max_weight, max_space, site_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siii", $type, $max_weight, $max_space, $home_site_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "A vehicle is created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating vehicle: " . $stmt->error;
    }
    $stmt->close();
}

// Handle POST request to update vehicle details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vehicle_id'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $type = $_POST['type'];
    $max_weight = $_POST['max_weight'];
    $max_space = $_POST['max_space'];
    $home_site_id = $_POST['site_id'];

    $sql = "UPDATE vehicles SET type = ?, max_weight = ?, max_space = ?, site_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error_message'] = "Error preparing SQL statement: " . $conn->error;
        header("Location: vehicles.php");
        exit;
    }

    $stmt->bind_param("siisi", $type, $max_weight, $max_space, $home_site_id, $vehicle_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Vehicle updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating vehicle: " . $stmt->error;
    }
    $stmt->close();
    header("Location: vehicles.php");
    exit();
}

// Fetch all vehicles to display
$sql = "SELECT id, type, max_weight, max_space, site_id FROM vehicles";
$result = $conn->query($sql);

$siteQuery = "SELECT id, name FROM sites";
$siteResult = $conn->query($siteQuery);
$sites = [];
while ($row = $siteResult->fetch_assoc()) {
    $sites[] = $row;
}

$message = '';
$search = $_GET['search'] ?? '';
$search = trim($search);

// Search results
$sql = "SELECT * FROM vehicles";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " WHERE type LIKE CONCAT('%', ?, '%') OR max_weight LIKE CONCAT('%', ?, '%') OR max_space LIKE CONCAT('%', ?, '%')";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types = 'sss';
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Append limit pagination
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $results_per_page;
$types .= 'ii';

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$countSql = "SELECT COUNT(*) as total FROM vehicles";
if (!empty($search)) {
    $countSql .= " WHERE type LIKE CONCAT('%', ?, '%') OR max_weight LIKE CONCAT('%', ?, '%') OR max_space LIKE CONCAT('%', ?, '%')";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('sss', $search, $search, $search);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
} else {
    $countResult = $conn->query($countSql);
    $totalCount = $countResult->fetch_assoc()['total'];
}

$number_of_pages = ceil($totalCount / $results_per_page);

$stmt->close();
if (isset($countStmt)) {
    $countStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>
        <!-- Page Content -->
        <div id="page-content-wrapper w-100">
            <div class="container-fluid">
                <div class="container mt-3">
                    <h2>Manage Vehicles</h2>

                    <?php if (isset($_SESSION['success_message'])) : ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success_message'];
                                                            unset($_SESSION['success_message']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])) : ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error_message'];
                                                        unset($_SESSION['error_message']); ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-6">
                            <button class="btn btn-primary mb-3" type="button" data-bs-toggle="modal" data-bs-target="#addVehicleModal"><i class="fa-solid fa-circle-plus px-1"></i>Create</button>
                        </div>
                        <div class="col-6">
                            <!-- Search -->
                            <form action="vehicles.php" method="GET" class="form-inline">
                                <div class="input-group mb-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search for..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search fa-sm"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Vehicles Table -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Max Weight (kg)</th>
                                    <th scope="col">Max Space (cu. m)</th>
                                    <th scope="col">Home Site</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0) : ?>
                                    <?php while ($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                                            <td><?php echo htmlspecialchars($row['max_weight']); ?></td>
                                            <td><?php echo htmlspecialchars($row['max_space']); ?></td>
                                            <td><?php echo getAllSites($row['site_id']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editVehicleModal" data-id="<?php echo $row['id']; ?>"><i class="fa-solid fa-pen px-1"></i>Edit</button>
                                                <a href="deleteVehicle.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa-solid fa-trash px-1"></i>Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5">No vehicles found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <nav aria-label="Page navigation example">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $number_of_pages; $i++) : ?>
                                <li class="page-item <?= ($i == $page ? 'active' : '') ?>"><a class="page-link" href="vehicles.php?page=<?= $i; ?>"><?= $i; ?></a></li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>

                <!-- Add Vehicle Modal -->
                <div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="vehicles.php">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addVehicleModalLabel">Add New Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="type" class="form-label">Type</label>
                                        <input type="text" class="form-control" id="type" name="type" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_weight" class="form-label">Max Weight (kg)</label>
                                        <input type="number" class="form-control" id="max_weight" name="max_weight" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="max_space" class="form-label">Max Space (cu. m)</label>
                                        <input type="number" class="form-control" id="max_space" name="max_space" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="site_id" class="form-label">Origin Site</label>
                                        <select class="form-control" id="site_id" name="site_id">
                                            <?php foreach ($sites as $site) : ?>
                                                <option value="<?php echo $site['id']; ?>"><?php echo htmlspecialchars($site['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Vehicle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Vehicle Modal -->
                <div class="modal fade" id="editVehicleModal" tabindex="-1" aria-labelledby="editVehicleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="vehicles.php">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editVehicleModalLabel">Edit Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="edit_vehicle_id" name="vehicle_id">
                                    <div class="mb-3">
                                        <label for="edit_type" class="form-label">Type</label>
                                        <input type="text" class="form-control" id="edit_type" name="type" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_max_weight" class="form-label">Max Weight (kg)</label>
                                        <input type="number" class="form-control" id="edit_max_weight" name="max_weight" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_max_space" class="form-label">Max Space (cu. m)</label>
                                        <input type="number" class="form-control" id="edit_max_space" name="max_space" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_home_site_id" class="form-label">Home Site</label>
                                        <select class="form-control" id="edit_home_site_id" name="site_id">
                                            <?php foreach ($sites as $site) : ?>
                                                <option value="<?php echo $site['id']; ?>"><?php echo htmlspecialchars($site['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                var vehicleId = $(this).data('id');
                console.log(vehicleId);
                $.ajax({
                    url: 'showVehicleDetails.php',
                    type: 'GET',
                    data: {
                        id: vehicleId
                    },
                    success: function(response) {
                        console.log(response);
                        var data = JSON.parse(response);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            $('#edit_vehicle_id').val(data.id);
                            $('#edit_type').val(data.type);
                            $('#edit_max_weight').val(data.max_weight);
                            $('#edit_max_space').val(data.max_space);
                            $('#edit_home_site_id').val(data.site_id);
                            $('#editVehicleModal').modal('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                });
            });
        });

        //clear form data after form is close
        $(document).ready(function() {
            $('#addJobModal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
                $(this).find('input[type=checkbox]').prop('checked', false);
                $(this).find('select').prop('selectedIndex', 0);
            });

            $('#editModal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
                $(this).find('input[type=checkbox]').prop('checked', false);
                $(this).find('select').prop('selectedIndex', 0);
            });
        });

        // Function for toggling the sidebar
        $("#sidebarToggle").on('click', function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });

        //hide alert message after 5seconds
        $(document).ready(function() {
            $('.alert').delay(5000).fadeOut('slow');
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>