<?php 
include('../../config/config.php');

// Initialize variables 
 $success_message = ''; 
 $error_message = ''; 
 $edit_user = null; 

// Handle form submissions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (isset($_POST['action'])) { 
        switch ($_POST['action']) { 
            case 'add_user': 
                try { 
                    // Validation: Check if passwords match
                    if ($_POST['password'] !== $_POST['repassword']) {
                        throw new Exception("Passwords do not match.");
                    }

                    // Hash the password before storing
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("INSERT INTO tbl_users (idno, fullname, username, password, email, contactno, department, user_type) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)"); 
                    
                    $stmt->execute([ 
                        $_POST['idno'], 
                        $_POST['fullname'], 
                        $_POST['username'],
                        $hashed_password, 
                        $_POST['email'],
                        $_POST['contactno'],
                        $_POST['department'],
                        $_POST['user_type']
                    ]); 
                    $success_message = "User added successfully!"; 
                } catch(Exception $e) { 
                    $error_message = "Error adding user: " . $e->getMessage(); 
                } 
                break; 
                        
            case 'edit_user': 
                try { 
                    // Check if password is being updated
                    if (!empty($_POST['password'])) {
                        // Validation: Check if passwords match
                        if ($_POST['password'] !== $_POST['repassword']) {
                            throw new Exception("Passwords do not match.");
                        }
                        
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE tbl_users SET idno=?, fullname=?, username=?, password=?, email=?, contactno=?, department=?, user_type=? 
                                               WHERE objid=?"); 
                        $params = [
                            $_POST['idno'], 
                            $_POST['fullname'], 
                            $_POST['username'],
                            $hashed_password, 
                            $_POST['email'],
                            $_POST['contactno'],
                            $_POST['department'],
                            $_POST['user_type'],
                            $_POST['objid'] 
                        ];
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("UPDATE tbl_users SET idno=?, fullname=?, username=?, email=?, contactno=?, department=?, user_type=? 
                                               WHERE objid=?"); 
                        $params = [
                            $_POST['idno'], 
                            $_POST['fullname'], 
                            $_POST['username'],
                            $_POST['email'],
                            $_POST['contactno'],
                            $_POST['department'],
                            $_POST['user_type'],
                            $_POST['objid'] 
                        ];
                    }

                    $stmt->execute($params); 
                    $success_message = "User updated successfully!"; 
                } catch(Exception $e) { 
                    $error_message = "Error updating user: " . $e->getMessage(); 
                } 
                break; 
                        
            case 'delete_user': 
                try { 
                    $stmt = $pdo->prepare("DELETE FROM tbl_users WHERE objid=?"); 
                    $stmt->execute([$_POST['objid']]); 
                    $success_message = "User deleted successfully!"; 
                } catch(PDOException $e) { 
                    $error_message = "Error deleting user: " . $e->getMessage(); 
                } 
                break; 
        } 
    } 
} 

// Handle edit request 
if (isset($_GET['edit_id'])) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE objid=?"); 
    $stmt->execute([$_GET['edit_id']]); 
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC); 
} 

// Fetch all users 
 $stmt = $pdo->query("SELECT * FROM tbl_users ORDER BY objid DESC"); 
 $users = $stmt->fetchAll(PDO::FETCH_ASSOC); 
?>  
<!DOCTYPE html> 
<html lang="en"> 

<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management System</title> 

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="../../dist/css/font.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap4.css"> 

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Theme style -->
    <link rel="stylesheet" href="../../dist/css/adminlte.min.css">
    <link rel="icon" type="image/png" sizes="40x16" href="../../dist/img/splogo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.0/dist/sweetalert2.min.css">

    <style>
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: none;
            opacity: 0;
            transition: opacity .3s ease-in-out;
        } 

        .overlay.active {
            display: block;
            opacity: 1;
        } 

        .overlay-content {
            position: absolute;
            top: 50%;
            left: 60%;
            transform: translate(-50%, -50%);
        } 

        .imageSpinner {
            filter: invert(1);
            mix-blend-mode: multiply;
            width: 30%;
        } 

        /* User Table Styles */
        #userTable.dataTable thead th {
            background-color: #343a40;
            border-color: #4b545c;
            color: white;
            text-align: center;
        } 

        #userTable.dataTable tbody td {
            text-align: center;
            vertical-align: middle !important;
        } 

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 5px;
        } 
        
        .card-body {
            max-height: 600px;
            overflow-y: auto;
        }

        /* Fix for user image in nav */
        .navbar-nav .user-menu .user-image {
            height: 25px;
            width: 25px;
            border-radius: 50%;
            margin-right: 5px;
        }
    </style> 

</head> 

<body class="sidebar-mini layout-fixed" style="height: auto"> 

    <div class="wrapper">
        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="" src="../../dist/img/itcsologo.webp" alt="AdminLTELogo" height="60" width="60">
        </div> 

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark navbar-dark">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Fullscreen Button -->
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li> 

                <!-- User Menu -->
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <img src="../../dist/img/default.jfif" class="user-image img-circle" alt="User Image">
                        <span class="d-none d-md-inline">ADMIN USER</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <li class="user-header bg-secondary">
                            <img src="../../dist/img/default.jfif" class="img-circle elevation-2" alt="User Image">
                            <p class="mt-2">ADMIN USER</p>
                        </li>
                        <li class="user-footer">
                            <a href="#" class="btn btn-default btn-flat" onclick="logout()">Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- /.navbar --> 

        <?php include '../../pages/sidebar/sidebar.php' ?> 

        <!-- body content --> 

        <div id="body_wrapper" class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">User Management System</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Users</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div> 

            <!-- PUT THE CONTENTS HERE -->
            <div class="content">
                <!-- Display messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-check"></i> Success!</h5>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?> 
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?> 

                <div class="row">
                    <!-- Add User Card -->
                    <div class="col-lg-6">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Add User</h3>
                            </div>
                            <form id="addUserForm" method="post" action="">
                                <input type="hidden" name="action" value="add_user">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="idno">ID No.</label>
                                                <input type="text" class="form-control" id="idno" name="idno" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fullname">Full Name</label>
                                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <input type="password" class="form-control" id="password" name="password" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="repassword">Retype Password</label>
                                                <input type="password" class="form-control" id="repassword" name="repassword" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="contactno">Contact No.</label>
                                                <input type="text" class="form-control" id="contactno" name="contactno" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="department">Department</label>
                                                <input type="text" class="form-control" id="department" name="department" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="user_type">User Type</label>
                                                <select class="form-control" id="user_type" name="user_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="Student">Student</option>
                                                    <option value="Teacher">Teacher</option>
                                                    <option value="Admin">Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Add User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div> 

                    <!-- User List Card -->
                    <div class="col-lg-6">
                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                <h3 class="card-title">User Lists</h3>
                            </div>
                            <div class="card-body">
                                <table id="userTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID No.</th>
                                            <th>Full Name</th>
                                            <th>Department</th>
                                            <th>User Type</th>
                                            <th>Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($users)): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['idno']; ?></td>
                                                    <td><?php echo $user['fullname']; ?></td>
                                                    <td><?php echo $user['department']; ?></td>
                                                    <td><?php echo $user['user_type']; ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="?edit_id=<?php echo $user['objid']; ?>" class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $user['objid']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> 

                    <!-- Edit User Card -->
                    <div class="col-12">
                        <div class="card card-secondary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Edit Users</h3>
                            </div>
                            <?php if ($edit_user): ?>
                                <form id="editUserForm" method="post" action="">
                                    <input type="hidden" name="action" value="edit_user">
                                    <input type="hidden" name="objid" value="<?php echo $edit_user['objid']; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_idno">ID No.</label>
                                                    <input type="text" class="form-control" id="edit_idno" name="idno" 
                                                            value="<?php echo $edit_user['idno']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_fullname">Full Name</label>
                                                    <input type="text" class="form-control" id="edit_fullname" name="fullname" 
                                                            value="<?php echo $edit_user['fullname']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_username">Username</label>
                                                    <input type="text" class="form-control" id="edit_username" name="username" 
                                                            value="<?php echo $edit_user['username']; ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_email">Email</label>
                                                    <input type="email" class="form-control" id="edit_email" name="email" 
                                                            value="<?php echo $edit_user['email']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_password">Password</label>
                                                    <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_repassword">Retype Password</label>
                                                    <input type="password" class="form-control" id="edit_repassword" name="repassword" placeholder="Fill only if changing password">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_contactno">Contact No.</label>
                                                    <input type="text" class="form-control" id="edit_contactno" name="contactno" 
                                                            value="<?php echo $edit_user['contactno']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_department">Department</label>
                                                    <input type="text" class="form-control" id="edit_department" name="department" 
                                                            value="<?php echo $edit_user['department']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="edit_user_type">User Type</label>
                                                    <select class="form-control" id="edit_user_type" name="user_type" required>
                                                        <option value="">Select Type</option>
                                                        <option value="Student" <?php echo $edit_user['user_type'] == 'Student' ? 'selected' : ''; ?>>Student</option>
                                                        <option value="Teacher" <?php echo $edit_user['user_type'] == 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
                                                        <option value="Admin" <?php echo $edit_user['user_type'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer text-right">
                                        <a href="" class="btn btn-default">Cancel</a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Update User
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="card-body">
                                    <p class="text-muted">Select a user from the list to edit</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div> 

    </div> 

    <div class="overlay" id="myOverlay">
        <div class="overlay-content">
            <img src="../../dist/img/load.gif" class="imageSpinner" alt="" srcset="">
        </div>
    </div> 

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            All rights reserved
        </div>
        <strong>Copyright &copy; 2024 User Management System.</strong>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this user?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="post" action="" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" id="delete_objid" name="objid">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div> 

    <!-- REQUIRED SCRIPTS --> 
    <script src="../../plugins/jquery/jquery.min.js"></script>
    <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../dist/js/adminlte.min.js"></script> 
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap4.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.0/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#userTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "ordering": true,
                "info": true,
                "paging": true,
                "pageLength": 5
            }); 

            // Show success/error messages with SweetAlert
            <?php if ($success_message): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $success_message; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?> 
            
            <?php if ($error_message): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?php echo $error_message; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>
        }); 

        function confirmDelete(objid) {
            $('#delete_objid').val(objid);
            $('#deleteModal').modal('show');
        } 

        function logout() {
            Swal.fire({
                title: 'Logout',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        } 

        // Show overlay when form is submitted
        $('form').on('submit', function() {
            $('#myOverlay').addClass('active');
        });
    </script> 

</body> 

</html>