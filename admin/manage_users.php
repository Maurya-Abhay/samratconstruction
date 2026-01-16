<?php
// manage_users.php

require_once 'database.php'; // Ensure DB connection is included
include 'topheader.php';
include 'sidenavbar.php';

$message = '';
$error = '';

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password_raw = trim($_POST['password'] ?? '');
    $image = $_FILES['image'] ?? null;

    if (!$name || !$email || !$phone || !$password_raw || !$image['name']) {
        $error = 'All fields including image are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $error = 'Invalid phone number.';
    } elseif (strlen($password_raw) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password_raw)) {
        $error = 'Password must be at least 8 characters and contain at least one special character.';
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM attendence_users WHERE email = ? OR phone = ?");
        $stmt_check->bind_param("ss", $email, $phone);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = 'Email or phone already exists.';
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!function_exists('csrf_token')) {
        function csrf_token() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }
        } else {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('user_') . '.' . $ext;
            
            if (move_uploaded_file($image['tmp_name'], $upload_dir . $image_name)) {
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO attendence_users (name, email, phone, password, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $phone, $password, $image_name);

                if ($stmt->execute()) {
                    $message = 'User added successfully!';
                } else {
                    $error = 'Database Error: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Failed to upload image.';
            }
        }
        $stmt_check->close();
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt_img = $conn->prepare("SELECT image FROM attendence_users WHERE id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $result_img = $stmt_img->get_result();
    if ($img_row = $result_img->fetch_assoc()) {
        $img_file = 'uploads/' . $img_row['image'];
        if (file_exists($img_file)) unlink($img_file);
    }
    $stmt_img->close();
    $stmt_del = $conn->prepare("DELETE FROM attendence_users WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    $stmt_del->execute();
    $stmt_del->close();
    header("Location: manage_users.php");
    exit();
}

// Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $image = $_FILES['image'] ?? null;

    if ($image && $image['name']) {
        $upload_dir = 'uploads/';
        $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('user_') . '.' . $ext;
        if (move_uploaded_file($image['tmp_name'], $upload_dir . $image_name)) {
            $stmt = $conn->prepare("UPDATE attendence_users SET name=?, email=?, phone=?, image=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $image_name, $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE attendence_users SET name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $id);
        $stmt->execute();
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!function_exists('csrf_token')) {
        function csrf_token() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }
        $stmt->close();
    }
    header("Location: manage_users.php");
    exit();
}

// Fetch Users
$users = $conn->query("SELECT * FROM attendence_users ORDER BY id DESC");
$total_users = $users->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Card */
        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
            margin-bottom: 20px;
        }

        /* Stats Cards */
        .summary-box {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow-x: auto !important;
            overflow-y: auto !important;
            max-height: 600px;
        }
        .table {
            min-width: 900px;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaecf4;
        }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-people-fill text-primary me-2"></i>Attendance Users</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-1"></i> Add User
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($message) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="summary-box">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8;">Total Users</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_users ?></h2>
                </div>
                <div class="fs-1" style="opacity: 0.3;"><i class="bi bi-person-lines-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="10%">Image</th>
                            <th width="25%">Name</th>
                            <th width="25%">Email</th>
                            <th width="20%">Phone</th>
                               <th width="15%">Salary Type</th>
                               <th width="15%">Salary Amount</th>
                               <th width="15%">Attendance Total</th>
                               <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_users > 0): ?>
                            <?php $users->data_seek(0); while ($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                <td>
                                    <?php 
                                        $img = !empty($row['image']) ? 'uploads/'.htmlspecialchars($row['image']) : 'assets/default-avatar.png'; 
                                    ?>
                                    <img src="<?= $img ?>" class="user-avatar" alt="Avatar">
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                   <td><?= htmlspecialchars($row['phone']) ?></td>
                                   <td><?= ucfirst($row['salary_type'] ?? '') ?></td>
                                   <td>₹<?= number_format($row['salary'] ?? 0, 2) ?></td>
                                   <td><?= ($row['attendance'] ?? 0) ?></td>
                                   <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?= $row['id'] ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                                     <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                                         onclick="event.preventDefault(); showDeleteUserSwal(this);" title="Delete">
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                            function showDeleteUserSwal(el) {
                                                Swal.fire({
                                                    title: 'Delete User?',
                                                    text: 'Are you sure you want to delete this user?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Yes, delete',
                                                    cancelButtonText: 'Cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        window.location.href = el.getAttribute('href');
                                                    }
                                                });
                                            }
                                            </script>
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="edit_user" value="1">
                                                
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                                                    <label>Full Name</label>
                                                </div>
                                                <div class="form-floating mb-3">
                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
                                                    <label>Email Address</label>
                                                </div>
                                                <div class="form-floating mb-3">
                                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required>
                                                    <label>Phone Number</label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small text-muted">Change Photo</label>
                                                    <input type="file" class="form-control" name="image">
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning px-4">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No users found. Add one to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Attendance User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="add_user" value="1">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="name" placeholder="Name" required>
                        <label>Full Name *</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                        <label>Email Address *</label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="tel" class="form-control" name="phone" placeholder="Phone" required>
                                <label>Phone *</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" name="password" placeholder="Pass" required>
                                <label>Password *</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">User Photo *</label>
                        <input type="file" class="form-control" name="image" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>