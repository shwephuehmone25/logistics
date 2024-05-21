<?php
include 'database.php';

$message = '';
$existing_name = '';
$existing_address = '';
$existing_vehicles = 0;

//create new job object
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['name'], $_POST['address'], $_POST['number_of_vehicles'])) {
        $site_name = $_POST['name'];
        $address = $_POST['address'];
        $number_of_vehicles = $_POST['number_of_vehicles'];

        $sql = "INSERT INTO sites (name, address, number_of_vehicles) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $site_name, $address, $number_of_vehicles);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "A new job created successfully!";
        } else {
            $_SESSION['error_message'] = "Error creating site: " . $stmt->error;
        }
        $stmt->close();
    }
}

$message = '';
$search = $_GET['search'] ?? '';
$search = trim($search);

//Search
$sql = "SELECT * FROM sites";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " WHERE name LIKE CONCAT('%', ?, '%') OR address LIKE CONCAT('%', ?, '%')";
    $params[] = $search;
    $params[] = $search;
    $types = 'ss';
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Append limit clause for pagination
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

// Fetch the total number of filtered results for pagination links
$countSql = "SELECT COUNT(*) as total FROM sites";
if (!empty($search)) {
    $countSql .= " WHERE name LIKE CONCAT('%', ?, '%') OR address LIKE CONCAT('%', ?, '%')";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('ss', $search, $search);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
} else {
    $countResult = $conn->query($countSql);
    $totalCount = $countResult->fetch_assoc()['total'];
}

$number_of_pages = ceil($totalCount / $results_per_page);

$stmt->close();
// if ($countStmt) {
//     $countStmt->close();
// }
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sites</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        @media (max-width: 768px) {
            #sidebar {
                display: none;
            }

            #page-content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100">
            <!-- Navbar -->
            <?php include 'navbar.php'; ?>

            <div class="container-fluid">
                <h2 class="mt-3">Sites Management</h2>
                <?php if (isset($_SESSION['success_message'])) : ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_message'];
                                                        unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])) : ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error_message'];
                                                    unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <button class="btn btn-primary mb-3" type="button" data-bs-toggle="modal" data-bs-target="#addSiteModal"><i class="fa-solid fa-circle-plus px-1"></i> Create Site</button>
                    </div>
                    <div class="col-12 col-md-6">
                        <form action="sites.php" method="GET" class="form-inline">
                            <div class="input-group mb-3">
                                <input type="text" name="search" class="form-control" placeholder="Search for..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search fa-sm"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Site Name</th>
                                <th scope="col">Address</th>
                                <th scope="col">Number of Vehicles</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0) : ?>
                                <?php while ($row = $result->fetch_assoc()) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                                        <td><?php echo htmlspecialchars($row['number_of_vehicles']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?php echo $row['id']; ?>"><i class="fa-solid fa-pen px-1"></i>Edit</button>
                                            <a href="deleteSite.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa-solid fa-trash px-1"></i>Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5">No sites found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $number_of_pages; $i++) : ?>
                            <li class="page-item <?= ($i == $page ? 'active' : '') ?>"><a class="page-link" href="sites.php?page=<?= $i; ?>"><?= $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add Site Modal -->
    <div class="modal fade" id="addSiteModal" tabindex="-1" aria-labelledby="addSiteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="sites.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSiteModalLabel">Add New Site</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="number_of_vehicles" class="form-label">Number of Vehicles</label>
                            <input type="number" class="form-control" id="number_of_vehicles" name="number_of_vehicles" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Site</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Site Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="editSite.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Site</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="edit-address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="number_of_vehicles" class="form-label">Number of Vehicles</label>
                            <input type="number" class="form-control" id="edit-number_of_vehicles" name="number_of_vehicles" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk px-1"></i>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                var siteId = $(this).data('id');
                $.ajax({
                    url: 'showSiteDetails.php',
                    type: 'post',
                    data: {
                        id: siteId
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        $('#edit-id').val(data.id);
                        $('#edit-name').val(data.name);
                        $('#edit-address').val(data.address);
                        $('#edit-number_of_vehicles').val(data.number_of_vehicles);
                        $('#editModal').modal('show');
                    }
                });
            });
        });

        //form validation 
        $(document).ready(function() {
            // Function to check if the input is empty
            $('#siteForm').submit(function(event) {
                event.preventDefault();

                var isNameValid = validateInput($('#name'), $('#name').siblings('.invalid-feedback'));
                var isAddressValid = validateInput($('#address'), $('#address').siblings('.invalid-feedback'));
                var isNumVehiclesValid = validateInput($('#number_of_vehicles'), $('#number_of_vehicles').siblings('.invalid-feedback'));

                if (isNameValid && isAddressValid && isNumVehiclesValid) {
                    this.submit();
                }
            });

            $('input').on('input', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            });
        });

        //hide alert message after 5seconds
        $(document).ready(function() {
            $('.alert').delay(5000).fadeOut('slow');
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>