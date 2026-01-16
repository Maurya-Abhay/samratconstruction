<?php
// services.php

include "lib_common.php";

$uploadPath = "uploads/";

// --- Logic: Fetch for Edit ---
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM services WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $edit = $result->fetch_assoc();
    }
}

// --- Logic: Insert / Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = $conn->real_escape_string($_POST['service_name']);
    $short_desc = $conn->real_escape_string($_POST['short_desc']);
    $long_desc = $conn->real_escape_string($_POST['long_desc']);
    $features = $conn->real_escape_string($_POST['features']);
    $pricing = $conn->real_escape_string($_POST['pricing']);
    $tags = $conn->real_escape_string($_POST['tags']);
    $testimonial = $conn->real_escape_string($_POST['testimonial']);
    $cta_label = $conn->real_escape_string($_POST['cta_label']);
    $cta_link = $conn->real_escape_string($_POST['cta_link']);
    $faq = $conn->real_escape_string($_POST['faq']);
    
    $imageName = '';

    // File Upload Logic
    if (!empty($_FILES['service_photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['service_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $tmp = $_FILES['service_photo']['tmp_name'];

        if (!in_array($ext, $allowed)) {
            $error = 'Only JPG, JPEG, PNG, WEBP, GIF allowed.';
        } elseif (getimagesize($tmp) === false) {
            $error = 'Invalid image file.';
        } elseif (filesize($tmp) > 2*1024*1024) {
            $error = 'File size must be less than 2MB.';
        } else {
            $contents = file_get_contents($tmp);
            if (strpos($contents, '<?php') !== false) {
                $error = 'Malware detected.';
            } else {
                if (!file_exists($uploadPath)) mkdir($uploadPath, 0777, true);
                $imageName = time() . '_' . basename($_FILES['service_photo']['name']);
                $target_file = $uploadPath . $imageName;
                move_uploaded_file($_FILES['service_photo']['tmp_name'], $target_file);
            }
        }
    }

    // Update or Insert
    if (!empty($_POST['update'])) {
        $id = intval($_POST['update']);
        if (!$imageName && isset($edit['service_photo'])) {
            $imageName = $edit['service_photo']; // Keep old image
        }
        $stmt = $conn->prepare("UPDATE services SET service_name=?, short_desc=?, long_desc=?, service_photo=?, features=?, pricing=?, tags=?, testimonial=?, cta_label=?, cta_link=?, faq=? WHERE id=?");
        $stmt->bind_param("sssssssssssi", $service_name, $short_desc, $long_desc, $imageName, $features, $pricing, $tags, $testimonial, $cta_label, $cta_link, $faq, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO services (service_name, short_desc, long_desc, service_photo, features, pricing, tags, testimonial, cta_label, cta_link, faq) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $service_name, $short_desc, $long_desc, $imageName, $features, $pricing, $tags, $testimonial, $cta_label, $cta_link, $faq);
    }

    if ($stmt->execute()) {
        header("Location: services.php");
        exit();
    }
}

// --- Logic: Delete ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Optional: Delete physical image file here if needed
    $conn->query("DELETE FROM services WHERE id = $id");
    header("Location: services.php");
    exit();
}

$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
$total_services = $services->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Card */
        .card-modern {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
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
        }

        /* Thumbnails */
        .service-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #eaecf4;
        }

        /* Table */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f1f3f9;
            color: #5a5c69;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }
        .table-hover tbody tr:hover {
            background-color: #fafbfc;
        }

        /* Form Styling */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > textarea.form-control { height: auto; min-height: 100px; }
        .form-floating > label { padding-top: 0.6rem; }
        
        .edit-mode-banner {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            color: #856404;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

<?php include 'topheader.php'; ?>
<?php include 'sidenavbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-tools text-primary me-2"></i>Services</h3>
        <?php if (!$edit): ?>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Service
            </button>
        <?php endif; ?>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="summary-box">
                <div>
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8;">Total Services</h6>
                    <h2 class="mb-0 fw-bold"><?= $total_services ?></h2>
                </div>
                <div class="fs-1" style="opacity: 0.3;"><i class="bi bi-grid-fill"></i></div>
            </div>
        </div>
    </div>

    <?php if ($edit): ?>
    <div class="card-modern p-4 mb-4 border-warning border-start border-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-warning fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Service: <?= htmlspecialchars($edit['service_name']) ?></h5>
            <a href="services.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update" value="<?= $edit['id'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="service_name" value="<?= htmlspecialchars($edit['service_name']) ?>" required>
                        <label>Service Name</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="tags" value="<?= htmlspecialchars($edit['tags']) ?>" placeholder="Tags">
                        <label>Tags (comma separated)</label>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-floating">
                        <textarea class="form-control" name="short_desc" style="height: 100px" required><?= htmlspecialchars($edit['short_desc']) ?></textarea>
                        <label>Short Description</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <textarea class="form-control" name="features" style="height: 100px"><?= htmlspecialchars($edit['features']) ?></textarea>
                        <label>Features (one per line)</label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control" name="long_desc" style="height: 150px"><?= htmlspecialchars($edit['long_desc']) ?></textarea>
                        <label>Long Description</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="pricing" value="<?= htmlspecialchars($edit['pricing']) ?>">
                        <label>Pricing info</label>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <label class="form-label small text-muted">Update Photo</label>
                            <input type="file" name="service_photo" class="form-control">
                        </div>
                        <?php if (!empty($edit['service_photo'])): ?>
                            <div class="ms-3 text-center">
                                <span class="d-block small text-muted mb-1">Current</span>
                                <img src="uploads/<?= htmlspecialchars($edit['service_photo']) ?>" class="service-thumb">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="accordion" id="accordionEditExtras">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditExtras">
                                    Additional Details (Testimonials, CTA, FAQ)
                                </button>
                            </h2>
                            <div id="collapseEditExtras" class="accordion-collapse collapse" data-bs-parent="#accordionEditExtras">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="cta_label" value="<?= htmlspecialchars($edit['cta_label']) ?>">
                                                <label>CTA Button Label</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="cta_link" value="<?= htmlspecialchars($edit['cta_link']) ?>">
                                                <label>CTA Link</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <textarea class="form-control" name="testimonial" style="height: 120px"><?= htmlspecialchars($edit['testimonial']) ?></textarea>
                                                <label>Customer Testimonial</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <textarea class="form-control" name="faq" style="height: 120px"><?= htmlspecialchars($edit['faq']) ?></textarea>
                                                <label>FAQ (Q: .. A: ..)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-warning px-4"><i class="bi bi-check-circle me-1"></i> Update Changes</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="card-modern">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="ps-4">ID</th>
                            <th width="10%">Image</th>
                            <th width="20%">Service Name</th>
                            <th width="35%">Description</th>
                            <th width="15%">Pricing</th>
                            <th width="15%" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($services->num_rows > 0): ?>
                            <?php $services->data_seek(0); while ($row = $services->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">#<?= $row['id'] ?></td>
                                <td>
                                    <?php if (!empty($row['service_photo'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($row['service_photo']) ?>" class="service-thumb">
                                    <?php else: ?>
                                        <div class="service-thumb bg-light d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['service_name']) ?></div>
                                    <small class="text-muted"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($row['tags'] ?? 'No tags') ?></small>
                                </td>
                                <td>
                                    <div class="text-muted small text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($row['short_desc']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success px-2">
                                        <?= htmlspecialchars($row['pricing'] ?: 'N/A') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this service?')" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No services found. Add one to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="text-primary fw-bold mb-3">Basic Information</h6>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="service_name" placeholder="Name" required>
                                <label>Service Name *</label>
                            </div>
                            <div class="form-floating mb-3">
                                <textarea class="form-control" name="short_desc" style="height: 100px" placeholder="Short Desc" required></textarea>
                                <label>Short Description *</label>
                            </div>
                            <div class="form-floating mb-3">
                                <textarea class="form-control" name="long_desc" style="height: 150px" placeholder="Long Desc"></textarea>
                                <label>Long Description</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="tags" placeholder="Tags">
                                <label>Tags (comma separated)</label>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h6 class="text-primary fw-bold mb-3">Media & Details</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Service Photo</label>
                                <input type="file" name="service_photo" class="form-control">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="pricing" placeholder="Price">
                                        <label>Pricing</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="cta_label" placeholder="Button">
                                        <label>CTA Label</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <textarea class="form-control" name="features" style="height: 100px" placeholder="Features"></textarea>
                                <label>Features (one per line)</label>
                            </div>

                            <div class="accordion" id="accordionAddExtras">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddExtras">
                                            More (FAQ, Testimonials)
                                        </button>
                                    </h2>
                                    <div id="collapseAddExtras" class="accordion-collapse collapse" data-bs-parent="#accordionAddExtras">
                                        <div class="accordion-body">
                                            <div class="form-floating mb-2">
                                                <input type="text" class="form-control" name="cta_link" placeholder="Link">
                                                <label>CTA Link</label>
                                            </div>
                                            <div class="form-floating mb-2">
                                                <textarea class="form-control" name="testimonial" style="height: 80px"></textarea>
                                                <label>Testimonial</label>
                                            </div>
                                            <div class="form-floating">
                                                <textarea class="form-control" name="faq" style="height: 80px"></textarea>
                                                <label>FAQ</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save Service</button>
                </div>
            </form>
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