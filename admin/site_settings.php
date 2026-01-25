<?php
// Admin Site Settings Page

require_once __DIR__ . '/lib_common.php';
// require_role('Admin'); // Assuming role check exists in lib_common or similar
include 'topheader.php';
include 'sidenavbar.php';

// --- Database Initialization ---
$conn->query("CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(64) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS slider_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Default keys
$default_keys = [
    'office_address', 'contact_email', 'contact_phone', 'logo_url',
    'facebook_url', 'youtube_url', 'linkedin_url', 'twitter_url', 'app_download_url',
    'cloud_name', 'cloud_api_key', 'cloud_api_secret'
];

// --- Handle Form Submissions ---
$msg = '';
$slider_msg = '';

// Save General Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($default_keys as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            $stmt->bind_param('ss', $key, $value);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Settings Updated',text:'Settings updated successfully!',showConfirmButton:false,timer:2000});</script>";
}


// Add Slider Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slider_image'])) {
    $image_url = trim($_POST['slider_image_url'] ?? '');
    $caption = trim($_POST['slider_caption'] ?? '');
    
    $count = (int)$conn->query("SELECT COUNT(*) FROM slider_images")->fetch_row()[0];
    
    if ($count >= 8) {
       echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Max Images',text:'Max 8 images allowed.',showConfirmButton:false,timer:2000});</script>";
    } elseif (empty($image_url)) {
       echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'warning',title:'Image URL',text:'Image URL required.',showConfirmButton:false,timer:2000});</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO slider_images (image_url, caption) VALUES (?, ?)");
        $stmt->bind_param('ss', $image_url, $caption);
        $stmt->execute();
        $stmt->close();
       echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Image Added',text:'Image added.',showConfirmButton:false,timer:2000});</script>";
    }
}

// Delete Slider Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slider'])) {
    $id = (int)$_POST['delete_slider'];
    $stmt = $conn->prepare("DELETE FROM slider_images WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Image Removed',text:'Image removed.',showConfirmButton:false,timer:2000});</script>";
}

// Fetch Data
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

$slider_images = $conn->query("SELECT * FROM slider_images ORDER BY created_at DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$current_slider_count = count($slider_images);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Admin Panel</title>
    
    <!-- CSS Libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
            color: #444;
        }

        /* Tabs */
        .nav-pills .nav-link {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s;
        }
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
        }

        /* Modern Card */
        .settings-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            background: #fff;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header-custom {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 25px;
        }

        /* Form Elements */
        .form-floating > .form-control { height: 3.5rem; border-radius: 10px; border: 1px solid #e0e0e0; }
        .form-floating > .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1); }
        .form-floating > label { padding-top: 0.6rem; }
        
        .section-label {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #888;
            letter-spacing: 1px;
            margin-bottom: 15px;
            margin-top: 10px;
        }

        /* Slider Grid */
        .slider-grid-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 16/9;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .slider-grid-item:hover { transform: translateY(-3px); }
        
        .slider-img {
            width: 100%; height: 100%; object-fit: cover;
        }
        
        .slider-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4);
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slider-grid-item:hover .slider-overlay { opacity: 1; }
        
        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s;
        }
        .delete-btn:hover { transform: scale(1.1); background: #c82333; }

        /* Logo Preview */
        .logo-preview-box {
            width: 120px; height: 120px;
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: #f9f9f9;
            overflow: hidden;
        }
        .logo-preview-img { max-width: 100%; max-height: 100%; padding: 10px; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-sliders text-primary me-2"></i>Settings</h3>
        
        <!-- Tabs -->
        <ul class="nav nav-pills bg-white p-1 rounded-pill shadow-sm" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button"><i class="bi bi-gear me-1"></i> General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="slider-tab" data-bs-toggle="tab" data-bs-target="#slider" type="button"><i class="bi bi-images me-1"></i> Slider <span class="badge bg-white text-dark ms-1 rounded-pill"><?= $current_slider_count ?></span></button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="settingsTabContent">
        
        <!-- GENERAL TAB -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            
            <?= $msg ?>

            <div class="row g-4">
                <!-- Left Column: Branding & Contact -->
                <div class="col-lg-8">
                    <form method="POST" class="settings-card">
                        <div class="card-header-custom">
                            <h5 class="m-0 fw-bold text-dark">General Configuration</h5>
                        </div>
                        <div class="p-4">
                            <div class="section-label">Contact Details</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" placeholder="Email">
                                        <label>Contact Email</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" placeholder="Phone">
                                        <label>Contact Phone</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" name="office_address" value="<?= htmlspecialchars($settings['office_address'] ?? '') ?>" placeholder="Address">
                                        <label>Office Address</label>
                                    </div>
                                </div>
                            </div>

                            <div class="section-label">Social Media</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-facebook text-primary"></i></span>
                                        <div class="form-floating flex-grow-1">
                                            <input type="url" class="form-control rounded-0 rounded-end" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="URL">
                                            <label>Facebook URL</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-youtube text-danger"></i></span>
                                        <div class="form-floating flex-grow-1">
                                            <input type="url" class="form-control rounded-0 rounded-end" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>" placeholder="URL">
                                            <label>YouTube URL</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-instagram text-warning"></i></span>
                                        <div class="form-floating flex-grow-1">
                                            <input type="url" class="form-control rounded-0 rounded-end" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="URL">
                                            <label>Instagram URL</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-android text-success"></i></span>
                                        <div class="form-floating flex-grow-1">
                                            <input type="url" class="form-control rounded-0 rounded-end" name="app_download_url" value="<?= htmlspecialchars($settings['app_download_url'] ?? '') ?>" placeholder="URL">
                                            <label>App Download Link</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="save_settings" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Logo Upload -->
                <div class="col-lg-4">
                    <div class="settings-card">
                        <div class="card-header-custom">
                            <h5 class="m-0 fw-bold text-dark">Brand Logo</h5>
                        </div>
                        <div class="p-4 text-center">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="logo-preview-box">
                                    <?php if (!empty($settings['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars($settings['logo_url']) ?>" class="logo-preview-img">
                                    <?php else: ?>
                                        <span class="text-muted small">No Logo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="logo_image" class="form-control form-control-sm mb-2" required>
                                <button type="submit" name="upload_logo" class="btn btn-sm btn-outline-primary w-100">Upload New Logo</button>
                            </form>
                        </div>
                    </div>
                </div>
                                </div>
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" name="cloud_api_key" value="<?= htmlspecialchars($settings['cloud_api_key'] ?? '') ?>" placeholder="API Key">
                                </div>
                                <div class="mb-3">
                                    <input type="password" class="form-control form-control-sm" name="cloud_api_secret" value="<?= htmlspecialchars($settings['cloud_api_secret'] ?? '') ?>" placeholder="API Secret">
                                </div>
                                <button type="submit" name="save_settings" class="btn btn-sm btn-secondary w-100">Update Credentials</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDER TAB -->
        <div class="tab-pane fade" id="slider" role="tabpanel">
            
            <?= $slider_msg ?>

            <!-- Add New Slider -->
            <div class="settings-card p-4 mb-4">
                <form method="POST" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="url" class="form-control" name="slider_image_url" placeholder="URL" required>
                            <label>Image URL</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="slider_caption" placeholder="Caption">
                            <label>Caption (Optional)</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_slider_image" class="btn btn-success w-100 py-3 fw-bold shadow-sm" <?= $current_slider_count >= 8 ? 'disabled' : '' ?>>
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                </form>
            </div>

            <!-- Slider Grid -->
            <div class="row g-4">
                <?php if ($current_slider_count > 0): ?>
                    <?php foreach ($slider_images as $img): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="slider-grid-item">
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" class="slider-img">
                            <div class="slider-overlay">
                                <form method="POST" onsubmit="return confirm('Delete this image?');">
                                    <input type="hidden" name="delete_slider" value="<?= $img['id'] ?>">
                                    <button type="submit" class="delete-btn shadow"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="text-center mt-2 small text-muted text-truncate px-2">
                            <?= htmlspecialchars($img['caption'] ?: 'No Caption') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-images display-4 mb-3 d-block opacity-25"></i>
                        No slider images found. Add some above.
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<?php include 'downfooter.php'; ?>

<!-- Image Upload Handler Logic (Optional placeholder for backend logic) -->
<?php
if (isset($_POST['upload_logo']) && isset($_FILES['logo_image'])) {
    $logo = $_FILES['logo_image'];
    if ($logo['error'] === UPLOAD_ERR_OK && $logo['size'] > 0) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($logo['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($logo['tmp_name'], $target_path)) {
            $local_url = 'uploads/' . $file_name;
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('logo_url', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            $stmt->bind_param('s', $local_url);
            $stmt->execute();
            $stmt->close();
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Logo Uploaded',text:'Logo uploaded successfully.',showConfirmButton:false,timer:2000});</script>";
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Upload Failed',text:'Logo upload failed.',showConfirmButton:false,timer:2000});</script>";
        }
    } 
} else {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'warning',title:'Invalid File',text:'Invalid logo file.',showConfirmButton:false,timer:2000});</script>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>