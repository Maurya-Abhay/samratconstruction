<?php
// --- PHP Logic: Authentication and Data Fetching ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$worker_id = isset($_SESSION['worker_id']) ? $_SESSION['worker_id'] : null;

if (!$worker_id) {
    header('Location: login.php');
    exit();
}

// Default values
$worker_name = "Worker";
$worker_profession = "Employee";
$worker_photo = "https://ui-avatars.com/api/?name=Worker&background=FF6E00&color=fff";

// Database Connection
if ($worker_id) {
    require_once '../admin/database.php';
    @include __DIR__ . '/../admin/analytics_track.php';
    
    $stmt = $conn->prepare("SELECT name, email, photo FROM workers WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $worker_id);
    $stmt->execute();
    $worker_result = $stmt->get_result();
    
    if ($worker_result && $worker_result->num_rows > 0) {
        $worker_data = $worker_result->fetch_assoc();
        $worker_name = $worker_data['name'];
        $worker_profession = "Construction Worker"; 
        
        if ($worker_data['photo']) {
            $photoPath = '../admin/' . (strpos($worker_data['photo'], 'uploads/') === 0 ? $worker_data['photo'] : 'uploads/' . $worker_data['photo']);
            if (file_exists($photoPath)) {
                $worker_photo = $photoPath;
            } else {
                $worker_photo = "https://ui-avatars.com/api/?name=" . urlencode($worker_name) . "&background=FF6E00&color=fff";
            }
        } else {
            $worker_photo = "https://ui-avatars.com/api/?name=" . urlencode($worker_name) . "&background=FF6E00&color=fff";
        }
    }
    $stmt->close();
}

$page_title = isset($page_title) ? $page_title : 'Worker Dashboard';
$show_back_btn = isset($show_back_btn) ? $show_back_btn : false;

// Modern FAB Refresh Button
echo '<style>
.fab-refresh-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    background: linear-gradient(135deg, #2563eb 60%, #1e40af 100%);
    border: none;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    box-shadow: 0 8px 24px #2563eb33, 0 2px 8px #0002;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.18s, background 0.18s;
    overflow: hidden;
}
.fab-refresh-btn:hover {
    box-shadow: 0 12px 32px #2563eb44, 0 3px 12px #0003;
    transform: scale(1.08);
    background: linear-gradient(135deg, #1e40af 60%, #2563eb 100%);
}
.fab-refresh-btn:active {
    transform: scale(0.97);
}
.fab-refresh-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.5s;
}
</style>';
echo '<button id="refreshBtn" class="fab-refresh-btn" title="Refresh Data">'
        .'<span id="refreshIcon" class="fab-refresh-icon">'
        .'<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.06-.27 2.06-.74 2.93l1.46 1.46C19.74 15.07 20 13.57 20 12c0-4.42-3.58-8-8-8zm-6.74 3.07L3.8 4.61C2.26 6.93 2 8.43 2 10c0 4.42 3.58 8 8 8v3l4-4-4-4v3c-3.31 0-6-2.69-6-6 0-1.06.27-2.06.74-2.93z"/></svg>'
        .'</span>'
        .'</button>'
        .'<script>'
        .'const btn = document.getElementById("refreshBtn");'
        .'const icon = document.getElementById("refreshIcon");'
        .'btn.addEventListener("click", function(e) {'
        .'  e.preventDefault();'
        .'  icon.style.transform = "rotate(360deg)";'
        .'  setTimeout(function() {'
        .'    icon.style.transform = "rotate(0deg)";'
        .'    location.reload();'
        .'  }, 500);'
        .'});'
        .'</script>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="icon" href="../admin/assets/jp_construction_logo.webp" type="image/webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            /* Image 2 Dark Header Color */
            --header-bg: #1C2136; 
            --accent-orange: #FF6E00;
            --text-white: #ffffff;
            --text-gray: #b0b3c5;
            --body-bg: #f5f7fa;
        }

        body { 
            background: var(--body-bg); 
            /* Header height (70px) + thoda gap taaki content overlap na ho */
            padding-top: 50px; 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; 
        }

        /* --- Header Styles like Image 2 --- */
        .modern-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 65px; /* Slim fixed height */
            background: var(--header-bg);
            color: var(--text-white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1030;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            /* Bottom Rounded Corners */
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        /* Profile Section */
        .profile-stack {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            /* Orange Border for Avatar */
            border: 2px solid var(--accent-orange); 
            object-fit: cover;
            background: #fff;
        }

        .user-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.1;
        }

        .user-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-white);
            margin: 0;
        }

        .user-role {
            font-size: 11px;
            color: var(--text-gray);
            margin: 0;
            opacity: 0.8;
        }

        /* Logout Button - Image 2 style (Orange Outline) */
        .logout-btn-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 32px;
            border: 1px solid var(--accent-orange);
            border-radius: 20px; /* Capsule shape */
            color: var(--accent-orange);
            background: transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .logout-btn-custom:hover {
            background: var(--accent-orange);
            color: #fff;
        }

        .back-arrow {
            color: white;
            margin-right: 10px;
            font-size: 1.3rem;
            text-decoration: none;
        }
    </style>
</head>
<body>

<header class="modern-header">
    <div class="profile-stack">
        <?php if ($show_back_btn): ?>
            <a href="dashboard.php" class="back-arrow"><i class="bi bi-arrow-left"></i></a>
        <?php endif; ?>
        
        <img src="<?= htmlspecialchars($worker_photo) ?>" class="header-avatar" alt="Profile">
        
        <div class="user-text">
            <div class="user-name"><?= htmlspecialchars($worker_name) ?></div>
            <div class="user-role"><?= htmlspecialchars($worker_profession) ?></div>
        </div>
    </div>

    <a href="logout.php" class="logout-btn-custom" aria-label="Logout">
        <i class="bi bi-box-arrow-right fs-5"></i>
    </a>
</header>

<main class="container">
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>