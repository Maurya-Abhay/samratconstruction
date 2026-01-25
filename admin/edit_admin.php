<?php
// edit_admin.php

require_once 'lib_common.php'; // Ensure DB connection
include 'topheader.php';
include 'sidenavbar.php';

$id = intval($_GET['id'] ?? 0);
$admin = null;
$error = '';

// --- 1. Fetch Admin Details ---
if ($id > 0) {
    $stmt_fetch = $conn->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $admin = $result->fetch_assoc();
    $stmt_fetch->close();
}

if (!$admin) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center">Admin not found or invalid ID. <a href="admins.php">Go Back</a></div></div>';
    include 'downfooter.php';
    exit;
}

// --- 2. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    // Email & Phone are read-only for integrity in this logic, but we capture them to be safe
    // $email = trim($_POST['email'] ?? ''); 
    // $phone = trim($_POST['phone'] ?? '');
    
    $address = trim($_POST['address'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $photo = $_FILES['photo'] ?? null;
    
    $new_photo_path = $admin['photo'];
    $set_photo_sql_part = ""; 

    // Basic Validation
    if (empty($name)) {
        $error = "Name is required.";
    } else {
        // Photo Upload Logic
        if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
            $max_size = 2 * 1024 * 1024; // 2 MB
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/webp'];
            $mime_type = mime_content_type($photo['tmp_name']);
            if ($photo['size'] > $max_size) {
                $error = "Photo size must be less than 2 MB.";
            } elseif (!in_array($mime_type, $allowed_mime_types)) {
                $error = "Only JPG, PNG, and WEBP files are allowed.";
            } else {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
                $file_name = 'admin_' . $id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $file_name;
                if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                    $new_photo_path = 'uploads/' . $file_name;
                    $set_photo_sql_part = ", photo = ?";
                } else {
                    $error = "Error uploading photo to server.";
                }
            }
        }

        if (!$error) {
            // Prepare Update Query
            $sql = "UPDATE admin SET name=?, address=?, aadhaar=?, dob=?, gender=?, city=?, state=?, status=? $set_photo_sql_part WHERE id=?";
            $stmt = $conn->prepare($sql);
            
            if ($set_photo_sql_part) {
                $stmt->bind_param("sssssssssi", $name, $address, $aadhaar, $dob, $gender, $city, $state, $status, $new_photo_path, $id);
            } else {
                $stmt->bind_param("ssssssssi", $name, $address, $aadhaar, $dob, $gender, $city, $state, $status, $id);
            }

            if ($stmt->execute()) {
                echo "<script>window.location.href='admin_detail.php?id=$id&update=success';</script>";
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Admin Updated',text:'Admin updated successfully.',showConfirmButton:false,timer:2000});</script>";
                exit();
            } else {
                $error = "Database update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin Profile</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
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

        .profile-upload-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-preview {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #eaecf4;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .upload-icon {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: transform 0.2s;
        }

        .upload-icon:hover {
            transform: scale(1.1);
        }

        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Admin Profile</h3>
        <a href="admin_detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-modern p-4">
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="row g-4">
                        <!-- Left Column: Photo & Basic Status -->
                        <div class="col-md-4 text-center border-end">
                            <div class="profile-upload-container">
                                <?php 
                                    $imgSrc = !empty($admin['photo']) ? htmlspecialchars($admin['photo']) : 'https://ui-avatars.com/api/?name='.urlencode($admin['name']).'&background=random';
                                ?>
                                <img src="<?= $imgSrc ?>" class="profile-preview" id="avatarPreview">
                                <label for="photoInput" class="upload-icon">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                                <input type="file" id="photoInput" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <p class="text-muted small mb-4">Click camera icon to change photo.<br>Max 2MB (JPG, PNG, WEBP)</p>

                            <div class="text-start px-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Account Status</label>
                                <select class="form-select mb-3" name="status">
                                    <option value="active" <?= ($admin['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($admin['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= ($admin['status'] == 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                </select>

                                <div class="alert alert-light border small">
                                    <strong>Note:</strong> Email and Phone number cannot be changed here for security reasons. Contact Super Admin for critical updates.
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Details Form -->
                        <div class="col-md-8">
                            <h5 class="mb-3 text-primary fw-bold">Personal Information</h5>
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                                        <label>Full Name</label>
                                    </div>
                                </div>
                                
                                <!-- Read Only Fields -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($admin['email']) ?>" readonly disabled>
                                        <label>Email Address (Locked)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($admin['phone']) ?>" readonly disabled>
                                        <label>Phone Number (Locked)</label>
                                    </div>
                                </div>

                                <!-- Identity -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="aadhaar" value="<?= htmlspecialchars($admin['aadhaar'] ?? '') ?>">
                                        <label>Aadhaar Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="gender">
                                            <option value="">Select</option>
                                            <option value="Male" <?= ($admin['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= ($admin['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= ($admin['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                                        </select>
                                        <label>Gender</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($admin['dob'] ?? '') ?>">
                                        <label>Date of Birth</label>
                                    </div>
                                </div>
                                
                                <!-- Address -->
                                <div class="col-12 mt-4">
                                    <h6 class="text-muted small fw-bold text-uppercase">Address Details</h6>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($admin['address'] ?? '') ?>">
                                        <label>Street Address</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($admin['city'] ?? '') ?>">
                                        <label>City</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="state" value="<?= htmlspecialchars($admin['state'] ?? '') ?>">
                                        <label>State</label>
                                    </div>
                                </div>

                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <a href="admin_detail.php?id=<?= $id ?>" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Changes</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('avatarPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>