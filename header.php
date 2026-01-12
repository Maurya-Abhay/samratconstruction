<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Bootstrap Icons -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

  <!-- Font Awesome -->

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>

    .logo-container {

      width: 200px;

      height: 60px;

      overflow: hidden;

      background-color: #000;

    }

    .logo-img {

      width: 100%;

      height: 100%;

      object-fit: cover;

      mix-blend-mode: lighten;

    }

    /* Custom purple color */

    .text-purple {

      color: #8245ec !important;

    }

    .text-purple-hover:hover {

      color: #8245ec !important;

      background-color: #8245ec22 !important;

      transition: all 0.2s ease-in-out;

    }

    /* Mobile dropdown menu */

    #mobileDropdownMenu {

      backdrop-filter: blur(10px);

      background-color: rgba(255, 255, 255, 0.95);

      min-width: 140px;

      border-radius: 1rem;

      box-shadow: 0 8px 24px rgba(130, 69, 236, 0.25);

      border: 1px solid #8245ec40;

      position: absolute;

      top: 56px; /* height of navbar + margin */

      right: 8px;

      z-index: 1050;

      display: none; /* initially hidden */

      flex-direction: column;

      padding: 0.5rem 0;

    }

    #mobileDropdownMenu.show {

      display: flex;

    }

    #mobileDropdownMenu li {

      list-style: none;

    }

    #mobileDropdownMenu a.dropdown-item {

      font-weight: 600;

      padding: 0.5rem 1.2rem;

      color: #4a4a4a;

      text-align: left;

      border-radius: 0.5rem;

      cursor: pointer;

      transition: background-color 0.2s ease, color 0.2s ease;

      display: block;

    }

    #mobileDropdownMenu a.dropdown-item:hover {

      background-color: #8245ec22;

      color: #8245ec;

    }

    #mobileDropdownMenu a.dropdown-item.active {

      background-color: #8245ec22;

      color: #8245ec;

    }

    /* Hide default toggler on mobile, custom button shown instead */

    .navbar-toggler {

      display: none;

    }

    /* Show custom toggler on mobile */

    .mobile-toggler {

      display: flex;

      align-items: center;

      justify-content: center;

      background: transparent;

      border: none;

      font-size: 1.8rem;

      color: #8245ec;

      cursor: pointer;

    }

    /* Show mobile toggler on small screens only */

    @media (max-width: 991.98px) {

      .navbar-collapse {

        display: none !important;

      }

      .mobile-toggler {

        display: flex;

      }

    }

  </style>

</head>

<body>
<!-- Manual Refresh Button HTML -->

<!-- Smaller Manual Refresh Button HTML -->
<div style="position:fixed;bottom:18px;right:18px;z-index:9999;">
  <button id="refreshBtn" title="Refresh Data" style="background:#0d6efd;border:none;border-radius:50%;width:40px;height:40px;box-shadow:0 2px 8px #0002;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;transition:box-shadow 0.2s;">
    <span id="refreshIcon" style="display:inline-block;transition:transform 0.5s;">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.06-.27 2.06-.74 2.93l1.46 1.46C19.74 15.07 20 13.57 20 12c0-4.42-3.58-8-8-8zm-6.74 3.07L3.8 4.61C2.26 6.93 2 8.43 2 10c0 4.42 3.58 8 8 8v3l4-4-4-4v3c-3.31 0-6-2.69-6-6 0-1.06.27-2.06.74-2.93z"/></svg>
    </span>
    <span id="refreshRing" style="position:absolute;top:0;left:0;width:40px;height:40px;display:none;align-items:center;justify-content:center;pointer-events:none;">
      <svg width="40" height="40" viewBox="0 0 40 40"><circle cx="20" cy="20" r="16" stroke="#fff" stroke-width="3" fill="none" stroke-dasharray="80" stroke-dashoffset="40"/></svg>
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
    btn.style.boxShadow = "0 0 0 4px #0d6efd44";
    setTimeout(function() {
      location.reload();
    }, 500);
  });
  btn.addEventListener("mousedown", function() {
    btn.style.boxShadow = "0 0 0 8px #0d6efd33";
  });
  btn.addEventListener("mouseup", function() {
    btn.style.boxShadow = "0 2px 8px #0002";
  });
  </script>
</div>
<?php
// Public-side lightweight analytics logger
@include __DIR__ . '/admin/analytics_track.php';
?>



  <!-- Top Contact Bar -->

  <div class="container-fluid bg-light border-bottom py-2 d-none d-md-block">

    <div class="row justify-content-center align-items-center">

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
      <div class="col-auto text-center">

        <div class="d-inline-flex flex-wrap align-items-center small fw-semibold text-dark">

          <i class="bi bi-geo-alt me-2 text-primary"></i> Nagra, Saran, Bihar

          <span class="mx-3 text-muted">|</span>

          <i class="bi bi-envelope-open me-2 text-success"></i> abhayprasad.maurya@gmail.com

          <span class="mx-3 text-muted">|</span>

          <i class="bi bi-phone-vibrate me-2 text-danger"></i> +91 9661329757

        </div>

      </div>

    </div>

  </div>



  <!-- Main Navbar -->

 <?php
// --- Dynamic Header Data from site_settings ---
require_once __DIR__ . '/admin/database.php';
$header_settings = [];
$keys = [
    'logo_url', 'app_download_url'
];
$res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
while ($row = $res->fetch_assoc()) {
    $header_settings[$row['setting_key']] = $row['setting_value'];
}
?>
<nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm">

    <div class="container d-flex align-items-center justify-content-between">

      <a class="navbar-brand d-flex align-items-center" href="index">

        <img src="<?= htmlspecialchars($header_settings['logo_url'] ?? 'admin/assets/111.png') ?>" alt="Logo" width="150" class="img-fluid rounded" />

      </a>

      <?php if (!empty($header_settings['app_download_url'])): ?>

      <a href="<?= htmlspecialchars($header_settings['app_download_url']) ?>" class="btn btn-outline-primary ms-3 rounded-pill px-4 d-none d-lg-inline-flex" target="_blank">

        <i class="bi bi-download"></i> Download App

      </a>

      <?php endif; ?>



      <!-- Custom Mobile Menu Toggler -->

      <button id="mobileMenuBtn" class="mobile-toggler d-lg-none" aria-label="Toggle menu">

        <i class="bi bi-list"></i>

      </button>



      <!-- Desktop Menu -->

      <div class="navbar-collapse">

        <ul class="navbar-nav ms-auto fw-semibold align-items-lg-center">

          <li class="nav-item">

            <a class="nav-link active" href="index.php">Home</a>

          </li>

          <li class="nav-item">

            <a class="nav-link active" href="about.php">About</a>

          </li>

          <li class="nav-item">

            <a class="nav-link active" href="services-static.php">Services</a>

          </li>

          <li class="nav-item">

            <a class="nav-link active" href="contact.php">Contact</a>

          </li>

          <li class="nav-item">

            <a class="nav-link active" href="terms.php">Terms</a>

          </li>

          <li class="nav-item">

            <a class="nav-link active" href="privacy.php">Privacy</a>

          </li>



          <!-- Login Dropdown -->

          <li class="nav-item dropdown">

            <a

              class="nav-link dropdown-toggle active"

              href="#"

              id="loginDropdown"

              role="button"

              data-bs-toggle="dropdown"

              aria-expanded="false"

            >

              Login

            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 rounded-3" aria-labelledby="loginDropdown">

              <li>

                <a class="dropdown-item" href="admin/index.php">

                  <i class="fa fa-user-shield me-2 text-primary"></i>Admin Login

                </a>

              </li>

              <li>

                <a class="dropdown-item" href="attendence/login.php">

                  <i class="fa fa-user-check me-2 text-success"></i>Attendance Login

                </a>

              </li>

              <li>

                <a class="dropdown-item" href="worker/login.php">

                  <i class="fa fa-users-cog me-2 text-warning"></i>Worker Login

                </a>

              </li>

              <li>

                <a class="dropdown-item" href="contact/login.php">

                  <i class="fa fa-address-book me-2 text-danger"></i>Contact Login

                </a>

              </li> 

            </ul>

          </li>

          <li class="nav-item me-2">

            <a href="https://github.com/Maurya-Abhay/smrtbuild-app-release/releases/download/v1.0/app-debug.apk" target="_blank" class="btn btn-outline-primary fw-bold px-3 py-2" style="border-radius: 30px;">

              <i class="fa fa-download me-2"></i>Download App

            </a>

          </li>

        </ul>

      </div>

    </div>



    <!-- Mobile Menu Items -->

    <ul id="mobileDropdownMenu" class="">

      <li><a href="index.php" class="dropdown-item active">Home</a></li>

      <li><a href="about.php" class="dropdown-item">About</a></li>

      <li><a href="services-static.php" class="dropdown-item">Services</a></li>

      <li><a href="contact.php" class="dropdown-item">Contact</a></li>

      <li><a href="terms.php" class="dropdown-item">Terms</a></li>

      <li><a href="privacy.php" class="dropdown-item">Privacy</a></li>

      <li><hr class="dropdown-divider"></li>

      <li><a href="admin/index.php" class="dropdown-item"><i class="fa fa-user-shield me-2 text-primary"></i>Admin Login</a></li>

      <li><a href="attendence/login.php" class="dropdown-item"><i class="fa fa-user-check me-2 text-success"></i>Attendance Login</a></li>

      <li><a href="worker/login.php" class="dropdown-item"><i class="fa fa-users-cog me-2 text-warning"></i>Worker Login</a></li>

      <li><a href="contact/login.php" class="dropdown-item"><i class="fa fa-address-book me-2 text-danger"></i>Contact Login</a></li>

      <li class="mt-3 mb-2 text-center">

        <a href="https://github.com/Maurya-Abhay/smrtbuild-app-release/releases/download/v1.0/app-debug.apk" target="_blank" class="btn btn-outline-primary fw-bold px-3 py-2 w-100" style="border-radius: 30px;">

          <i class="fa fa-download me-2"></i>Download App

        </a>

      </li>

    </ul>

  </nav>





  <!-- Custom JS for mobile menu toggling -->

  <script>

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');

    const mobileDropdownMenu = document.getElementById('mobileDropdownMenu');

    const menuIcon = mobileMenuBtn.querySelector('i');



    mobileMenuBtn.addEventListener('click', () => {

      const isShown = mobileDropdownMenu.classList.toggle('show');



      // Toggle icon between hamburger and close

      if (isShown) {

        menuIcon.classList.remove('bi-list');

        menuIcon.classList.add('bi-x');

      } else {

        menuIcon.classList.remove('bi-x');

        menuIcon.classList.add('bi-list');

      }

    });



    // Optional: Close menu when clicking outside

    document.addEventListener('click', (e) => {

      if (!mobileDropdownMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {

        mobileDropdownMenu.classList.remove('show');

        menuIcon.classList.remove('bi-x');

        menuIcon.classList.add('bi-list');

      }

    });

  </script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>