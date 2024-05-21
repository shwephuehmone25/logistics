<?php
session_start();
require 'database.php';

function getSiteName($siteId)
{
    global $conn;
    if ($conn === null || !$conn->ping()) {
        return "Database connection is not available.";
    }

    $sql = "SELECT name FROM sites WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $siteId);
        $stmt->execute();
        $stmt->bind_result($siteName);
        $stmt->fetch();
        $stmt->close();
        return $siteName;
    } else {
        return "Error retrieving site: " . $conn->error;
    }
}

function getRandomId($table, $conn)
{
    $sql = "SELECT id FROM $table ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    } else {
        return null;
    }
}

//auto generate jobs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'generate') {
    for ($i = 0; $i < 100; $i++) {
        $good_weight = 100;
        $good_size = 50;
        $hazardous = rand(0, 1);

        $start_date = date('Y-m-d');
        $deadline = date('Y-m-d', strtotime('+1 week'));

        // Fetch random IDs for origin, destination, and vehicle
        $origin_site_id = getRandomId('sites', $conn);
        $destination_site_id = getRandomId('sites', $conn);
        $vehicle_id = getRandomId('vehicles', $conn);

        $status = 'Outstanding';

        // Insert into database
        $sql = "INSERT INTO jobs (good_weight, good_size, hazardous, start_date, deadline, origin_site_id, destination_site_id, vehicle_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiissiiis", $good_weight, $good_size, $hazardous, $start_date, $deadline, $origin_site_id, $destination_site_id, $vehicle_id, $status);
            if (!$stmt->execute()) {
                $_SESSION['error_message'] = "Error creating job: " . $stmt->error;
                break;
            }
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
            break;
        }
        $stmt->close();
    }

    if (!isset($_SESSION['error_message'])) {
        $_SESSION['success_message'] = "Jobs were automatically created successfully!";
    }

    header("Location: jobs.php");
    exit;
}

// Handling POST requests for Create and Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $good_weight = $_POST['good_weight'];
    $good_size = $_POST['good_size'];
    $hazardous = isset($_POST['hazardous']) ? 1 : 0;
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $origin_site_id = $_POST['origin_site_id'];
    $destination_site_id = $_POST['destination_site_id'];
    $status = $_POST['status'];

    if ($action === 'create') {
        // Insert new job
        $sql = "INSERT INTO jobs (good_weight, good_size, hazardous, start_date, deadline, origin_site_id, destination_site_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    } elseif ($action === 'update') {
        // Update existing job
        $job_id = $_POST['job_id'];
        $sql = "UPDATE jobs SET good_weight = ?, good_size = ?, hazardous = ?, start_date = ?, deadline = ?, origin_site_id = ?, destination_site_id = ?, status = ? WHERE id = ?";
    }

    if ($stmt = $conn->prepare($sql)) {
        if ($action === 'update') {
            $stmt->bind_param("iiissiisi", $good_weight, $good_size, $hazardous, $start_date, $deadline, $origin_site_id, $destination_site_id, $status, $job_id);
        } else {
            $stmt->bind_param("iiissiis", $good_weight, $good_size, $hazardous, $start_date, $deadline, $origin_site_id, $destination_site_id, $status);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = $action === 'create' ? "A new job is created successfully!" : "A new job is updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "SQL preparation error: " . $conn->error;
    }
    header("Location: jobs.php");
    exit;
}

// Pagination and filtering logic
$jobsPerPage = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $jobsPerPage;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$whereClause = $filter === 'completed' ? " WHERE status = 'Completed'" : ($filter === 'outstanding' ? " WHERE status = 'Outstanding'" : "");

$total_sql = "SELECT COUNT(id) AS total FROM jobs" . $whereClause;
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $jobsPerPage);

$sql = "SELECT * FROM jobs" . $whereClause . " ORDER BY id DESC LIMIT $offset, $jobsPerPage";
$result = $conn->query($sql);

$siteQuery = "SELECT id, name FROM sites";
$siteResult = $conn->query($siteQuery);
$sites = [];
while ($row = $siteResult->fetch_assoc()) {
    $sites[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @media (max-width: 768px) {
            #sidebar {
                display: none;
            }

            #page-content-wrapper {
                margin-left: 0;
            }
        }

        .error .form-control {
            border-color: #dc3545;
        }

        .success .form-control {
            border-color: #28a745;
        }

        .errorMessage {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <div class="container-fluid">
                <div class="container mt-3">
                    <h2 class="mb-3">Jobs Management</h2>
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
                            <button class="btn btn-primary mb-3" type="button" data-bs-toggle="modal" data-bs-target="#addJobModal"><i class="fa-solid fa-circle-plus"></i> Create Job</button>
                        </div>
                        <div class="col-6">
                            <form action="jobs.php" method="post">
                                <button class="btn btn-warning mb-3" type="submit" name="action" value="generate">Generate Job</button>
                            </form>
                        </div>
                    </div>
                    <!-- Filter Section -->
                    <div class="mb-3">
                        <label for="statusFilter">Filter Jobs:</label>
                        <select id="statusFilter" class="form-select" onchange="applyFilter()">
                            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All</option>
                            <option value="completed" <?= $filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="outstanding" <?= $filter == 'outstanding' ? 'selected' : '' ?>>Outstanding</option>
                        </select>
                    </div>

                    <!-- Jobs Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Weight</th>
                                <th scope="col">Size</th>
                                <th scope="col">Hazardous</th>
                                <th scope="col">Start Date</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Origin Site</th>
                                <th scope="col">Destination Site</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                    <td><?= htmlspecialchars($row['good_weight']); ?> kg</td>
                                    <td><?= htmlspecialchars($row['good_size']); ?> cm</td>
                                    <td><?= $row['hazardous'] ? 'Yes' : 'No'; ?></td>
                                    <td><?= htmlspecialchars($row['start_date']); ?></td>
                                    <td><?= htmlspecialchars($row['deadline']); ?></td>
                                    <td><?= htmlspecialchars(getSiteName($row['origin_site_id'])); ?></td>
                                    <td><?= htmlspecialchars(getSiteName($row['destination_site_id'])); ?></td>
                                    <td><?= htmlspecialchars($row['status']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editJobModal" data-id="<?= $row['id']; ?>" onclick="setEditModalValues(<?= htmlspecialchars(json_encode($row)); ?>)"><i class="fa-solid fa-pen"></i> Edit</button>
                                        <a href="deleteJob.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this job?');"><i class="fa-solid fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="jobs.php?page=<?= $i; ?>&filter=<?= $filter; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>

                    <!-- Add Job Modal -->
                    <div class="modal fade" id="addJobModal" tabindex="-1" aria-labelledby="addJobModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="jobs.php" onsubmit="return validateForm()">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addJobModalLabel">Add New Job</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="create">
                                        <div class="mb-3">
                                            <label for="good_weight" class="form-label">Good Weight (kg)</label>
                                            <input type="number" class="form-control" id="good_weight" name="good_weight" required>
                                            <div class="invalid-feedback text-danger">Good weight is required.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="good_size" class="form-label">Good Size (cm)</label>
                                            <input type="number" class="form-control" id="good_size" name="good_size" required>
                                            <div class="invalid-feedback">Good size is required.</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hazardous" name="hazardous">
                                                <label class="form-check-label" for="hazardous">Hazardous</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                            <div class="invalid-feedback">Start date is required.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="deadline" class="form-label">Deadline</label>
                                            <input type="date" class="form-control" id="deadline" name="deadline" required>
                                            <div class="invalid-feedback">Deadline cannot be earlier than the start date.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="origin_site_id" class="form-label">Origin Site</label>
                                            <select class="form-control" id="origin_site_id" name="origin_site_id">

                                                <?php foreach ($sites as $site) : ?>
                                                    <option value="<?= $site['id']; ?>"><?= htmlspecialchars($site['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="destination_site_id" class="form-label">Destination Site</label>
                                            <select class="form-control" id="destination_site_id" name="destination_site_id">

                                                <?php foreach ($sites as $site) : ?>
                                                    <option value="<?= $site['id']; ?>"><?= htmlspecialchars($site['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="available_vehicles" class="form-label">Available Vehicles</label>
                                            <select class="form-control" id="available_vehicles" name="vehicle_id">
                                                <option value="">Select a vehicle...</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="Outstanding">Outstanding</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save Job</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Job Modal -->
                    <div class="modal fade" id="editJobModal" tabindex="-1" aria-labelledby="editJobModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="jobs.php">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editJobModalLabel">Edit Job</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="job_id" id="edit-job_id">
                                        <div class="mb-3">
                                            <label for="edit-good_weight" class="form-label">Good Weight (kg)</label>
                                            <input type="number" class="form-control" id="edit-good_weight" name="good_weight" required>
                                            <div id="invalid-feedback" class="invalid-feedback" style="display: none;">
                                                The Good weight cannot be empty.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit-good_size" class="form-label">Good Size (cm)</label>
                                            <input type="number" class="form-control" id="edit-good_size" name="good_size" required>
                                            <span class="invalid-feedback">Good size is required.</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit-hazardous" name="hazardous">
                                            <label class="form-check-label" for="edit-hazardous">Hazardous</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="edit-start_date" name="start_date" required>
                                        <span class="invalid-feedback">
                                            The Start Date is required or cannot be empty.
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-deadline" class="form-label">Deadline</label>
                                        <input type="date" class="form-control" id="edit-deadline" name="deadline" required>
                                        <span class="invalid-feedback">End date cannot be less than the start date.</span>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit-origin_site_id" class="form-label">Origin Site</label>
                                        <select class="form-control" id="edit-origin_site_id" name="origin_site_id">
                                            <?php foreach ($sites as $site) : ?>
                                                <option value="<?= $site['id']; ?>"><?= htmlspecialchars($site['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-destination_site_id" class="form-label">Destination Site</label>
                                        <select class="form-control" id="edit-destination_site_id" name="destination_site_id">
                                            <?php foreach ($sites as $site) : ?>
                                                <option value="<?= $site['id']; ?>"><?= htmlspecialchars($site['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit-available_vehicles" class="form-label">Available Vehicles</label>
                                        <select class="form-control" id="edit-available_vehicles" name="vehicle_id">
                                            <option value="">Select a vehicle...</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit-status" class="form-label">Status</label>
                                        <select class="form-control" id="edit-status" name="status">
                                            <option value="Outstanding">Outstanding</option>
                                            <option value="Completed">Completed</option>
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
        function setEditModalValues(data) {
            $('#edit-job_id').val(data.id);
            $('#edit-good_weight').val(data.good_weight);
            $('#edit-good_size').val(data.good_size);
            $('#edit-hazardous').prop('checked', data.hazardous === 1);
            $('#edit-start_date').val(data.start_date);
            $('#edit-deadline').val(data.deadline);
            $('#edit-origin_site_id').val(data.origin_site_id);
            $('#edit-destination_site_id').val(data.destination_site_id);
            $('#edit-status').val(data.status);
        }

        function applyFilter() {
            var status = $('#statusFilter').val();
            window.location.href = 'jobs.php?filter=' + status;
        }

        //fetch available vehicles
        $(document).ready(function() {
            $('#good_weight').on('change', function() {
                fetchVehicles($(this).val(), '#available_vehicles');
            });

            $('#edit-good_weight').on('change', function() {
                fetchVehicles($(this).val(), '#edit-available_vehicles');
            });

            function fetchVehicles(weight, vehicleSelectId) {
                if (weight) {
                    $.ajax({
                        url: 'fetchVehicles.php',
                        type: 'POST',
                        data: {
                            weight: weight
                        },
                        success: function(response) {
                            $(vehicleSelectId).html(response);
                        },
                        error: function() {
                            alert('Failed to retrieve vehicles.');
                            $(vehicleSelectId).html('<option value="">Select a vehicle...</option>');
                        }
                    });
                } else {
                    $(vehicleSelectId).html('<option value="">Select a vehicle...</option>');
                }
            }
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

        document.addEventListener('DOMContentLoaded', function() {
            $('#editJobModal').on('shown.bs.modal', function() {
                const editStartDate = document.getElementById('edit-start_date');
                const editDeadline = document.getElementById('edit-deadline');

                if (editStartDate.value) editDeadline.min = editStartDate.value;
                if (editDeadline.value) editStartDate.max = editDeadline.value;

                editStartDate.addEventListener('change', function() {
                    editDeadline.min = editStartDate.value;
                });

                editDeadline.addEventListener('change', function() {
                    if (editDeadline.value) {
                        editStartDate.max = editDeadline.value;
                    } else {
                        editStartDate.max = '';
                    }
                });
            });
        });

        //date picker
        const datePicker = document.getElementById("deadline");

        datePicker.min = getDate();

        function getDate() {
            let date = new Date();
            const offset = date.getTimezoneOffset();
            date = new Date(date.getTime() - (offset * 60 * 1000));
            return date.toISOString().split("T")[0];
        }

        // Function for toggling the sidebar
        $("#sidebarToggle").on('click', function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });

        //hide alert message after 5seconds
        $(document).ready(function() {
            $('.alert').delay(5000).fadeOut('slow');
        });

        //form validation
        function validateForm() {
            console.log("validateForm hit");
            let isValid = true;

            // Validate Good Weight
            const goodWeight = document.getElementById('good_weight');
            const goodWeightFeedback = goodWeight.nextElementSibling;
            console.log(goodWeightFeedback);
            if (!goodWeight.value || goodWeight.value <= 0) {
                //goodWeight.classList.add('invalid-feedback');    
                goodWeightFeedback.style.display = 'block';
                isValid = false;
            } else {
                goodWeight.classList.remove('invalid-feedback');
                goodWeightFeedback.style.display = 'none';
            }

            // Validate Good Size
            const goodSize = document.getElementById('good_size');
            const goodSizeFeedback = goodSize.nextElementSibling;
            if (!goodSize.value || goodSize.value <= 0) {
                goodSize.classList.add('invalid-feedback');
                goodSizeFeedback.style.display = 'block';
                isValid = false;
            } else {
                goodSize.classList.remove('invalid-feedback');
                goodSizeFeedback.style.display = 'none';
            }

            // Validate Start Date
            const startDate = document.getElementById('start_date');
            const startDateFeedback = startDate.nextElementSibling;
            if (!startDate.value) {
                startDate.classList.add('is-invalid');
                startDateFeedback.style.display = 'block';
                isValid = false;
            } else {
                startDate.classList.remove('is-invalid');
                startDateFeedback.style.display = 'none';
            }

            // Validate Deadline - must be after or the same as Start Date
            const deadline = document.getElementById('deadline');
            const deadlineFeedback = deadline.nextElementSibling;
            const startDateValue = new Date(startDate.value);
            const deadlineValue = new Date(deadline.value);
            if (!deadline.value || deadlineValue < startDateValue) {
                deadline.classList.add('is-invalid');
                deadlineFeedback.style.display = 'block';
                isValid = false;
            } else {
                deadline.classList.remove('is-invalid');
                deadlineFeedback.style.display = 'none';
            }

            return isValid;
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php $conn->close(); ?>