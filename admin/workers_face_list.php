<?php
// face_list.php

require_once 'lib_common.php';
include 'topheader.php';
include 'sidenavbar.php';

$msg = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    
    // Optional: Delete from Face++ API if needed (logic not included here)
    
    if (mysqli_query($conn, "DELETE FROM workers_face WHERE id=$del_id")) {
        $msg = "Face registration deleted successfully!";
        $msg_type = "success";
    } else {
        $msg = "Delete failed: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Fetch Faces
$faces = [];
$res = mysqli_query($conn, "SELECT wf.id, wf.user_id, wf.name, w.photo FROM workers_face wf LEFT JOIN workers w ON wf.user_id=w.id ORDER BY wf.id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $faces[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Faces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        .face-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #eaecf4;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-person-bounding-box text-primary me-2"></i>Registered Faces</h3>
        <a href="face_register.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Register New Face
        </a>
    </div>

    <?php if ($msg): ?>
    <script>
        Swal.fire({
            icon: '<?= $msg_type ?>',
            title: '<?= $msg_type === "success" ? "Success" : "Error" ?>',
            text: '<?= addslashes($msg) ?>',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    <?php endif; ?>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="10%" class="ps-4">ID</th>
                            <th width="15%">Photo</th>
                            <th width="30%">Worker Name</th>
                            <th width="20%">User ID</th>
                            <th width="25%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($faces) > 0): ?>
                            <?php foreach ($faces as $f): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $f['id'] ?></td>
                                <td>
                                    <?php if (!empty($f['photo'])): ?>
                                        <img src="<?= htmlspecialchars($f['photo']) ?>" class="face-thumb">
                                    <?php else: ?>
                                        <div class="face-thumb bg-light d-flex align-items-center justify-content-center text-muted border">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($f['name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">UID: <?= htmlspecialchars($f['user_id']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?= $f['id'] ?>, '<?= addslashes(htmlspecialchars($f['name'])) ?>')">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No face registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    function confirmDelete(id, name) {
        Swal.fire({
            title: 'Delete Face Registration?',
            text: `Are you sure you want to remove the face data for ${name}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `?delete_id=${id}`;
            }
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>