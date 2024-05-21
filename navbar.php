<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Apparition Logistics - Dashboard</title>

    <!-- Custom fonts for this template-->

    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body id="page-top">
    <!-- Page Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <!-- <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form> -->

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                        </li>

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <!-- Counter - Messages -->
                                <span class="badge badge-danger badge-counter">7</span>
                            </a>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <div class="dropdown">
                                <button class="btn  dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="mr-2 d-none d-lg-inline text-gray-600 small">Admin</span>
                                        <img class="img-profile rounded-circle" src="data:image/png;base64,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">
                                    </a>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </li>
                    </ul>

                </nav>
                <!-- End of Topbar -->
        </div>
        <!-- End of Content Wrapper -->
        
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>
</body>

</html>