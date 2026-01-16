<?php
// header.php - Simple Minimalist Dark Header
date_default_timezone_set('Asia/Kolkata');

// 1. Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }

// --- Attendance Panel User (The one who is logged in) ---
$attendance_id = $_SESSION['attendance_id'];
$attendance_name = 'Abhay Prasad'; // Required name
$attendance_role = 'Attendance Panel'; // Required role
// Default avatar using UI Avatars API
$attendance_photo = "https://ui-avatars.com/api/?name=" . urlencode($attendance_name) . "&background=6366f1&color=fff&size=40";

// 3. Database Lookup (If $conn exists)
// Note: This block updates defaults if database connection is available
if (isset($conn)) {
    try {
        $stmt = $conn->prepare("SELECT name, image, role FROM attendence_users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $attendance_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Update name/role from DB if available, else use default 'Abhay Prasad'
                $attendance_name = htmlspecialchars($row['name'] ?? 'Abhay Prasad'); 
                $attendance_role = htmlspecialchars($row['role'] ?? 'Attendance Panel');
                
                $db_image = $row['image'] ?? '';
                $attendance_photo = !empty($db_image) ? htmlspecialchars($db_image) : "https://ui-avatars.com/api/?name=" . urlencode($attendance_name) . "&background=6366f1&color=fff&size=40";
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Continue with default values if DB fails
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* --- CSS Variables --- */
        :root {
            --bg-dark: #1e293b;       /* Dark Slate */
            --header-bg: #0f172a;     /* Deep Blue/Black for header */
            --primary: #4f46e5;       /* Modern Indigo */
            --text-white: #e2e8f0;    
            --text-muted: #94a3b8;
        }

        /* --- Global Body Styles --- */
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark); 
            color: var(--text-white);
            overflow-x: hidden;
            padding-top: 65px; /* Space for fixed header */
            min-height: 100vh;
        }

        /* --- Fixed Header Navbar (Simple and Solid) --- */
        .app-navbar {
            background: var(--header-bg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            height: 65px;
            z-index: 1000;
        }

        /* --- User Pill (Profile Info) --- */
        .user-pill {
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05); /* Subtle hover/focus area */
        }

        .user-avatar-sm {
            width: 35px; height: 35px;
            border-radius: 50%;
            object-fit: cover; 
            border: 2px solid var(--primary);
        }
        .user-info-text {
            line-height: 1.2;
        }
        .user-name {
            font-weight: 700;
            color: var(--text-white);
            font-size: 0.95rem;
        }
        .user-role {
            color: var(--text-muted);
            font-size: 0.7rem;
            display: block; /* Ensure it stays on its own line */
        }
        
        /* --- Logout Button --- */
        .logout-btn {
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 6px;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.1);
            transition: 0.2s;
        }
        .logout-btn:hover { 
            background: #ef4444; /* Red for logout */
            color: white; 
        }
    </style>
</head>
<body>

<nav class="navbar fixed-top app-navbar">
    <div class="container-fluid px-lg-4">
        
        <div class="d-flex align-items-center gap-3">
            <div class="user-pill">
                <img src="<?= $attendance_photo ?>" class="user-avatar-sm me-2" alt="<?= $attendance_name ?> Photo">
                <div class="user-info-text">
                    <span class="user-name"><?= $attendance_name ?></span>
                    <span class="user-role"><?= $attendance_role ?></span>
                </div>
            </div>
        </div>
        
        <div class="d-flex align-items-center">
            <a href="logout.php" class="logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
        
    </div>
</nav>