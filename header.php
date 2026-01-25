<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0b1c2c">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">

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
        --primary: #0b1c2c; /* Deep Navy */
        --accent: #ff9f1c;  /* Amber */
        --font-head: 'Oswald', sans-serif;
        --font-body: 'Manrope', sans-serif;
    }

    /* --- Top Bar (Dark) --- */
    .top-bar {
        background-color: var(--primary);
        color: rgba(255, 255, 255, 0.9);
        font-family: var(--font-body);
        font-size: 0.85rem;
        padding: 8px 0;
        border-bottom: 3px solid rgba(255, 255, 255, 0.05);
    }
    .top-bar i { color: var(--accent); margin-right: 6px; }
    .top-sep { margin: 0 15px; color: rgba(255,255,255,0.2); }

    /* --- Navbar (White) --- */
    .navbar {
        background: #fff !important;
        padding: 0.8rem 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border-bottom: 2px solid var(--accent); /* Thin Amber Line */
    }

    .navbar-brand img {
        max-height: 55px;
        width: auto;
    }

    .nav-link {
        font-family: var(--font-head);
        font-size: 1rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--primary) !important;
        padding: 0.5rem 15px !important;
        transition: 0.3s;
    }

    .nav-link:hover, .nav-link.active {
        color: var(--accent) !important;
    }

    /* Login Button Styled */
    .btn-nav-login {
        background: var(--primary);
        color: white !important;
        font-family: var(--font-head);
        text-transform: uppercase;
        letter-spacing: 1px;
        border-radius: 4px;
        padding: 8px 25px;
        border: 2px solid var(--primary);
        transition: 0.3s;
    }
    .btn-nav-login:hover {
        background: transparent;
        color: var(--primary) !important;
    }

    /* Dropdown Styling */
    .dropdown-menu {
        border-top: 3px solid var(--accent);
        border-radius: 0 0 8px 8px;
        margin-top: 15px !important;
    }
    .dropdown-item {
        font-family: var(--font-body);
        font-weight: 500;
        padding: 10px 20px;
        font-size: 0.95rem;
    }
    .dropdown-item:hover {
        background: rgba(255, 159, 28, 0.1); /* Light Amber */
        color: var(--primary);
    }

    /* --- Mobile Menu (Floating Card) --- */
    #mobileDropdownMenu {
        position: absolute;
        top: 85px;
        right: 15px;
        width: 280px;
        background: #fff;
        border-left: 4px solid var(--accent);
        border-radius: 4px;
        box-shadow: 0 20px 40px rgba(11, 28, 44, 0.2);
        padding: 20px;
        list-style: none;
        display: none;
        z-index: 2000;
        transform-origin: top right;
        animation: slideIn 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #mobileDropdownMenu.show { display: block; }

    /* Mobile Items */
    .m-item {
        padding: 12px 15px;
        color: var(--primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        font-family: var(--font-head);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #f0f0f0;
        transition: 0.2s;
    }
    .m-item i { width: 25px; color: var(--accent); }
    .m-item:hover { background: #f9f9f9; padding-left: 20px; color: var(--accent); }
    
    /* Mobile Toggle Button */
    .mobile-toggler {
        font-size: 2rem;
        color: var(--primary);
        border: none;
        background: none;
        padding: 0;
    }

    @media (max-width: 991px) {
        .desktop-nav { display: none !important; }
        .top-bar { display: none; } /* Optional: Hide top bar on mobile to save space */
    }
</style>

<div class="top-bar d-none d-lg-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="me-4"><i class="bi bi-geo-alt-fill"></i> Nagra, Saran, Bihar</span>
            <span><i class="bi bi-envelope-fill"></i> abhayprasad.maurya@gmail.com</span>
        </div>
        <div>
            <span><i class="bi bi-telephone-fill"></i> +91 00000 00000</span>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="admin/assets/jp_construction_logo.webp" alt="JP Construction">
        </a>

        <button id="mobileMenuBtn" class="mobile-toggler d-lg-none">
            <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse desktop-nav">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li><a class="nav-link" href="index.php">Home</a></li>
                <li><a class="nav-link" href="about.php">About</a></li>
                <li><a class="nav-link" href="services-static.php">Services</a></li>
                <li><a class="nav-link" href="contact.php">Contact</a></li>
                
                <li class="nav-item dropdown ms-3">
                    <a class="btn btn-nav-login dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill me-1"></i> Login
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        <li><a class="dropdown-item" href="admin/index.php"><i class="fa fa-user-shield me-2 opacity-50"></i> Admin Panel</a></li>
                        <li><a class="dropdown-item" href="attendence/login.php"><i class="fa fa-user-check me-2 opacity-50"></i> Attendance</a></li>
                        <li><a class="dropdown-item" href="worker/login.php"><i class="fa fa-users-cog me-2 opacity-50"></i> Worker Panel</a></li>
                        <li><a class="dropdown-item" href="contact/login.php"><i class="fa fa-address-book me-2 opacity-50"></i> Contact Panel</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <ul id="mobileDropdownMenu">
        <li><a href="index.php" class="m-item"><i class="bi bi-house-door"></i> Home</a></li>
        <li><a href="about.php" class="m-item"><i class="bi bi-info-circle"></i> About</a></li>
        <li><a href="services-static.php" class="m-item"><i class="bi bi-bricks"></i> Services</a></li>
        <li><a href="contact.php" class="m-item"><i class="bi bi-envelope-paper"></i> Contact</a></li>
        
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted fw-bold px-3 d-block mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">PORTALS</small>
            <li><a href="admin/index.php" class="m-item"><i class="fa fa-user-shield"></i> Admin</a></li>
            <li><a href="attendence/login.php" class="m-item"><i class="fa fa-user-check"></i> Attendance</a></li>
            <li><a href="worker/login.php" class="m-item"><i class="fa fa-users-cog"></i> Worker</a></li>
        </div>
        
        <li class="mt-3 px-2">
            <a href="#" class="btn btn-dark w-100 rounded-0 text-uppercase fw-bold" style="background: var(--primary); letter-spacing: 1px;">
                Download App <i class="bi bi-download ms-2"></i>
            </a>
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
        menuIcon.classList.toggle('bi-x-lg', isShown); // Updated icon to X-LG for cleaner look
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
            mobileMenu.classList.remove('show');
            menuIcon.classList.add('bi-list');
            menuIcon.classList.remove('bi-x-lg');
        }
    });
</script>