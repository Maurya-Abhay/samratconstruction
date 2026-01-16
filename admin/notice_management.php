<?php
// notices.php

require_once 'database.php';
include 'topheader.php';
include 'sidenavbar.php';

// Resolve Admin ID
$admin_email = $_SESSION['email'] ?? '';
$current_admin_id = 0;

if ($admin_email) {
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_admin_id = (int)$row['id'];
    }
    $stmt->close();
}

$success_message = '';
$error_message = '';

// Handle Messages from Redirects
if (isset($_SESSION['status_message'])) {
    if ($_SESSION['status_message']['type'] == 'success') {
        $success_message = $_SESSION['status_message']['message'];
    } else {
        $error_message = $_SESSION['status_message']['message'];
    }
    unset($_SESSION['status_message']);
}

// --- HANDLE POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add Notice
    if (isset($_POST['add_notice'])) {
        $title = trim($_POST['notice_title'] ?? '');
        $content = trim($_POST['notice_content'] ?? '');
        $priority = $_POST['priority'] ?? 'Medium';
        $is_active = $_POST['is_active'] ?? 'Yes';
        
        if ($title && $content) {
            $stmt = $conn->prepare("INSERT INTO notices (title, content, priority, is_active, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $content, $priority, $is_active, $current_admin_id);
            
            if ($stmt->execute()) {
                $_SESSION['status_message'] = ['type' => 'success', 'message' => 'Notice created successfully!'];
                header("Location: notices.php");
                exit();
            } else {
                $error_message = "Error creating notice: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Update Notice
    if (isset($_POST['update_notice'])) {
        $id = (int)$_POST['notice_id'];
        $title = trim($_POST['notice_title'] ?? '');
        $content = trim($_POST['notice_content'] ?? '');
        $priority = $_POST['priority'];
        $is_active = $_POST['is_active'];
        
        $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, priority=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $content, $priority, $is_active, $id);
        
        if ($stmt->execute()) {
            $_SESSION['status_message'] = ['type' => 'success', 'message' => 'Notice updated successfully!'];
            header("Location: notices.php");
            exit();
        } else {
            $error_message = "Error updating notice: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Delete Notice
    if (isset($_POST['delete_notice'])) {
        $id = (int)$_POST['notice_id'];
        $stmt = $conn->prepare("DELETE FROM notices WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['status_message'] = ['type' => 'success', 'message' => 'Notice deleted successfully!'];
            header("Location: notices.php");
            exit();
        } else {
            $error_message = "Error deleting notice: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Toggle Status
    if (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['notice_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE notices SET is_active=? WHERE id=?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['status_message'] = ['type' => 'success', 'message' => 'Status updated!'];
            header("Location: notices.php");
            exit();
        } else {
            $error_message = "Error updating status.";
        }
        $stmt->close();
    }
}

// Fetch Data
$notices = $conn->query("SELECT n.*, a.name as created_by_name FROM notices n LEFT JOIN admin a ON n.created_by = a.id ORDER BY n.created_at DESC");

// Stats
$total_notices = (int)($conn->query("SELECT COUNT(*) as count FROM notices")->fetch_assoc()['count'] ?? 0);
$active_notices = (int)($conn->query("SELECT COUNT(*) as count FROM notices WHERE is_active='Yes'")->fetch_assoc()['count'] ?? 0);
$urgent_notices = (int)($conn->query("SELECT COUNT(*) as count FROM notices WHERE priority='Urgent' AND is_active='Yes'")->fetch_assoc()['count'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management</title>
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
        .stats-card {
            border: none;
            border-radius: 12px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .bg-gradient-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
        .bg-gradient-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
        .bg-gradient-danger { background: linear-gradient(135deg, #e74a3b, #be2617); }
        
        .stats-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.3;
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

        /* Priority Badges */
        .badge-priority { font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; }
        .priority-Low { background-color: #e2e6ea; color: #495057; }
        .priority-Medium { background-color: #fff3cd; color: #856404; }
        .priority-High { background-color: #ffeeba; color: #856404; font-weight: bold; }
        .priority-Urgent { background-color: #f8d7da; color: #721c24; font-weight: bold; animation: pulse 2s infinite; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-megaphone-fill text-primary me-2"></i>Notices</h3>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
            <i class="bi bi-plus-lg me-1"></i> Create Notice
        </button>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success_message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error_message) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stats-card bg-gradient-primary">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Total Notices</div>
                    <div class="h2 mb-0 fw-bold"><?= $total_notices ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-gradient-success">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Active</div>
                    <div class="h2 mb-0 fw-bold"><?= $active_notices ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-broadcast"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-gradient-danger">
                <div>
                    <div class="small fw-bold text-uppercase mb-1" style="opacity:0.8">Urgent</div>
                    <div class="h2 mb-0 fw-bold"><?= $urgent_notices ?></div>
                </div>
                <div class="stats-icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="5%" class="ps-4">#</th>
                            <th width="25%">Title</th>
                            <th width="30%">Content Preview</th>
                            <th width="10%">Priority</th>
                            <th width="10%">Status</th>
                            <th width="10%">Date</th>
                            <th width="10%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notices && $notices->num_rows > 0): ?>
                            <?php $i=1; while($row = $notices->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $i++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                    <small class="text-muted">By: <?= htmlspecialchars($row['created_by_name'] ?? 'System') ?></small>
                                </td>
                                <td>
                                    <div class="text-muted small text-truncate" style="max-width: 350px;">
                                        <?= htmlspecialchars($row['content']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-priority priority-<?= htmlspecialchars($row['priority']) ?>">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['is_active'] == 'Yes'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('d M Y', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>"><i class="bi bi-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editNotice(<?= $row['id'] ?>, '<?= addslashes($row['title']) ?>', '<?= addslashes($row['content']) ?>', '<?= $row['priority'] ?>', '<?= $row['is_active'] ?>')"
                                                data-bs-toggle="modal" data-bs-target="#editNoticeModal"><i class="bi bi-pencil"></i></button>
                                        
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="notice_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $row['is_active'] == 'Yes' ? 'No' : 'Yes' ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                                <i class="bi bi-<?= $row['is_active'] == 'Yes' ? 'pause-fill' : 'play-fill' ?>"></i>
                                            </button>
                                        </form>

                                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); showDeleteNoticeSwal(this);">
                                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                            <script>
                                            function showDeleteNoticeSwal(form) {
                                                Swal.fire({
                                                    title: 'Delete Notice?',
                                                    text: 'Delete this notice?',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#aaa',
                                                    confirmButtonText: 'Yes, delete',
                                                    cancelButtonText: 'Cancel',
                                                    reverseButtons: true
                                                }).then((result) => {
                                                    if (result.isConfirmed) {
                                                        form.submit();
                                                    }
                                                });
                                            }
                                            </script>
                                            <input type="hidden" name="notice_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_notice" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title"><?= htmlspecialchars($row['title']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p style="white-space: pre-wrap;"><?= htmlspecialchars($row['content']) ?></p>
                                            <hr>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Priority: <strong><?= $row['priority'] ?></strong></span>
                                                <span>Date: <?= date('d M Y h:i A', strtotime($row['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No notices found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Create Notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="add_notice" value="1">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="notice_title" placeholder="Title" required>
                        <label>Notice Title *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="notice_content" style="height: 150px" placeholder="Content" required></textarea>
                        <label>Content *</label>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-floating">
                                <select class="form-select" name="priority">
                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                                <label>Priority</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <select class="form-select" name="is_active">
                                    <option value="Yes">Active</option>
                                    <option value="No">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Publish Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="update_notice" value="1">
                    <input type="hidden" name="notice_id" id="edit_notice_id">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="notice_title" id="edit_notice_title" placeholder="Title" required>
                        <label>Notice Title *</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="notice_content" id="edit_notice_content" style="height: 150px" placeholder="Content" required></textarea>
                        <label>Content *</label>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-floating">
                                <select class="form-select" name="priority" id="edit_priority">
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                                <label>Priority</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <select class="form-select" name="is_active" id="edit_is_active">
                                    <option value="Yes">Active</option>
                                    <option value="No">Inactive</option>
                                </select>
                                <label>Status</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editNotice(id, title, content, priority, isActive) {
    document.getElementById('edit_notice_id').value = id;
    document.getElementById('edit_notice_title').value = title;
    document.getElementById('edit_notice_content').value = content;
    document.getElementById('edit_priority').value = priority;
    document.getElementById('edit_is_active').value = isActive;
}
</script>

</body>
</html>