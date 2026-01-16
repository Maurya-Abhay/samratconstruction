<?php
// admin_profile.php

require_once 'lib_common.php'; // Includes DB connection and session start

// --- 1. Authentication & Security Check ---
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$admin_email = $_SESSION['email'];

// --- 2. Fetch Admin Data (Using Prepared Statement) ---
$stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// Handle case where admin is deleted but session exists
if (!$admin) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Helper to format dates
function formatDate($date) {
    return $date ? date('d M, Y', strtotime($date)) : '<span class="text-muted fst-italic">Not set</span>';
}

// Default Avatar Logic
$photoPath = !empty($admin['photo']) ? $admin['photo'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- Profile Card Styles --- */
        .profile-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #fff;
            overflow: hidden;
            position: relative;
            margin-bottom: 30px;
        }

        /* Decorative Header Background */
        .profile-header-bg {
            height: 160px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            position: relative;
        }
        
        /* Avatar Container */
        .avatar-container {
            position: absolute;
            top: 100px; /* Overlaps header and body */
            left: 50%;
            transform: translateX(-50%);
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            background: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-initials {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            background: #f0f2f5;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Content Area */
        .profile-body {
            padding: 80px 30px 30px 30px; /* Top padding accounts for avatar overlap */
            text-align: center;
        }

        .admin-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .admin-role {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            display: inline-block;
            background: rgba(78, 115, 223, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            text-align: left;
            margin-top: 30px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 4px solid #e3e6f0;
            transition: all 0.2s;
        }
        
        .info-item:hover {
            border-left-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #858796;
            font-weight: 700;
            margin-bottom: 4px;
            display: block;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #2d3e50;
            word-break: break-word;
        }
        
        .info-icon {
            color: #b7b9cc;
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        /* Action Buttons */
        .action-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                position: relative;
                top: auto;
                right: auto;
                justify-content: center;
                margin-top: -50px; /* Adjust for mobile layout */
                margin-bottom: 60px;
            }
            .profile-header-bg { height: 120px; }
            .avatar-container { top: 60px; width: 110px; height: 110px; }
            .profile-body { padding-top: 60px; }
        }
    </style>
</head>
<body>

<?php include 'topheader.php'; ?>
<?php include 'sidenavbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <!-- Breadcrumb / Back -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-person-circle text-primary me-2"></i>My Profile</h3>
        <a href="dash.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            
            <div class="profile-card">
                <!-- Header Background -->
                <div class="profile-header-bg">
                    <!-- Desktop Actions (Absolute Positioned) -->
                    <div class="action-buttons d-none d-md-flex">
                        <a href="admins.php" class="btn btn-light btn-sm shadow-sm text-primary fw-bold">
                            <i class="bi bi-people-fill me-1"></i> All Admins
                        </a>
                        <a href="create_admin.php" class="btn btn-light btn-sm shadow-sm text-success fw-bold">
                            <i class="bi bi-person-plus-fill me-1"></i> Add New
                        </a>
                        <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="btn btn-light btn-sm shadow-sm text-warning fw-bold">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Avatar -->
                <div class="avatar-container">
                    <?php if ($photoPath): ?>
                        <img src="<?= htmlspecialchars($photoPath) ?>" class="profile-img" alt="Admin Photo">
                    <?php else: ?>
                        <div class="profile-initials">
                            <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="profile-body">
                    
                    <!-- Mobile Actions (Relative Positioned) -->
                    <div class="d-md-none mb-4 d-flex justify-content-center gap-2">
                        <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="btn btn-warning btn-sm rounded-pill px-3">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <a href="admins.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-people"></i> List
                        </a>
                    </div>

                    <h1 class="admin-name"><?= htmlspecialchars($admin['name']) ?></h1>
                    <div class="admin-role">Administrator</div>
                    
                    <div class="d-flex justify-content-center gap-3 text-muted mb-4">
                        <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($admin['email']) ?></span>
                        <span><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($admin['phone']) ?></span>
                    </div>

                    <hr class="mx-auto" style="width: 60%; opacity: 0.1;">

                    <!-- Info Grid -->
                    <div class="info-grid">
                        
                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-geo-alt-fill info-icon"></i> Address</span>
                            <div class="info-value"><?= htmlspecialchars($admin['address'] ?? 'Not Provided') ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-card-heading info-icon"></i> Aadhaar Number</span>
                            <div class="info-value"><?= htmlspecialchars($admin['aadhaar'] ?? 'Not Provided') ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-cake2-fill info-icon"></i> Date of Birth</span>
                            <div class="info-value"><?= formatDate($admin['dob']) ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-gender-ambiguous info-icon"></i> Gender</span>
                            <div class="info-value"><?= htmlspecialchars($admin['gender'] ?? 'Not Specified') ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-buildings-fill info-icon"></i> City / State</span>
                            <div class="info-value">
                                <?= htmlspecialchars($admin['city'] ?? '-') ?>, 
                                <?= htmlspecialchars($admin['state'] ?? '-') ?>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-activity info-icon"></i> Account Status</span>
                            <div class="info-value">
                                <span class="badge bg-success bg-opacity-10 text-success px-2">
                                    <?= htmlspecialchars($admin['status'] ?? 'Active') ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-calendar-check info-icon"></i> Joined On</span>
                            <div class="info-value"><?= formatDate($admin['created_at']) ?></div>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><i class="bi bi-clock-history info-icon"></i> Last Updated</span>
                            <div class="info-value"><?= formatDate($admin['updated_at']) ?></div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>