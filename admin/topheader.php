<?php



// Include the database connection file.

// Assumed to define the $conn mysqli connection object.
// --- Prevent Browser Caching ---
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include "lib_common.php";



// Start session only if not already active

if (session_status() !== PHP_SESSION_ACTIVE) {

  session_start();

}



// Lightweight analytics logging (non-blocking)

@include __DIR__ . '/analytics_track.php';



// ---------------------------------------------------------------------

// 1. Authentication Check

// ---------------------------------------------------------------------



// Check if the user is logged in (session email is set).

if (!isset($_SESSION['email'])) {

  header("Location: /samrat/admin/index.php");

    exit();

}



// ---------------------------------------------------------------------

// 2. Fetch Admin Data and Validation

// ---------------------------------------------------------------------



$admin_email = $_SESSION['email'];



// Query the database to fetch admin details using the session email.

$result = $conn->query("SELECT * FROM admin WHERE email='$admin_email'");

$admin = $result->fetch_assoc();



// Check if the admin user exists in the database.

if (!$admin) {

    // Fallback: logout if the admin record is not found (database mismatch).

    header("Location: logout.php");

    exit();

}



// The $admin variable now holds the authenticated administrator's data.



?>
<!-- Manual Refresh Button HTML -->
<div style="position:fixed;bottom:18px;right:18px;z-index:9999;">
  <button id="refreshBtn" title="Refresh Data" style="background:#0d6efd;border:none;border-radius:50%;width:54px;height:54px;box-shadow:0 4px 16px #0002;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;transition:box-shadow 0.2s;">
    <span id="refreshIcon" style="display:inline-block;transition:transform 0.5s;">
      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.06-.27 2.06-.74 2.93l1.46 1.46C19.74 15.07 20 13.57 20 12c0-4.42-3.58-8-8-8zm-6.74 3.07L3.8 4.61C2.26 6.93 2 8.43 2 10c0 4.42 3.58 8 8 8v3l4-4-4-4v3c-3.31 0-6-2.69-6-6 0-1.06.27-2.06.74-2.93z"/></svg>
    </span>
    <span id="refreshRing" style="position:absolute;top:0;left:0;width:54px;height:54px;display:none;align-items:center;justify-content:center;pointer-events:none;">
      <svg width="54" height="54" viewBox="0 0 54 54"><circle cx="27" cy="27" r="22" stroke="#fff" stroke-width="4" fill="none" stroke-dasharray="120" stroke-dashoffset="60"/></svg>
    </span>
  </button>
  <script>
  const btn = document.getElementById("refreshBtn");
  const icon = document.getElementById("refreshIcon");
  const ring = document.getElementById("refreshRing");
  btn.addEventListener("click", function(e) {
    e.preventDefault();
    icon.style.transform = "rotate(360deg)";
    ring.style.display = "flex";
    btn.style.boxShadow = "0 0 0 6px #0d6efd44";
    setTimeout(function() {
      location.reload();
    }, 600);
  });
  btn.addEventListener("mousedown", function() {
    btn.style.boxShadow = "0 0 0 10px #0d6efd33";
  });
  btn.addEventListener("mouseup", function() {
    btn.style.boxShadow = "0 4px 16px #0002";
  });
  </script>
</div>

<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Admin Dashboard</title>

  <link rel="icon" href="./assets/jp_construction_logo.webp" type="image/webp">

    <!-- PWA Manifest & Service Worker -->

    <link rel="manifest" href="/manifest.json">

    <meta name="theme-color" content="#1976d2">

    <script>

      if ('serviceWorker' in navigator) {

        window.addEventListener('load', function() {

          navigator.serviceWorker.register('/service-worker.js');

        });

      }

    </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>

    body {

      background: #f5f7fb;

      font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;

    }

    .sidebar {

      background: #2d3e6e;

      color: #fff;

      min-height: 100vh;

      width: 200px;

      position: fixed;

      left: 0; top: 0;

      z-index: 1000;

      box-shadow: 2px 0 16px #0001;

      display: flex;

      flex-direction: column;

      padding: 0;

    }

    .sidebar .logo {

      font-size: 1.5rem;

      font-weight: bold;

      padding: 1rem 1rem 0.5rem 1rem;

      display: flex;

      align-items: center;

      gap: 0.7rem;

      border-bottom: 1px solid #3a4a7c;

      position: sticky;

      top: 0;

      background: #2d3e6e;

      z-index: 10;

    }

    .sidebar .nav {

      flex: 1;

      padding: 0.2rem 0.5rem;

      overflow-y: auto;

      overflow-x: hidden;

    }

    .sidebar .nav::-webkit-scrollbar {

      width: 6px;

    }

    .sidebar .nav::-webkit-scrollbar-track {

      background: #3a4a7c;

      border-radius: 10px;

    }

    .sidebar .nav::-webkit-scrollbar-thumb {

      background: #5a6a9c;

      border-radius: 10px;

    }

    .sidebar .nav::-webkit-scrollbar-thumb:hover {

      background: #7a8abc;

    }

    .sidebar .nav-link {

      color: #fff;

      font-weight: 500;

      padding: 0.3rem 0.7rem;

      border-radius: 8px;

      margin-bottom: 0.2rem;

      display: flex;

      align-items: center;

      gap: 0.7rem;

      font-size: 0.9rem;

      transition: background 0.2s;

    }

    .sidebar .nav-link.active, .sidebar .nav-link:hover {

      background: #3a4a7c;

      color: #fff;

    }

    .main {

      margin-left: 200px;

      padding: 0;

      min-height: 100vh;

      background: #f5f7fb;

    }

    .header {

      background: #fff;

      padding: 0.5rem 1rem 0.5rem 1rem;

      display: flex;

      align-items: center;

      justify-content: space-between;

      border-bottom: 1px solid #e3e7ef;

      position: sticky;

      top: 0;

      z-index: 10;

    }

    /* .header .search and input removed */

    .header .user {

      display: flex;

      align-items: center;

      gap: 1.2rem;

    }

    .header .user .avatar {

      width: 40px; height: 40px;

      border-radius: 50%;

      object-fit: cover;

      border: 2px solid #e3e7ef;

    }

    .header .user .email {

      font-weight: 500;

      color: #2d3e6e;

      font-size: 1rem;

    }

    .dashboard-welcome {

      padding: 0.5rem 0.5rem 0.5rem 0.5rem;

      display: flex;

      align-items: center;

      gap: 1rem;

    }

    .dashboard-welcome .emoji {

      font-size: 2rem;

    }

    .dashboard-welcome .title {

      font-size: 1.5rem;

      font-weight: 600;

      color: #2d3e6e;

    }

    .stat-card {

      flex: 1 1 240px;

      min-width: 240px;

      max-width: 380px;

      border-radius: 16px;

      box-shadow: 0 6px 28px rgba(45, 62, 110, 0.12);

      background: #fff;

      padding: 0;

      margin-bottom: 0.5rem;

      position: relative;

      overflow: hidden;

      border: none;

      display: flex;

      align-items: stretch;

      height: 75px;

      transition: transform 0.2s, box-shadow 0.2s;

    }

    .stat-card:hover {

      transform: translateY(-4px);

      box-shadow: 0 8px 32px rgba(45, 62, 110, 0.18);

    }

    .stat-inner {

      border: 2px solid rgba(255,255,255,0.6);

      border-radius: 14px;

      margin: 6px;

      padding: 10px 18px;

      display: flex;

      align-items: center;

      gap: 0.9rem;

      background: transparent;

      width: 100%;

    }

    .stat-icon {

      font-size: 1.5rem;

      color: #fff;

      background: rgba(255,255,255,0.15);

      border-radius: 8px;

      width: 40px; height: 40px;

      display: flex; align-items: center; justify-content: center;

      border: 2px solid rgba(255,255,255,0.4);

      flex-shrink: 0;

    }

    .stat-label {

      font-size: 0.85rem;

      font-weight: 500;

      color: rgba(255,255,255,0.95);

      margin-bottom: 0.05rem;

      letter-spacing: 0.3px;

    }

    .stat-value {

      font-size: 1.5rem;

      font-weight: 700;

      color: #fff;

      line-height: 1.1;

    }

    .sidebar.hide {

      left: -200px !important;

      transition: left 0.3s;

    }

    @media (max-width: 991px) {

      .sidebar {

        width: 200px;

        left: -200px;

        transition: left 0.3s;

      }

      .sidebar.show {

        left: 0;

      }

      .main {

        margin-left: 0;

      }

    }

    @media (max-width: 600px) {

      .stats-row {

        display: grid !important;

        grid-template-columns: repeat(2, 1fr) !important;

        gap: 10px !important;

        padding: 1rem !important;

      }

      .stat-card {

        width: 100%;

        min-width: 0;

        max-width: 100%;

        height: 60px;

        margin-bottom: 0 !important;

      }

      .stat-inner {

        padding: 8px 10px;

        gap: 0.6rem;

      }

      .stat-icon {

        font-size: 1.2rem;

        width: 32px; height: 32px;

      }

      .stat-label {

        font-size: 0.75rem;

      }

      .stat-value {

        font-size: 1.1rem;

      }

      .dashboard-welcome {

        padding: 0rem !important;

      }

      .dashboard-welcome .title {

        font-size: 1.1rem;

      }

    }

  </style>

<script>

  document.addEventListener('DOMContentLoaded', function() {

    var sidebar = document.getElementById('sidebar');

    var toggleBtn = document.getElementById('sidebarToggle');

    // Show sidebar on toggle

    toggleBtn && toggleBtn.addEventListener('click', function(e) {

      e.stopPropagation();

      sidebar.classList.toggle('show');

    });

    // Hide sidebar when clicking outside (only for mobile)

    document.addEventListener('click', function(e) {

      if (window.innerWidth <= 991) {

        if (sidebar.classList.contains('show')) {

          // If click is outside sidebar and not on toggle button

          if (!sidebar.contains(e.target) && e.target !== toggleBtn) {

            sidebar.classList.remove('show');

          }

        }

      }

    });

    // Optional: Hide sidebar on resize if desktop

    window.addEventListener('resize', function() {

      if (window.innerWidth > 991) {

        sidebar.classList.remove('show');

      }

    });

  });

</script>

</head>

<script>

  document.addEventListener('DOMContentLoaded', function() {

    var sidebar = document.getElementById('sidebar');

    var toggleBtn = document.getElementById('sidebarToggle');

    var sidebarOverlay = document.getElementById('sidebarOverlay');

    var mainContent = document.getElementById('mainContent');

    function showSidebar() {

      sidebar.classList.add('show');

      sidebar.classList.remove('hide');

      if (sidebarOverlay) sidebarOverlay.style.display = window.innerWidth <= 991 ? 'block' : 'none';

      if (mainContent) mainContent.style.marginLeft = window.innerWidth > 991 ? '200px' : '0';

    }

    function hideSidebar() {

      sidebar.classList.remove('show');

      sidebar.classList.add('hide');

      if (sidebarOverlay) sidebarOverlay.style.display = 'none';

      if (mainContent) mainContent.style.marginLeft = '0';

    }

    // On page load, always hide sidebar for mobile

    if(window.innerWidth <= 991) {

      hideSidebar();

    } else {

      showSidebar();

    }

    if (toggleBtn) {

      toggleBtn.addEventListener('click', function(e) {

        e.stopPropagation();

        if (sidebar.classList.contains('show')) {

          hideSidebar();

        } else {

          showSidebar();

        }

      });

    }

    if (sidebarOverlay) {

      sidebarOverlay.addEventListener('click', function() {

        hideSidebar();

      });

    }

    // Hide sidebar when clicking outside (mobile only)

    document.addEventListener('click', function(e) {

      if(window.innerWidth <= 991 && sidebar.classList.contains('show')) {

        if (!sidebar.contains(e.target) && e.target !== toggleBtn) {

          hideSidebar();

        }

      }

    });

    window.addEventListener('resize', function() {

      if(window.innerWidth > 991) {

        showSidebar();

      } else {

        hideSidebar();

      }

    });

  });

</script>

<body>

 <div class="main" id="mainContent">

  <div class="header">

    <button class="btn btn-light" id="sidebarToggle" style="margin-right:1rem;"><i class="bi bi-list"></i></button>

    

    <div class="user dropdown">

      <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="gap: 0.8rem;">

        <img src="<?php echo isset($admin['photo']) ? htmlspecialchars($admin['photo']) : 'https://randomuser.me/api/portraits/men/1.jpg'; ?>" class="avatar shadow" alt="Admin" style="border: 2px solid #e3e7ef; box-shadow: 0 2px 8px #0001;">

        <div class="d-flex flex-column align-items-start ms-2">

          <span class="email fw-bold" style="color:#2d3e6e; font-size:1rem;"><?php echo isset($admin['name']) ? htmlspecialchars($admin['name']) : 'Admin'; ?></span>

          <span class="badge bg-warning text-dark" style="font-size:0.78rem;padding:5px 10px;border-radius:8px;box-shadow:0 2px 8px #0001;letter-spacing:1px;font-weight:600;">Super Admin</span>

        </div>

      </a>

      <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" aria-labelledby="userDropdown" style="min-width:180px; border-radius:14px;">

        <li>

          <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 rounded-3 fw-semibold" href="admin_profile.php" style="transition:background 0.2s;">

            <span style="background:#e3f2fd; color:#1976d2; border-radius:50%; padding:6px; display:inline-flex; align-items:center; justify-content:center;">

              <i class="fa fa-user" style="font-size:1.2rem;"></i>

            </span>

            <span style="margin-left:6px;">Profile</span>

          </a>

        </li>

        <li>

          <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 rounded-3 fw-semibold text-danger" href="logout.php" style="transition:background 0.2s;">

            <span style="background:#ffebee; color:#d32f2f; border-radius:50%; padding:6px; display:inline-flex; align-items:center; justify-content:center;">

              <i class="fa fa-sign-out-alt" style="font-size:1.2rem;"></i>

            </span>

            <span style="margin-left:6px;">Logout</span>

          </a>

        </li>

      </ul>

    </div>

</div>

