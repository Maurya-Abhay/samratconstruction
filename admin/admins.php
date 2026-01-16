<?php
// admins.php

// --- 0. Define PRIMARY_ADMIN_ID if not already defined ---
if (!defined('PRIMARY_ADMIN_ID')) {
    define('PRIMARY_ADMIN_ID', 1); // Change 1 to your actual primary admin ID if needed
}

require_once 'lib_common.php'; // Ensure DB connection is here
include 'topheader.php';
include 'sidenavbar.php';

$message = '';
$msg_type = '';

// --- Handle Delete ---
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    $current_user_id = $_SESSION['user_id'] ?? 0; // Assuming session stores admin ID

    // Permission Check: Only Primary Admin can delete
    if ($current_user_id != PRIMARY_ADMIN_ID) {
         $message = "Permission Denied: Only the Primary Admin can delete accounts.";
         $msg_type = "danger";
    } elseif ($delete_id) {
        // Prevent Self-Deletion / Primary Admin Deletion
        if ($delete_id == PRIMARY_ADMIN_ID) {
            $message = "Critical: The Primary Admin account cannot be deleted.";
            $msg_type = "danger";
        } else {
            // Execute Delete
            $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                $message = "Admin account deleted successfully.";
                $msg_type = "success";
            } else {
                $message = "Database Error: " . $conn->error;
                $msg_type = "danger";
            }
            $stmt->close();
        }
    }
}

// --- Fetch Admins ---
$admins_result = $conn->query("SELECT * FROM admin ORDER BY id ASC");
$total_admins = $admins_result ? $admins_result->num_rows : 0;

// Current logged-in user ID for permission checks in the loop
$current_user_id = $_SESSION['user_id'] ?? 0; 
$is_super_admin = ($current_user_id == PRIMARY_ADMIN_ID);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management</title>
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

        /* Summary Box */
        .summary-box {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.2);
            margin-bottom: 20px;
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaecf4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background-color: #f8f9fc;
            color: var(--primary-color);
        }

        .badge-role {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Admin Management</h3>
        
        <?php if ($is_super_admin): ?>
            <a href="create_admin.php" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus-fill me-1"></i> Create New Admin
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 col-sm-6">
            <div class="summary-box">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8;">Total Admins</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_admins ?></h2>
                </div>
                <div class="fs-1" style="opacity: 0.3;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="10%">Photo</th>
                            <th width="20%">Name</th>
                            <th width="25%">Email</th>
                            <th width="15%">Phone</th>
                            <th width="10%">Role/Status</th>
                            <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_admins > 0): ?>
                            <?php while ($row = $admins_result->fetch_assoc()): 
                                $is_primary = ($row['id'] == PRIMARY_ADMIN_ID);
                            ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                <td>
                                    <?php if (!empty($row['photo'])): ?>
                                        <img src="<?= htmlspecialchars($row['photo']) ?>" class="admin-avatar">
                                    <?php else: ?>
                                        <div class="admin-avatar">
                                            <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td>
                                    <?php if ($is_primary): ?>
                                        <span class="badge bg-primary badge-role">Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark badge-role">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="admin_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($is_super_admin): ?>
                                            <?php if (!$is_primary): ?>
                                                <a href="edit_admin.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to permanently delete admin: <?= addslashes($row['name']) ?>?');" 
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light text-muted" disabled title="Super Admin Protected"><i class="bi bi-lock-fill"></i></button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No admins found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-refresh every 1 minute for admin panel
setInterval(function() {
    location.reload();
}, 60000);
</script>
</body>
</html>