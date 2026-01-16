<?php
// profile.php - Client/Contact Profile Management

// Start session and load common library/config
require_once __DIR__ . '/../admin/lib_common.php'; // Assumes this file starts the session and establishes $conn
@include_once __DIR__ . '/../admin/analytics_track.php';

// --- Security Check ---
if (!isset($_SESSION['contact_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$contact_id = (int)$_SESSION['contact_id'];

// --- Load Contact Data ---
$stmt = $conn->prepare("SELECT * FROM contacts WHERE id=?");
$stmt->bind_param('i', $contact_id);
$stmt->execute();
$contact = $stmt->get_result()->fetch_assoc();
$stmt->close();

$errors = [];
$success = '';

// --- Handle Profile Update Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    
    // Sanitize and trim input data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $aadhaar = trim($_POST['aadhaar']);
    $address = trim($_POST['address']);

    // Validation (even for read-only fields, to ensure integrity if they were editable)
    // NOTE: The UI fields for email/phone are READONLY, but the variables are set here.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) $errors[] = 'Invalid phone number (10 digits starting with 6-9).';
    
    // Use stored contact data for read-only fields to prevent tampering if readonly is bypassed
    $name = trim($_POST['name'] ?? $contact['name']); 
    $aadhaar = trim($_POST['aadhaar'] ?? $contact['aadhaar']);
    $address = trim($_POST['address'] ?? $contact['address']);
    $email = $contact['email']; // Use DB value
    $phone = $contact['phone']; // Use DB value


    // Secure file upload for contact photo
    $photoUpdate = ''; // SQL part for photo update
    $newFileName = null; // Used to hold the new file name if upload is successful

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $originalFileName = $_FILES['photo']['name'];
        $tmp = $_FILES['photo']['tmp_name'];
        $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, JPEG, PNG, WEBP, GIF files are allowed.';
        } elseif (getimagesize($tmp) === false) {
            $errors[] = 'This is not a valid image file.';
        } elseif (filesize($tmp) > 2*1024*1024) {
            $errors[] = 'File size must be less than 2MB.';
        } else {
            $contents = file_get_contents($tmp);
            if (strpos($contents, '<?php') !== false) {
                $errors[] = 'Malware detected, upload blocked.';
                error_log('Malware upload attempt: '.$originalFileName.' by '.$_SERVER['REMOTE_ADDR']);
            } else {
                // Upload to Cloudinary
                $cloudinary_url = upload_to_cloudinary($tmp, 'contact_photos');
                if ($cloudinary_url) {
                    $photoUpdate = ", photo=?";
                    $newPhotoValue = $cloudinary_url;
                } else {
                    $errors[] = 'Cloudinary upload failed.';
                }
            }
        }
    }

    // --- Database Update ---
    if (!$errors) {
        // Updated query to use $email and $phone from $contact to ensure read-only fields are NOT updated
        $query = "UPDATE contacts SET name=?, aadhaar=?, address=? $photoUpdate WHERE id=?";
        
        $types = 'sssi';
        $params = [&$name, &$aadhaar, &$address];
        
        // If photo was updated, add the new photo path to params and update types string
        if ($photoUpdate) { 
            $newPhotoValue = 'uploads/'.$newFileName; 
            $params[] = &$newPhotoValue; 
            $types = 'ssssi'; // Adjust types for name, aadhaar, address, photo, id
        }
        
        // Add contact ID to the end
        $params[] = &$contact_id;
        
        $stmt = $conn->prepare($query);
        // Correctly bind parameters for variable number of parameters
        $stmt->bind_param($types, ...$params); 
        
        if ($stmt->execute()) { 
            $success = 'Profile updated successfully!'; 
            // Reload contact data to update the form fields immediately
            $contact = $conn->query("SELECT * FROM contacts WHERE id=$contact_id")->fetch_assoc(); 
        } else { 
            $errors[] = 'Error updating profile: '.$stmt->error; 
            // If DB update failed but file uploaded, consider cleaning up the new file
            if ($photoUpdate && isset($uploadPath) && file_exists($uploadPath)) @unlink($uploadPath);
        }
        $stmt->close();
    }
}

// --- Determine Photo Path for Display ---
$photo = $contact['photo'] ?? '';
$photoPath = (!empty($photo) && preg_match('#^https?://#', $photo)) ? $photo : 'https://ui-avatars.com/api/?name='.urlencode($contact['name']).'&background=0d6efd&color=fff&size=120&font-size=0.5';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Client Portal</title>
    <link rel="icon" href="../admin/assets/smrticon.png" type="image/png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Base Styles & Typography */
        body { 
            background: #f0f2f5; 
            font-family: 'Roboto', sans-serif; 
            padding-top: 80px; 
        }
        
        /* Card Styling */
        .profile-card {
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
            background: #ffffff;
        }
        
        /* Header Title */
        .header-title {
            font-weight: 700;
            color: #0d6efd;
            font-size: 2rem;
        }

        /* Profile Photo */
        .profile-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 5px solid #0d6efd;
            box-shadow: 0 0 0 5px rgba(13, 110, 253, 0.2); /* Outer glow effect */
        }
        
        /* Input Styles */
        .form-floating input, .form-floating textarea {
            border-radius: 10px;
        }
        
        /* Read-Only Field Styling */
        .form-floating input:read-only { 
            background-color: #e9ecef !important; 
            border: 1px dashed #ced4da; /* Dashed border for visual difference */
            cursor: not-allowed;
        }
        
        /* Save Button */
        .btn-primary {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
        }
    </style>
</head>
<body>

<?php 
$contact_show_back_btn = true; 
$contact_back_href = 'dashboard.php'; 
include __DIR__ . '/header.php'; 
?>

<div class="container py-5">
    <h2 class="header-title mb-5">
        <i class="bi bi-person-badge me-2"></i> Account Profile Settings
    </h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger rounded-3 mb-4 shadow-sm">
            <h5 class="alert-heading"><i class="bi bi-x-octagon-fill me-2"></i>Update Failed!</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success rounded-3 mb-4 shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="card profile-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-5">
                <img src="<?= htmlspecialchars($photoPath) ?>" class="rounded-circle profile-photo shadow-lg" alt="Profile Photo">
                <h3 class="mt-4 fw-bold text-dark"><?= htmlspecialchars($contact['name']) ?></h3>
                <p class="text-muted small">Client ID: #<?= $contact_id ?></p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <h5 class="mb-4 text-secondary border-bottom pb-2">Personal Details</h5>
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($contact['name']) ?>" required placeholder="Full Name" readonly>
                            <label for="name"><i class="bi bi-person me-1"></i>Full Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($contact['email']) ?>" readonly placeholder="Email Address">
                            <label for="email"><i class="bi bi-envelope me-1"></i>Email (Admin Locked)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($contact['phone']) ?>" readonly placeholder="Phone Number">
                            <label for="phone"><i class="bi bi-telephone me-1"></i>Phone (Admin Locked)</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="aadhaar" name="aadhaar" value="<?= htmlspecialchars($contact['aadhaar'] ?? '') ?>" placeholder="Aadhaar Number" readonly>
                            <label for="aadhaar"><i class="bi bi-credit-card me-1"></i>Aadhaar Number</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="address" name="address" style="height: 120px;" required placeholder="Full Address" readonly><?= htmlspecialchars($contact['address']) ?></textarea>
                            <label for="address"><i class="bi bi-geo-alt me-1"></i>Address</label>
                        </div>
                    </div>
                    <div class="col-12 pt-3">
                        <label for="photo_upload" class="form-label fw-semibold text-dark"><i class="bi bi-image me-1"></i>Change Profile Photo</label>
                        <input type="file" class="form-control" id="photo_upload" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" disabled>
                        <small class="text-muted">Max size: 2MB. Allowed: JPG, PNG, WEBP, GIF.</small>
                    </div>
                </div>
                <div class="mt-5 text-center text-md-end">
                    <button type="button" id="editBtn" class="btn btn-lg btn-outline-primary px-5 shadow-sm">
                        <i class="bi bi-pencil-square me-2"></i>Edit Profile
                    </button>
                    <button type="submit" name="update_contact" id="saveBtn" class="btn btn-lg btn-primary px-5 shadow-sm d-none">
                        <i class="bi bi-cloud-check me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Modern profile edit toggle
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const form = document.getElementById('profileForm');
    const fields = form.querySelectorAll('input, textarea');
    editBtn.addEventListener('click', function() {
        fields.forEach(f => {
            if (f.id !== 'email' && f.id !== 'phone') {
                f.removeAttribute('readonly');
                f.removeAttribute('disabled');
            }
        });
        document.getElementById('aadhaar').removeAttribute('readonly');
        document.getElementById('address').removeAttribute('readonly');
        document.getElementById('photo_upload').removeAttribute('disabled');
        editBtn.classList.add('d-none');
        saveBtn.classList.remove('d-none');
    });
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>