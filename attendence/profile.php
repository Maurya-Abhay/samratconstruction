<?php
session_start();
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }

include "../admin/database.php";



// Check if user is logged in

if (!isset($_SESSION['attendence_email'])) {

    header('Location: login.php');

    exit();

}



$email = $_SESSION['attendence_email'];



// Handle form submission for profile update (name, address, and photo)

if ($_POST && isset($_POST['update_profile'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!function_exists('csrf_token')) {
        function csrf_token() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }
    $name = mysqli_real_escape_string($conn, $_POST['name']);

    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $photoUpdate = '';

    

    // Handle photo upload (Cloudinary migration)

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        $maxSize = 1 * 1024 * 1024; // 1MB

        

        $fileType = $_FILES['photo']['type'];

        $fileSize = $_FILES['photo']['size'];

        

        if (!in_array($fileType, $allowedTypes)) {

            $error_message = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";

        } elseif ($fileSize > $maxSize) {

            $error_message = "File size too large. Maximum 1MB allowed.";

        } else {

            $cloudinary_url = upload_to_cloudinary($_FILES['photo']['tmp_name'], 'attendence_photos');

            if ($cloudinary_url) {

                $photoUpdate = ", image='" . $cloudinary_url . "'";

            } else {

                $error_message = "Cloudinary upload failed.";

            }

        }

    }

    

    if (!isset($error_message)) {

        $stmt = $conn->prepare("UPDATE attendence_users SET name=?, address=? $photoUpdate WHERE email=?");

        if ($photoUpdate) {

            $stmt->bind_param("sss", $name, $address, $email);

        } else {

            $stmt->bind_param("sss", $name, $address, $email);

        }

        if ($stmt->execute()) {

            $success_message = "Profile updated successfully!";

            // Refresh user data

            $user = $conn->query("SELECT * FROM attendence_users WHERE email='$email'")->fetch_assoc();

            $photoPath = (!empty($user['image']) && preg_match('#^https?://#', $user['image'])) ? $user['image'] : '';

        } else {

            $error_message = "Error updating profile: " . $conn->error;

        }

        $stmt->close();

    }

}



// Fetch user info

$user = $conn->query("SELECT * FROM attendence_users WHERE email='$email'")->fetch_assoc();



if (!$user) {

    echo '<div class="alert alert-danger">User not found!</div>';

    exit();

}



$photoPath = (!empty($user['image']) && preg_match('#^https?://#', $user['image'])) ? $user['image'] : '';

include 'header.php';
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile - Attendance System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
body { background: #fff !important; }
.glass-card, .profile-card, .container, .card, .form-control, .form-select { background: #fff !important; color: #222 !important; }

        .profile-img {

            width: 150px;

            height: 150px;

            object-fit: cover;

            border: 4px solid #0d6efd;

            box-shadow: 0 4px 12px rgba(0,0,0,0.15);

        }

        .profile-card {

            background: #fff;

            border-radius: 16px;

            box-shadow: 0 4px 16px rgba(0,0,0,0.08);

            overflow: hidden;

        }

        .profile-header {

            background: linear-gradient(135deg, #0d6efd, #6610f2);

            color: white;

            padding: 2rem;

            text-align: center;

            position: relative;

        }

        .back-btn {

            position: absolute;

            top: 1rem;

            left: 1rem;

            color: white;

            text-decoration: none;

        }

        .form-floating label {

            color: #6c757d;

        }

        .btn-edit {

            border-radius: 50px;

            padding: 0.5rem 1.5rem;

        }

    </style>

</head>

<body>



<div class="container py-3">

    <?php if (isset($success_message)): ?>

        <div class="alert alert-success alert-dismissible fade show" role="alert">

            <i class="bi bi-check-circle me-2"></i><?= $success_message ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>



    <?php if (isset($error_message)): ?>

        <div class="alert alert-danger alert-dismissible fade show" role="alert">

            <i class="bi bi-exclamation-triangle me-2"></i><?= $error_message ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>



    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="profile-card">

                <!-- Profile Header -->

                <div class="profile-header">

                    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>

                    <div class="position-relative d-inline-block mb-3">

                        <img id="profileImage" src="<?= !empty($photoPath) ? htmlspecialchars($photoPath) : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0d6efd&color=fff&size=150' ?>" 

                             class="rounded-circle profile-img" alt="Profile Photo">

                        <label for="photoInput" id="changePhotoBtn" class="position-absolute bottom-0 end-0 btn btn-light btn-sm rounded-circle" style="width: 40px; height: 40px; padding: 0; display: none; cursor: pointer; border: 2px solid #0d6efd;">

                            <i class="bi bi-camera-fill text-primary"></i>

                        </label>

                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;">

                    </div>

                    <h3 class="mb-1"><?= htmlspecialchars($user['name']) ?></h3>

                    <p class="mb-0 opacity-75">User ID: <?= htmlspecialchars($user['id'] ?? '-') ?></p>

                    <p class="mb-0 opacity-75"><span class="badge bg-success"><?= htmlspecialchars($user['status'] ?? 'Active') ?></span></p>

                </div>



                <!-- Profile Content -->

                <div class="p-4">

                    <!-- View Mode -->

                    <div id="viewMode">

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <h5 class="text-primary mb-0"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h5>

                            <button type="button" class="btn btn-outline-primary btn-edit" onclick="toggleEditMode(true)">

                                <i class="bi bi-pencil-square me-2"></i>Edit Profile

                            </button>

                        </div>



                        <div class="row g-3">

                            <div class="col-md-6">

                                <div class="card border-0 bg-light">

                                    <div class="card-body">

                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-person me-2"></i>Full Name</h6>

                                        <p class="card-text fw-semibold"><?= htmlspecialchars($user['name']) ?></p>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="card border-0 bg-light">

                                    <div class="card-body">

                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-envelope me-2"></i>Email</h6>

                                        <p class="card-text fw-semibold"><?= htmlspecialchars($user['email']) ?></p>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="card border-0 bg-light">

                                    <div class="card-body">

                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-telephone me-2"></i>Phone</h6>

                                        <p class="card-text fw-semibold"><?= htmlspecialchars($user['phone']) ?></p>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="card border-0 bg-light">

                                    <div class="card-body">

                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-credit-card me-2"></i>Aadhaar</h6>

                                        <p class="card-text fw-semibold"><?= htmlspecialchars($user['aadhaar'] ?? '-') ?></p>

                                    </div>

                                </div>

                            </div>

                            <div class="col-12">

                                <div class="card border-0 bg-light">

                                    <div class="card-body">

                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-geo-alt me-2"></i>Address</h6>

                                        <p class="card-text fw-semibold"><?= htmlspecialchars($user['address'] ?? '-') ?></p>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>



                    <!-- Edit Mode -->

                    <div id="editMode" style="display: none;">

                        <div class="d-flex justify-content-between align-items-center mb-4">

                            <h5 class="text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>

                            <button type="button" class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)">

                                <i class="bi bi-x-lg me-2"></i>Cancel

                            </button>

                        </div>



                        <form method="POST" enctype="multipart/form-data">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

                            <div class="row g-3">

                                <div class="col-12">

                                    <div class="mb-3">

                                        <label for="photoUpload" class="form-label">

                                            <i class="bi bi-image me-2"></i>Change Profile Photo

                                        </label>

                                        <input type="file" class="form-control" id="photoUpload" name="photo" accept="image/*">

                                        <small class="text-muted">Max 1MB. Allowed: JPG, PNG, GIF, WEBP</small>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="form-floating">

                                        <input type="text" class="form-control" id="name" name="name" 

                                               value="<?= htmlspecialchars($user['name']) ?>" required>

                                        <label for="name"><i class="bi bi-person me-2"></i>Full Name</label>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="form-floating">

                                        <input type="email" class="form-control" id="email" name="email" 

                                               value="<?= htmlspecialchars($user['email']) ?>" readonly>

                                        <label for="email"><i class="bi bi-envelope me-2"></i>Email (Read-only)</label>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="form-floating">

                                        <input type="tel" class="form-control" id="phone" name="phone" 

                                               value="<?= htmlspecialchars($user['phone']) ?>" readonly>

                                        <label for="phone"><i class="bi bi-telephone me-2"></i>Phone (Read-only)</label>

                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <div class="form-floating">

                                        <input type="text" class="form-control" id="aadhaar" name="aadhaar" 

                                               value="<?= htmlspecialchars($user['aadhaar'] ?? '') ?>" readonly>

                                        <label for="aadhaar"><i class="bi bi-credit-card me-2"></i>Aadhaar (Read-only)</label>

                                    </div>

                                </div>

                                <div class="col-12">

                                    <div class="form-floating">

                                        <textarea class="form-control" id="address" name="address" 

                                                  style="height: 100px"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>

                                        <label for="address"><i class="bi bi-geo-alt me-2"></i>Address</label>

                                    </div>

                                </div>

                            </div>



                            <div class="d-flex gap-3 mt-4">

                                <button type="submit" name="update_profile" class="btn btn-primary btn-edit">

                                    <i class="bi bi-check-lg me-2"></i>Save Changes

                                </button>

                                <button type="button" class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)">

                                    <i class="bi bi-x-lg me-2"></i>Cancel

                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

function toggleEditMode(edit) {

    const viewMode = document.getElementById('viewMode');

    const editMode = document.getElementById('editMode');

    const changePhotoBtn = document.getElementById('changePhotoBtn');

    

    if (edit) {

        viewMode.style.display = 'none';

        editMode.style.display = 'block';

        changePhotoBtn.style.display = 'flex';

        changePhotoBtn.style.alignItems = 'center';

        changePhotoBtn.style.justifyContent = 'center';

    } else {

        viewMode.style.display = 'block';

        editMode.style.display = 'none';

        changePhotoBtn.style.display = 'none';

    }

}



// Photo preview for both inputs

function setupPhotoPreview(inputId) {

    const input = document.getElementById(inputId);

    if (input) {

        input.addEventListener('change', function(e) {

            const file = e.target.files[0];

            if (file) {

                // Validate file size (1MB)

                if (file.size > 1 * 1024 * 1024) {

                    alert('File size too large! Maximum 1MB allowed.');

                    this.value = '';

                    return;

                }

                

                // Validate file type

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

                if (!allowedTypes.includes(file.type)) {

                    alert('Invalid file type! Only JPG, PNG, GIF, and WEBP are allowed.');

                    this.value = '';

                    return;

                }

                

                const reader = new FileReader();

                reader.onload = function(e) {

                    document.getElementById('profileImage').src = e.target.result;

                }

                reader.readAsDataURL(file);

            }

        });

    }

}



setupPhotoPreview('photoInput');

setupPhotoPreview('photoUpload');



// Sync both file inputs

document.getElementById('photoInput')?.addEventListener('change', function() {

    const photoUpload = document.getElementById('photoUpload');

    if (photoUpload && this.files[0]) {

        const dt = new DataTransfer();

        dt.items.add(this.files[0]);

        photoUpload.files = dt.files;

    }

});



document.getElementById('photoUpload')?.addEventListener('change', function() {

    const photoInput = document.getElementById('photoInput');

    if (photoInput && this.files[0]) {

        const dt = new DataTransfer();

        dt.items.add(this.files[0]);

        photoInput.files = dt.files;

    }

});



// Auto-refresh every 1 minute
setInterval(function() {
    location.reload();
}, 60000);
</script>

</body>

</html>