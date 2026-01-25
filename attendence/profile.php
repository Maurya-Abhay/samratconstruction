<?php
session_start();
require "../admin/database.php"; // Database connection

// Redirect if not logged in
if (!isset($_SESSION['attendence_email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['attendence_email'];

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch user
$stmt = $conn->prepare("SELECT * FROM attendence_users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("<div class='alert alert-danger'>User not found!</div>");
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $photoUrl = $user['image'];

    // Image upload
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 1 * 1024 * 1024; // 1MB
        if (!in_array($_FILES['photo']['type'], $allowed)) {
            $error_message = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
        } elseif ($_FILES['photo']['size'] > $maxSize) {
            $error_message = "Image size must be less than 1MB.";
        } else {
            $upload_dir = __DIR__ . '/../admin/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = 'attendence_photo_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photoUrl = 'uploads/' . $file_name;
            } else {
                $error_message = "Image upload failed.";
            }
        }
    }

    if (empty($error_message)) {
        $stmt = $conn->prepare("UPDATE attendence_users SET name=?, address=?, image=? WHERE email=?");
        $stmt->bind_param("ssss", $name, $address, $photoUrl, $email);
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            $user['name'] = $name;
            $user['address'] = $address;
            $user['image'] = $photoUrl;
        } else {
            $error_message = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #fff; }
        .profile-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); overflow: hidden; }
        .profile-header { background: linear-gradient(135deg, #0d6efd, #6610f2); color: white; padding: 2rem; text-align: center; position: relative; }
        .back-btn { position: absolute; top: 1rem; left: 1rem; color: white; text-decoration: none; }
        .profile-img { width: 150px; height: 150px; object-fit: cover; border: 4px solid #0d6efd; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-edit { border-radius: 50px; padding: 0.5rem 1.5rem; }
        .form-floating label { color: #6c757d; }
    </style>
</head>
<body>
<div class="container py-3">

    <?php if($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>
    <?php if($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card">
                <!-- Header -->
                <div class="profile-header">
                    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
                    <div class="position-relative d-inline-block mb-3">
                        <img id="profileImage" src="<?= !empty($user['image']) ? htmlspecialchars($user['image']) : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=0d6efd&color=fff&size=150' ?>" 
                             class="rounded-circle profile-img" alt="Profile Photo">
                        <label for="photoInput" id="changePhotoBtn" class="position-absolute bottom-0 end-0 btn btn-light btn-sm rounded-circle" style="width: 40px; height: 40px; padding: 0; display: none; border:2px solid #0d6efd; cursor:pointer;">
                            <i class="bi bi-camera-fill text-primary"></i>
                        </label>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;">
                    </div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="mb-0 opacity-75">User ID: <?= htmlspecialchars($user['id']) ?></p>
                    <p class="mb-0 opacity-75"><span class="badge bg-success"><?= htmlspecialchars($user['status'] ?? 'Active') ?></span></p>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <div id="viewMode">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-primary mb-0"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h5>
                            <button class="btn btn-outline-primary btn-edit" onclick="toggleEditMode(true)">
                                <i class="bi bi-pencil-square me-2"></i>Edit Profile
                            </button>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card border-0 bg-light p-3"><h6 class="text-muted mb-1"><i class="bi bi-person me-2"></i>Full Name</h6><p><?= htmlspecialchars($user['name']) ?></p></div></div>
                            <div class="col-md-6"><div class="card border-0 bg-light p-3"><h6 class="text-muted mb-1"><i class="bi bi-envelope me-2"></i>Email</h6><p><?= htmlspecialchars($user['email']) ?></p></div></div>
                            <div class="col-md-6"><div class="card border-0 bg-light p-3"><h6 class="text-muted mb-1"><i class="bi bi-telephone me-2"></i>Phone</h6><p><?= htmlspecialchars($user['phone']) ?></p></div></div>
                            <div class="col-md-6"><div class="card border-0 bg-light p-3"><h6 class="text-muted mb-1"><i class="bi bi-credit-card me-2"></i>Aadhaar</h6><p><?= htmlspecialchars($user['aadhaar'] ?? '-') ?></p></div></div>
                            <div class="col-12"><div class="card border-0 bg-light p-3"><h6 class="text-muted mb-1"><i class="bi bi-geo-alt me-2"></i>Address</h6><p><?= htmlspecialchars($user['address'] ?? '-') ?></p></div></div>
                        </div>
                    </div>

                    <div id="editMode" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                            <button class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)">
                                <i class="bi bi-x-lg me-2"></i>Cancel
                            </button>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="bi bi-image me-2"></i>Change Profile Photo</label>
                                        <input type="file" class="form-control" id="photoUpload" name="photo" accept="image/*">
                                        <small class="text-muted">Max 1MB. Allowed: JPG, PNG, GIF, WEBP</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                        <label for="name"><i class="bi bi-person me-2"></i>Full Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                        <label for="email"><i class="bi bi-envelope me-2"></i>Email (Read-only)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="address" name="address" style="height:100px"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                        <label for="address"><i class="bi bi-geo-alt me-2"></i>Address</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-edit"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
                                <button type="button" class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)"><i class="bi bi-x-lg me-2"></i>Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleEditMode(edit) {
    document.getElementById('viewMode').style.display = edit ? 'none' : 'block';
    document.getElementById('editMode').style.display = edit ? 'block' : 'none';
    document.getElementById('changePhotoBtn').style.display = edit ? 'flex' : 'none';
}

// Photo preview
function setupPhotoPreview(inputId) {
    const input = document.getElementById(inputId);
    input?.addEventListener('change', function(e){
        const file = e.target.files[0];
        if(file){
            if(file.size > 1024*1024){ alert('Max 1MB'); input.value=''; return; }
            const allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
            if(!allowed.includes(file.type)){ alert('Invalid type'); input.value=''; return; }
            const reader = new FileReader();
            reader.onload = e => document.getElementById('profileImage').src = e.target.result;
            reader.readAsDataURL(file);
        }
    });
}
setupPhotoPreview('photoInput');
setupPhotoPreview('photoUpload');
</script>
</body>
</html>
