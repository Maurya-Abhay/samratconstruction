<?php
// create_admin.php

include 'lib_common.php'; // Ensure DB connection
include 'topheader.php';
include 'sidenavbar.php';

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $aadhaar = trim($_POST['aadhaar']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']); // Added gender field capture
    $city = trim($_POST['city']);     // Added city field capture
    $state = trim($_POST['state']);   // Added state field capture
    $password = trim($_POST['password']);
    $photo = $_FILES['photo'] ?? null;
    $photo_path = '';

    // Basic Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "Name, Email, and Password are required.";
        $msg_type = "warning";
    } else {
        // Check if Email Exists
        $check_stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $message = "Email address already registered.";
            $msg_type = "danger";
        } else {
            // Handle Photo Upload
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                $max_size = 2 * 1024 * 1024; // 2 MB
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                $mime = mime_content_type($photo['tmp_name']);

                if ($photo['size'] > $max_size) {
                    $message = "Photo size too large (Max 2MB).";
                    $msg_type = "warning";
                } elseif (!in_array($mime, $allowed_types)) {
                    $message = "Invalid photo format (JPG, PNG, WEBP only).";
                    $msg_type = "warning";
                } else {
                    // Local upload
                    $upload_dir = __DIR__ . '/uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
                    $file_name = 'admin_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    $target_path = $upload_dir . $file_name;
                    if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                        $photo_path = 'uploads/' . $file_name;
                    } else {
                        $message = "Photo upload failed.";
                        $msg_type = "danger";
                    }
                }
            }

            // If no errors so far (msg_type empty)
            if (empty($msg_type)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Prepare Insert Statement (including new fields gender, city, state)
                $sql = "INSERT INTO admin (name, email, phone, address, aadhaar, dob, gender, city, state, password, photo, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssss", $name, $email, $phone, $address, $aadhaar, $dob, $gender, $city, $state, $hashed_password, $photo_path);

                if ($stmt->execute()) {
                    $message = "New Admin account created successfully!";
                    $msg_type = "success";
                    // Optional: Redirect or clear form
                } else {
                    $message = "Database Error: " . $stmt->error;
                    $msg_type = "danger";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin</title>
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

        /* Profile Upload */
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
            background-color: #fff;
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

        .upload-icon:hover { transform: scale(1.1); }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-person-plus-fill text-success me-2"></i>Create Admin</h3>
        <a href="admins.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card-modern p-4">
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="row g-4">
                        <div class="col-md-4 text-center border-end">
                            <div class="profile-upload-container">
                                <img src="assets/default-avatar.png" class="profile-preview" id="avatarPreview" alt="Preview">
                                <label for="photoInput" class="upload-icon">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                                <input type="file" id="photoInput" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <p class="text-muted small mb-4">Upload Profile Picture<br>(Max 2MB, JPG/PNG)</p>
                        </div>

                        <div class="col-md-8">
                            <h5 class="mb-3 text-primary fw-bold">Account Details</h5>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="name" placeholder="Full Name" required>
                                        <label>Full Name <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                                        <label>Email Address <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="phone" placeholder="Phone">
                                        <label>Phone Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" name="password" placeholder="Pass" required>
                                        <label>Password <span class="text-danger">*</span></label>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-muted small fw-bold text-uppercase">Personal Info</h6>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" name="dob">
                                        <label>Date of Birth</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <label>Gender</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="aadhaar" placeholder="Aadhaar">
                                        <label>Aadhaar Number</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="city" placeholder="City">
                                        <label>City</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="state" placeholder="State">
                                        <label>State</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" name="address" style="height: 100px" placeholder="Address"></textarea>
                                        <label>Full Address</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="reset" class="btn btn-light me-2">Reset</button>
                                <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-lg me-1"></i> Create Account</button>
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