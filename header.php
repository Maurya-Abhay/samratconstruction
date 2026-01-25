
<!-- PWA Manifest & Service Worker -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#1976d2">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/service-worker.js').catch(function(e) { console.log('SW registration failed:', e); });
        });
    }
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

<style>
    :root {
        --brand-purple: #8245ec;
        --dark-bg: #0A192F;
    }

    /* Navbar Basic Styling */
    .navbar {
        padding: 0rem 1rem;
        background: #fff !important;
        transition: all 0.3s ease;
    }

    .navbar-brand img {
        max-height: 60px;
        width: auto;
    }

    .nav-link {
        font-size: 0.9rem;
        font-weight: 600;
        color: #444 !important;
        padding: 0.5rem 12px !important;
    }

    .nav-link:hover, .nav-link.active {
        color: var(--brand-purple) !important;
    }

    /* Mobile Menu - Professional Floating Card */
    #mobileDropdownMenu {
        position: absolute;
        top: 70px;
        right: 15px;
        width: 260px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        border: 1px solid rgba(0,0,0,0.05);
        padding: 15px;
        list-style: none;
        display: none;
        z-index: 2000;
        transform-origin: top right;
        animation: fadeInScale 0.2s ease-out;
    }

    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    #mobileDropdownMenu.show { display: block; }

    /* Mobile Menu Items */
    .m-item {
        padding: 10px 15px;
        color: #333;
        text-decoration: none;
        display: flex;
        align-items: center;
        border-radius: 8px;
        font-weight: 500;
        margin-bottom: 5px;
    }

    .m-item:hover { background: #f4f0ff; color: var(--brand-purple); }

    /* Nested Login Menu for Mobile */
    .mobile-login-sub {
        background: #f8f9fa;
        border-radius: 8px;
        margin-top: 5px;
        padding-left: 15px;
    }

    .mobile-toggler {
        font-size: 1.8rem;
        color: var(--brand-purple);
        border: none;
        background: none;
    }

    @media (max-width: 991px) {
        .desktop-nav { display: none !important; }
    }
</style>

<div class="top-bar border-bottom py-1 d-none d-md-block">
    <div class="container text-center">
        <span class="me-3"><i class="bi bi-geo-alt text-primary"></i> Nagra, Saran, Bihar</span>
        <span class="me-3"><i class="bi bi-envelope text-success"></i> abhayprasad.maurya@gmail.com</span>
        <span><i class="bi bi-phone text-danger"></i> +91 00000 00000</span>
    </div>
</div>

<nav class="navbar navbar-expand-lg sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index">
            <img src="admin/assets/jp_construction_logo.webp" alt="Logo">
        </a>

        <button id="mobileMenuBtn" class="mobile-toggler d-lg-none">
            <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse desktop-nav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li><a class="nav-link" href="index.php">Home</a></li>
                <li><a class="nav-link" href="about.php">About</a></li>
                <li><a class="nav-link" href="services-static.php">Services</a></li>
                <li><a class="nav-link" href="contact.php">Contact</a></li>
                
                <li class="nav-item dropdown ms-2">
                    <a class="btn btn-sm btn-outline-primary dropdown-toggle rounded-pill px-3" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> Login
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                        <li><a class="dropdown-item py-2" href="admin/index.php"><i class="fa fa-user-shield me-2 text-primary"></i> Admin Panel</a></li>
                        <li><a class="dropdown-item py-2" href="attendence/login.php"><i class="fa fa-user-check me-2 text-success"></i> Attendance</a></li>
                        <li><a class="dropdown-item py-2" href="worker/login.php"><i class="fa fa-users-cog me-2 text-warning"></i> Worker Panel</a></li>
                        <li><a class="dropdown-item py-2" href="contact/login.php"><i class="fa fa-address-book me-2 text-danger"></i> Contact Panel</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <ul id="mobileDropdownMenu">
        <li><a href="index.php" class="m-item"><i class="bi bi-house me-2"></i> Home</a></li>
        <li><a href="about.php" class="m-item"><i class="bi bi-info-circle me-2"></i> About</a></li>
        <li><a href="services-static.php" class="m-item"><i class="bi bi-gear me-2"></i> Services</a></li>
        <li><a href="contact.php" class="m-item"><i class="bi bi-envelope me-2"></i> Contact</a></li>
        
        <hr class="my-2 opacity-10">
        
        <li class="fw-bold px-3 mb-2 text-muted small">LOGIN PANELS</li>
        <div class="mobile-login-sub">
            <li><a href="admin/index.php" class="m-item py-2"><i class="fa fa-user-shield me-2 text-primary"></i> Admin</a></li>
            <li><a href="attendence/login.php" class="m-item py-2"><i class="fa fa-user-check me-2 text-success"></i> Attendance</a></li>
            <li><a href="worker/login.php" class="m-item py-2"><i class="fa fa-users-cog me-2 text-warning"></i> Worker</a></li>
            <li><a href="contact/login.php" class="m-item py-2"><i class="fa fa-address-book me-2 text-danger"></i> Contact</a></li>
        </div>
        
        <li class="mt-3">
            <a href="#" class="btn btn-primary w-100 rounded-pill">Download App</a>
        </li>
    </ul>
</nav>

<script>
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileDropdownMenu');
    const menuIcon = menuBtn.querySelector('i');

    menuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isShown = mobileMenu.classList.toggle('show');
        menuIcon.classList.toggle('bi-list', !isShown);
        menuIcon.classList.toggle('bi-x', isShown);
    });

    // Bahar click karne par menu band ho jaye
    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
            mobileMenu.classList.remove('show');
            menuIcon.classList.add('bi-list');
            menuIcon.classList.remove('bi-x');
        }
    });
</script>