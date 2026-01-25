<?php
// documentation.php - Next Gen Documentation
$appName = "JP ERP";
$version = "2.0.0";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation | <?php echo $appName; ?></title>
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #64748b;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-width: 280px;
            --code-bg: #1e293b;
            --accent-gradient: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        }

        [data-bs-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --code-bg: #020617;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.7;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1030;
        }
        [data-bs-theme="dark"] .navbar {
            background: rgba(15, 23, 42, 0.8);
        }

        /* Sidebar */
        .doc-sidebar {
            position: sticky;
            top: 5rem;
            height: calc(100vh - 6rem);
            overflow-y: auto;
            border-right: 1px solid var(--border-color);
            padding-right: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            margin-bottom: 0.2rem;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .nav-link.active {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Typography */
        h1, h2, h3, h4 {
            color: var(--text-main);
            letter-spacing: -0.025em;
        }
        h1 { font-weight: 800; }
        .section-title {
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        /* Cards */
        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }
        .icon-box {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Terminal / Code Blocks */
        .terminal-window {
            background: var(--code-bg);
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            border: 1px solid #334155;
        }
        .terminal-header {
            background: rgba(255,255,255,0.05);
            padding: 0.5rem 1rem;
            display: flex; gap: 6px;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }
        .terminal-body {
            padding: 1.5rem;
            font-family: 'JetBrains Mono', monospace;
            color: #e2e8f0;
            font-size: 0.9rem;
        }
        .cmd { color: #4ade80; margin-right: 8px; }
        .comment { color: #64748b; font-style: italic; }

        /* Accordion */
        .accordion-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem !important;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .accordion-button {
            background: var(--bg-card);
            color: var(--text-main);
            font-weight: 600;
            box-shadow: none !important;
        }
        .accordion-button:not(.collapsed) {
            background: rgba(37, 99, 235, 0.05);
            color: var(--primary);
        }

        /* Architecture Flow */
        .flow-diagram {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            position: relative;
        }
        .flow-level {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
            z-index: 2;
        }
        .flow-node {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 0.5rem;
        }
        .flow-line {
            position: absolute;
            width: 2px;
            background: var(--border-color);
            z-index: 1;
            top: 20px; bottom: 20px; left: 50%;
            transform: translateX(-50%);
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border-color);
            padding: 3rem 0;
            margin-top: 5rem;
            background: var(--bg-card);
        }

        /* Mobile specific */
        @media (max-width: 991px) {
            .doc-sidebar { display: none; }
            .mobile-toc { display: block; margin-bottom: 2rem; }
        }
        @media (min-width: 992px) {
            .mobile-toc { display: none; }
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#doc-nav" data-bs-offset="100" tabindex="0">

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <div class="bg-primary text-white rounded p-1"><i class="bi bi-grid-1x2-fill"></i></div>
            <span><?php echo $appName; ?></span>
            <span class="badge bg-light text-primary border ms-2" style="font-size: 0.7rem;">v<?php echo $version; ?></span>
        </a>
        
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <i class="bi bi-list"></i> Menu
            </button>
            <div class="dropdown">
                <button class="btn btn-link nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-circle-half"></i> Theme
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><button class="dropdown-item" onclick="setTheme('light')"><i class="bi bi-sun me-2"></i>Light</button></li>
                    <li><button class="dropdown-item" onclick="setTheme('dark')"><i class="bi bi-moon-stars me-2"></i>Dark</button></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-lg-5 py-5">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <nav id="doc-nav" class="doc-sidebar">
                <div class="small fw-bold text-uppercase text-muted mb-3 ls-1">Onboarding</div>
                <nav class="nav nav-pills flex-column mb-4">
                    <a class="nav-link active" href="#overview">Overview</a>
                    <a class="nav-link" href="#installation">Installation</a>
                    <a class="nav-link" href="#architecture">Architecture</a>
                </nav>
                
                <div class="small fw-bold text-uppercase text-muted mb-3 ls-1">Modules</div>
                <nav class="nav nav-pills flex-column mb-4">
                    <a class="nav-link" href="#admin-panel">Admin Panel</a>
                    <a class="nav-link" href="#worker-portal">Worker Portal</a>
                    <a class="nav-link" href="#client-portal">Client Portal</a>
                </nav>

                <div class="small fw-bold text-uppercase text-muted mb-3 ls-1">Support</div>
                <nav class="nav nav-pills flex-column">
                    <a class="nav-link" href="#security">Security</a>
                    <a class="nav-link" href="#troubleshooting">Troubleshooting</a>
                </nav>
            </nav>
        </div>

        <div class="col-lg-8 col-xl-7 mx-auto">
            
            <section id="overview" class="mb-5 fade-in">
                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                    <i class="bi bi-stars"></i> <span>Introduction</span>
                </div>
                <h1 class="display-4 mb-4">The Operating System for Modern Construction.</h1>
                <p class="lead text-muted mb-4">
                    <?php echo $appName; ?> is a comprehensive ERP solution designed to digitize workforce management, 
                    automate attendance via AI, and streamline financial operations between contractors and clients.
                </p>
                <div class="row g-4 mt-2">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="icon-box"><i class="bi bi-cpu"></i></div>
                            <h5 class="fw-bold">AI Attendance</h5>
                            <p class="small text-muted mb-0">Face recognition & Geo-fenced QR scanning.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="icon-box"><i class="bi bi-wallet2"></i></div>
                            <h5 class="fw-bold">Smart Payroll</h5>
                            <p class="small text-muted mb-0">Auto-calculated wages & PDF payslips.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <div class="icon-box"><i class="bi bi-shield-check"></i></div>
                            <h5 class="fw-bold">Secure</h5>
                            <p class="small text-muted mb-0">Role-based access & encrypted data flow.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="installation" class="mb-5">
                <h3 class="section-title">Installation</h3>
                <p class="text-muted mb-4">Deploying the application takes less than 5 minutes on a standard LAMP/XAMPP stack.</p>

                <div class="terminal-window mb-4">
                    <div class="terminal-header">
                        <div class="dot dot-red"></div>
                        <div class="dot dot-yellow"></div>
                        <div class="dot dot-green"></div>
                    </div>
                    <div class="terminal-body">
                        <div class="mb-2"><span class="comment"># 1. Clone or Extract files to web root</span></div>
                        <div class="mb-3"><span class="cmd">$</span> cp -r nexus-erp/ /var/www/html/</div>
                        
                        <div class="mb-2"><span class="comment"># 2. Set Permissions for uploads</span></div>
                        <div class="mb-3"><span class="cmd">$</span> chmod -R 755 uploads/</div>
                        
                        <div class="mb-2"><span class="comment"># 3. Import Database</span></div>
                        <div class="mb-3"><span class="cmd">$</span> mysql -u root -p nexus_db < database/schema.sql</div>
                        
                        <div class="mb-2"><span class="comment"># 4. Configure Connection</span></div>
                        <div class="mb-1"><span class="cmd">$</span> nano admin/database.php</div>
                        <div class="text-white-50 ms-3">// Edit $servername, $username, $password</div>
                    </div>
                </div>
                
                <div class="alert alert-info border-0 d-flex gap-3 align-items-center">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    <div>
                        <strong>Requirements:</strong> PHP 7.4+, MySQL/MariaDB, GD Library (for images), and OpenSSL.
                    </div>
                </div>
            </section>

            <section id="architecture" class="mb-5">
                <h3 class="section-title">System Architecture</h3>
                <div class="p-5 bg-light rounded-4 border">
                    <div class="flow-diagram">
                        <div class="flow-line"></div>
                        
                        <div class="flow-level">
                            <div class="flow-node border-primary text-primary">
                                <i class="bi bi-database-fill"></i> Central Database
                            </div>
                        </div>

                        <div class="flow-level" style="margin: 2rem 0;">
                            <div class="flow-node"><i class="bi bi-shield-lock-fill text-danger"></i> Admin Core</div>
                            <div class="flow-node"><i class="bi bi-arrow-left-right text-muted"></i> API Layer</div>
                            <div class="flow-node"><i class="bi bi-cloud-arrow-up text-info"></i> Cloud Sync</div>
                        </div>

                        <div class="flow-level">
                            <div class="flow-node small"><i class="bi bi-person-badge"></i> Worker App</div>
                            <div class="flow-node small"><i class="bi bi-building"></i> Client Portal</div>
                            <div class="flow-node small"><i class="bi bi-qr-code"></i> Attendance</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="modules-section" class="mb-5">
                <h3 class="section-title">Core Modules</h3>
                
                <div id="admin-panel" class="mb-5">
                    <h4 class="fw-bold mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Admin Panel</h4>
                    <p class="text-muted">The control center for the entire operation. Accessible at <code>/admin</code>.</p>
                    
                    <div class="accordion" id="adminAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#adm1">
                                    Workforce Onboarding
                                </button>
                            </h2>
                            <div id="adm1" class="accordion-collapse collapse show" data-bs-parent="#adminAccordion">
                                <div class="accordion-body text-muted">
                                    Register new workers with KYC details (Aadhaar, Bank Info). Upload <strong>Reference Photos</strong> here to enable the Face Recognition engine.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#adm2">
                                    Financial Approvals
                                </button>
                            </h2>
                            <div id="adm2" class="accordion-collapse collapse" data-bs-parent="#adminAccordion">
                                <div class="accordion-body text-muted">
                                    Review UPI proofs uploaded by clients. Verify transaction IDs and approve payments to update the project ledger instantly.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#adm3">
                                    System Settings
                                </button>
                            </h2>
                            <div id="adm3" class="accordion-collapse collapse" data-bs-parent="#adminAccordion">
                                <div class="accordion-body text-muted">
                                    Configure global variables: Password policies, Maintenance Mode, Backup Schedules, and UI Themes.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="worker-portal" class="mb-5">
                    <h4 class="fw-bold mb-3"><i class="bi bi-helmet text-warning me-2"></i>Worker Portal</h4>
                    <p class="text-muted">Mobile-first interface for field staff. Accessible at <code>/worker</code>.</p>
                    <ul class="list-group list-group-flush border rounded">
                        <li class="list-group-item bg-transparent">
                            <strong><i class="bi bi-scan me-2"></i>Smart Attendance:</strong> Workers scan a daily QR code or use the "Selfie" mode for AI face matching.
                        </li>
                        <li class="list-group-item bg-transparent">
                            <strong><i class="bi bi-geo-alt me-2"></i>Geo-Tagging:</strong> Location is captured with every punch-in to prevent proxy attendance.
                        </li>
                        <li class="list-group-item bg-transparent">
                            <strong><i class="bi bi-calendar-check me-2"></i>Leave Mgmt:</strong> Simple interface to request days off and view approval status.
                        </li>
                    </ul>
                </div>

                <div id="client-portal" class="mb-5">
                    <h4 class="fw-bold mb-3"><i class="bi bi-briefcase text-success me-2"></i>Client Portal</h4>
                    <p class="text-muted">Transparency portal for customers. Accessible at <code>/contact</code>.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold">Payment Gateway</h6>
                                <p class="small mb-0">Scan admin UPI QR codes, upload screenshots, and track payment history.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold">Project Progress</h6>
                                <p class="small mb-0">View live status reports, attendance summaries, and project timelines.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="security" class="mb-5">
                <h3 class="section-title">Security Protocols</h3>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 text-success"><i class="bi bi-shield-check fs-2"></i></div>
                            <div>
                                <h5>SQL Injection Protection</h5>
                                <p class="text-muted small">All database interactions utilize <code>Prepared Statements (PDO/MySQLi)</code> to sanitize inputs.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 text-success"><i class="bi bi-incognito fs-2"></i></div>
                            <div>
                                <h5>Session Hijacking Prevention</h5>
                                <p class="text-muted small">Sessions are regenerated on login, with strict timeouts and IP binding checks.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0 text-success"><i class="bi bi-file-earmark-lock fs-2"></i></div>
                            <div>
                                <h5>File Validation</h5>
                                <p class="text-muted small">Uploads are strictly checked for MIME types (Images/PDFs only) to prevent shell execution.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <section id="troubleshooting" class="mb-5">
                 <h3 class="section-title">Troubleshooting</h3>
                 <div class="alert alert-warning border-0 text-dark">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Common Issues</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Camera Not Opening?</strong><br>
                            Ensure browser permissions are set to "Allow" for Camera. Needs HTTPS on live servers.
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Upload Failed?</strong><br>
                            Check `php.ini` settings: `upload_max_filesize` (Rec: 5M) and `post_max_size`.
                        </div>
                        <div class="col-md-6">
                            <strong>Login Loops?</strong><br>
                            Clear browser cookies or check `session.save_path` write permissions on the server.
                        </div>
                    </div>
                 </div>
            </section>

        </div>
    </div>
</div>

<footer>
    <div class="container text-center">
        <p class="mb-2 fw-bold"><?php echo $appName; ?></p>
        <p class="small text-muted mb-0">
            &copy; <?php echo date("Y"); ?> Engineered for Excellence. 
            <span class="mx-2">|</span> 
            <a href="#" class="text-decoration-none text-muted">Privacy</a>
            <span class="mx-2">|</span> 
            <a href="#" class="text-decoration-none text-muted">Support</a>
        </p>
    </div>
</footer>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><?php echo $appName; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group list-group-flush">
            <a href="#overview" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Overview</a>
            <a href="#installation" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Installation</a>
            <a href="#architecture" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Architecture</a>
            <div class="my-2 border-bottom"></div>
            <a href="#admin-panel" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Admin Panel</a>
            <a href="#worker-portal" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Worker Portal</a>
            <a href="#client-portal" class="list-group-item list-group-item-action border-0" onclick="closeMenu()">Client Portal</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Theme Toggling Logic
    function setTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
    }

    // Auto-detect system preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        setTheme(savedTheme);
    } else {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        setTheme(systemTheme);
    }

    // Mobile menu helper
    function closeMenu() {
        const menu = bootstrap.Offcanvas.getInstance(document.getElementById('mobileMenu'));
        menu.hide();
    }
</script>
</body>
</html>