<?php
/**
 * Worker Profile Management Page
 * Handles displaying worker details and managing updates for personal information and UPI payment settings.
 * * Assumes the following external variables/files are defined and available:
 * - $page_title, $show_back_btn (for header.php)
 * - $conn (Database connection object from lib_common.php)
 * - $worker_id (Authenticated worker ID)
 * - header.php and footer.php (for layout/styling, assumed to include Bootstrap/Icons)
 */

$page_title = "Profile";
$show_back_btn = true;

// Includes
include 'header.php';

// Authentication check
if (!$worker_id) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../admin/lib_common.php';

$success_message = null;
$error_message = null;
$upi_success = null;
$upi_error = null;

// --- 1. Ensure worker_payment_settings table exists ---
$conn->query("CREATE TABLE IF NOT EXISTS worker_payment_settings (
    worker_id INT PRIMARY KEY,
    upi_vpa VARCHAR(120) NULL,
    upi_payee VARCHAR(120) NULL,
    upi_mobile VARCHAR(20) NULL,
    upi_qr_path VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wps_worker_prof FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


// --- 2. Handle form submission for profile update (Personal Info) - CONVERTED TO PREPARED STATEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Collect and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');

    // Server-side validation
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = "Name, Email, and Phone are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error_message = "Phone number must be a 10-digit number.";
    } elseif (!empty($aadhaar) && !preg_match('/^[0-9]{12}$/', $aadhaar)) {
        $error_message = "Aadhaar number must be a 12-digit number.";
    } else {
        try {
            $update_query = "UPDATE workers SET 
                                name=?, 
                                email=?, 
                                phone=?, 
                                address=?, 
                                aadhaar=? 
                             WHERE id=?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sssssi', $name, $email, $phone, $address, $aadhaar, $worker_id);

            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        } catch (Throwable $e) {
            $error_message = 'Database error during profile update: '.$e->getMessage();
            error_log($error_message);
        }
    }
}


// --- 3. Handle UPI settings save (Already using prepared statements, preserving logic) ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_upi'])){
    $vpa    = trim($_POST['upi_vpa'] ?? '');
    $payee  = trim($_POST['upi_payee'] ?? '');
    $mobile = trim($_POST['upi_mobile'] ?? '');

    // Basic UPI Mobile Validation
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $upi_error = "UPI Mobile number must be 10 digits or left blank.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO worker_payment_settings (worker_id, upi_vpa, upi_payee, upi_mobile) VALUES (?,?,?,?)
              ON DUPLICATE KEY UPDATE upi_vpa=VALUES(upi_vpa), upi_payee=VALUES(upi_payee), upi_mobile=VALUES(upi_mobile)");
            $stmt->bind_param('isss', $worker_id, $vpa, $payee, $mobile);
            $stmt->execute();
            $stmt->close();
            $upi_success = 'UPI details saved.';
        } catch (Throwable $e) {
            $upi_error = 'Failed to save UPI details: '.$e->getMessage();
        }
    }
    
    // Secure file upload for UPI QR
    if (isset($_FILES['upi_qr']) && $_FILES['upi_qr']['error']===UPLOAD_ERR_OK){
        $ext = strtolower(pathinfo($_FILES['upi_qr']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $tmp = $_FILES['upi_qr']['tmp_name'];

        if (!in_array($ext, $allowed)) {
            $upi_error = 'Only JPG, JPEG, PNG, WEBP, GIF files are allowed.';
        } elseif (getimagesize($tmp) === false) {
            $upi_error = 'This is not a valid image file.';
        } elseif (filesize($tmp) > 2*1024*1024) {
            $upi_error = 'File size must be less than 2MB.';
        } else {
            $contents = file_get_contents($tmp);
            if (strpos($contents, '<?php') !== false || strpos($contents, '<?=') !== false) {
                $upi_error = 'Malware detected, upload blocked.';
                error_log('Malware upload attempt: '.$_FILES['upi_qr']['name'].' by user ID: '.$worker_id);
            } else {
                $upload_dir = __DIR__ . '/../admin/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = pathinfo($_FILES['upi_qr']['name'], PATHINFO_EXTENSION);
                $file_name = 'worker_qr_' . $worker_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $file_name;
                if (move_uploaded_file($tmp, $target_path)) {
                    $stmt_qr = $conn->prepare("UPDATE worker_payment_settings SET upi_qr_path=? WHERE worker_id=?");
                    $stmt_qr->bind_param('si', 'uploads/' . $file_name, $worker_id);
                    $stmt_qr->execute();
                    $stmt_qr->close();
                    $upi_success = ($upi_success? $upi_success.' ' : '').'QR uploaded.';
                } else { $upi_error = 'Error uploading QR image to server.'; }
            }
        }
    }
}


// --- 4. Fetch updated worker info and UPI settings ---
$worker_stmt = $conn->prepare("SELECT * FROM workers WHERE id=?");
$worker_stmt->bind_param('i', $worker_id);
$worker_stmt->execute();
$worker = $worker_stmt->get_result()->fetch_assoc();
$worker_stmt->close();

if (!$worker) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Worker Not Found',text:'Worker not found!',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit();
}

$photoPath = !empty($worker['photo']) ? '../admin/' . (strpos($worker['photo'], 'uploads/') === 0 ? $worker['photo'] : 'uploads/' . $worker['photo']) : '../admin/assets/default-avatar.png';

// Fetch current UPI settings
$wps_stmt = $conn->prepare("SELECT * FROM worker_payment_settings WHERE worker_id=?");
$wps_stmt->bind_param('i', $worker_id);
$wps_stmt->execute();
$wps = $wps_stmt->get_result()->fetch_assoc();
$wps_stmt->close();
?>

<!-- ========================================================================================= -->
<!-- HTML/CSS/JS for Presentation -->
<!-- ========================================================================================= -->

<style>
    /* Styling to make the profile card look modern and responsive */
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
        /* Vibrant gradient for the header section */
        background: linear-gradient(135deg, #0d6efd, #6610f2);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .form-floating label {
        color: #6c757d;
    }
    .btn-edit {
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
    }
    /* Responsive adjustment for view mode cards */
    .card-body p.fw-semibold {
        font-size: 1.1rem;
        word-break: break-word; /* Ensure long emails/addresses wrap */
    }
    .bg-light {
        transition: transform 0.2s;
    }
    .bg-light:hover {
        transform: translateY(-2px);
    }
</style>

<div class="container py-3">
    <!-- Success and Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($upi_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($upi_success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($upi_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($upi_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <!-- Profile Photo with fallback using UI Avatars API -->
                    <img src="<?= file_exists($photoPath) ? htmlspecialchars($photoPath) : 'https://ui-avatars.com/api/?name='.urlencode($worker['name']).'&background=0d6efd&color=fff&size=150' ?>" 
                         onerror="this.onerror=null;this.src='https://placehold.co/150x150/0d6efd/ffffff?text=<?php echo urlencode(substr($worker['name'], 0, 1)); ?>'"
                         class="rounded-circle profile-img mb-3" alt="Profile Photo">
                    <h3 class="mb-1"><?= htmlspecialchars($worker['name']) ?></h3>
                    <p class="mb-0 opacity-75">Worker ID: <?= htmlspecialchars($worker_id) ?></p>
                    <p class="mb-0 opacity-75"><span class="badge bg-light text-primary"><?= htmlspecialchars($worker['status'] ?? 'Active') ?></span></p>
                </div>

                <!-- Profile Content (Tabs/Sections) -->
                <div class="p-4">
                    <!-- View Mode (Default) -->
                    <div id="viewMode">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-primary mb-0"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h5>
                            <button type="button" class="btn btn-outline-primary btn-edit" onclick="toggleEditMode(true)">
                                <i class="bi bi-pencil-square me-2"></i>Edit Profile
                            </button>
                        </div>

                        <!-- Personal Info Display -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-person me-2"></i>Full Name</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($worker['name']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-envelope me-2"></i>Email</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($worker['email']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-telephone me-2"></i>Phone</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($worker['phone']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-credit-card me-2"></i>Aadhaar</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($worker['aadhaar'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-geo-alt me-2"></i>Address</h6>
                                        <p class="card-text fw-semibold"><?= nl2br(htmlspecialchars($worker['address'] ?? '-')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Information (Read-only) -->
                        <h5 class="text-primary mt-4 mb-3"><i class="bi bi-briefcase me-2"></i>Work Information</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-calendar-plus me-2"></i>Joining Date</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($worker['joining_date'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-cash-stack me-2"></i>Daily Salary</h6>
                                        <p class="card-text fw-semibold">₹<?= number_format($worker['salary'] ?? 0, 2) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-clock me-2"></i>Last Check-in</h6>
                                        <p class="card-text fw-semibold">
                                            <?= htmlspecialchars($worker['last_checkin'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UPI Payment Settings (Read-only) -->
                        <h5 class="text-primary mt-4 mb-3"><i class="bi bi-upc-scan me-2"></i>UPI Payment Settings</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-at me-2"></i>UPI VPA</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($wps['upi_vpa'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-person-badge me-2"></i>Payee Name</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($wps['upi_payee'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-1"><i class="bi bi-phone me-2"></i>Mobile</h6>
                                        <p class="card-text fw-semibold"><?= htmlspecialchars($wps['upi_mobile'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card border-0 bg-light">
                                    <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                                        <div>
                                            <h6 class="card-title text-muted mb-1"><i class="bi bi-qr-code me-2"></i>UPI QR</h6>
                                            <p class="mb-0">Your uploaded QR code for receiving payments.</p>
                                        </div>
                                        <?php if (!empty($wps['upi_qr_path'])): ?>
                                            <img src="<?= htmlspecialchars($wps['upi_qr_path']) ?>" alt="UPI QR" style="max-height:120px" class="ms-md-auto border rounded">
                                        <?php else: ?>
                                            <span class="text-muted ms-md-auto">No QR uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Mode (Hidden by default) -->
                    <div id="editMode" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                            <button type="button" class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)">
                                <i class="bi bi-x-lg me-2"></i>Cancel
                            </button>
                        </div>

                        <!-- Personal Information Edit Form -->
                        <form method="POST" action="">
                            <h6 class="text-secondary mb-3">Update Personal Details</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($worker['name']) ?>" required>
                                        <label for="name"><i class="bi bi-person me-2"></i>Full Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($worker['email']) ?>" required>
                                        <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?= htmlspecialchars($worker['phone']) ?>" 
                                               pattern="[0-9]{10}" maxlength="10" required>
                                        <label for="phone"><i class="bi bi-telephone me-2"></i>Phone (10-digit)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="aadhaar" name="aadhaar" 
                                               value="<?= htmlspecialchars($worker['aadhaar'] ?? '') ?>" 
                                               pattern="[0-9]{12}" maxlength="12">
                                        <label for="aadhaar"><i class="bi bi-credit-card me-2"></i>Aadhaar Number (12-digit)</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="address" name="address" 
                                                  placeholder="Your full address"
                                                  style="height: 100px"><?= htmlspecialchars($worker['address'] ?? '') ?></textarea>
                                        <label for="address"><i class="bi bi-geo-alt me-2"></i>Address</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-edit">
                                    <i class="bi bi-check-lg me-2"></i>Save Personal Changes
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-edit" onclick="toggleEditMode(false)">
                                    <i class="bi bi-x-lg me-2"></i>Cancel
                                </button>
                            </div>
                        </form>

                        <hr class="my-5">

                        <!-- UPI Payment Settings Edit Form -->
                        <h5 class="text-primary mb-3"><i class="bi bi-upc-scan me-2"></i>UPI Payment Settings</h5>
                        <form method="POST" action="" enctype="multipart/form-data" class="border rounded p-4 bg-light">
                            <h6 class="text-secondary mb-3">Update Payment Details & QR</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="upi_vpa" name="upi_vpa" placeholder="name@bank" value="<?= htmlspecialchars($wps['upi_vpa'] ?? '') ?>">
                                        <label for="upi_vpa"><i class="bi bi-at me-2"></i>UPI VPA</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="upi_payee" name="upi_payee" placeholder="Payee Name" value="<?= htmlspecialchars($wps['upi_payee'] ?? '') ?>">
                                        <label for="upi_payee"><i class="bi bi-person-badge me-2"></i>Payee Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="upi_mobile" name="upi_mobile" placeholder="10-digit mobile" value="<?= htmlspecialchars($wps['upi_mobile'] ?? '') ?>" pattern="[0-9]{10}" maxlength="10">
                                        <label for="upi_mobile"><i class="bi bi-phone me-2"></i>Mobile (10-digit)</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-3 align-items-end">
                                <div class="col-md-8">
                                    <label for="upi_qr" class="form-label mb-1"><i class="bi bi-qr-code me-2"></i>UPI QR Image (Max 2MB)</label>
                                    <input class="form-control" type="file" id="upi_qr" name="upi_qr" accept="image/jpeg,image/png,image/webp,image/gif">
                                    <div class="form-text">Accepted formats: JPG, PNG, WebP, GIF. Leave blank to keep existing QR.</div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <?php if (!empty($wps['upi_qr_path'])): ?>
                                        <p class="mb-1 text-muted small">Current QR:</p>
                                        <img src="../admin/<?= htmlspecialchars($wps['upi_qr_path']) ?>" alt="Current UPI QR" style="max-height:120px" class="border rounded">
                                    <?php else: ?>
                                        <span class="text-muted small">No QR uploaded</span>
                                    <?php endif; ?>
                                </div>
                            </div>


                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" name="update_upi" class="btn btn-success">
                                    <i class="bi bi-save me-2"></i>Save UPI Settings
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleEditMode(false)">
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

<script>
/**
 * Toggles between the read-only view mode and the edit mode forms.
 * @param {boolean} edit - True to show edit mode, false to show view mode.
 */
function toggleEditMode(edit) {
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');
    
    if (edit) {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
    } else {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
    }
}

// Auto-format Aadhaar number (client-side UX)
document.getElementById('aadhaar')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value.substring(0, 12);
});

// Removed the aggressive `setInterval(location.reload(), 60000)` as it's poor UX for a user actively viewing/editing a form.
</script>

<?php include 'footer.php'; ?>