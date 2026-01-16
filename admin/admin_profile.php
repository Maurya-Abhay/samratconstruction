<?php
// admin_profile.php

require_once 'lib_common.php'; // Ensure DB connection

// --- 1. Authentication Check ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// --- 2. Fetch Admin Data ---
$admin_email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    // Session invalid or user deleted
    header("Location: logout.php");
    exit();
}

include 'sidenavbar.php';
include 'topheader.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profile-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: none;
        }

        .profile-header-bg {
            height: 150px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }

        .profile-img-container {
            position: relative;
            margin-top: -75px;
            text-align: center;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        
        .profile-name {
            margin-top: 15px;
            font-weight: 700;
            color: #333;
        }
        
        .profile-role {
            color: #4e73df;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fc;
            border-radius: 10px;
            border-left: 4px solid #4e73df;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #858796;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #333;
            word-break: break-word;
        }

        .action-bar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
             .profile-header-bg { height: 100px; }
             .profile-img { width: 120px; height: 120px; margin-top: -60px; }
             .action-bar { justify-content: center; flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<div class="profile-container">

    <div class="action-bar">
        <a href="admins.php" class="btn btn-outline-secondary">
            <i class="fas fa-users me-1"></i> All Admins
        </a>
        <a href="create_admin.php" class="btn btn-success">
            <i class="fas fa-user-plus me-1"></i> New Admin
        </a>
        <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="btn btn-warning text-dark">
            <i class="fas fa-user-edit me-1"></i> Edit Profile
        </a>
    </div>

    <div class="profile-card">
        
        <div class="profile-header-bg"></div>

        <div class="profile-img-container">
            <?php if (!empty($admin['photo'])): ?>
                <img src="<?= htmlspecialchars($admin['photo']) ?>" class="profile-img" alt="Admin Photo">
            <?php else: ?>
                <div class="profile-img d-inline-flex align-items-center justify-content-center bg-light text-primary display-4 fw-bold">
                    <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            
            <h3 class="profile-name"><?= htmlspecialchars($admin['name']) ?></h3>
            <div class="profile-role">Administrator</div>
            <div class="text-muted mt-1 small">
                <i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($admin['email']) ?>
                <span class="mx-2">•</span>
                <i class="fas fa-phone me-1"></i> <?= htmlspecialchars($admin['phone']) ?>
            </div>
        </div>

        <hr class="mx-4 my-4">

        <div class="info-grid">
            <?php
            function renderInfo($label, $value, $icon = 'circle') {
                $val = !empty($value) ? htmlspecialchars($value) : '<span class="text-muted fst-italic">Not Provided</span>';
                echo "
                <div class='info-item'>
                    <div class='info-label'><i class='fas fa-$icon me-1'></i> $label</div>
                    <div class='info-value'>$val</div>
                </div>";
            }

            renderInfo('Status', ucfirst($admin['status']), 'toggle-on');
            renderInfo('Aadhaar Number', $admin['aadhaar'], 'id-card');
            renderInfo('Date of Birth', $admin['dob'], 'calendar-alt');
            renderInfo('Gender', ucfirst($admin['gender']), 'venus-mars');
            renderInfo('City', $admin['city'], 'city');
            renderInfo('State', $admin['state'], 'map-marker-alt');
            renderInfo('Full Address', $admin['address'], 'home');
            renderInfo('Joined On', date('d M Y', strtotime($admin['created_at'])), 'clock');
            ?>
        </div>

    </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>