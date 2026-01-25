<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Privacy Policy | JP Construction</title>
    
    <meta name="description" content="Read JP Construction's privacy policy to understand how we collect, use, and protect your personal information.">
    <meta name="theme-color" content="#0b1c2c">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary: #0b1c2c;       /* Deep Navy */
            --accent: #ff9f1c;        /* Amber */
            --bg-light: #f4f7f6;      /* Industrial Light Gray */
            --text-body: #4a5568;     /* Slate Gray */
            --text-head: #0b1c2c;     /* Dark Navy */
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            font-family: var(--font-body);
            color: var(--text-body);
            line-height: 1.7;
            scroll-behavior: smooth;
        }

        .privacy-wrapper {
            padding: 60px 0 100px 0;
        }

        /* --- Sidebar Navigation --- */
        .policy-sidebar {
            position: sticky;
            top: 110px;
        }

        .sidebar-header {
            font-family: var(--font-head);
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: var(--accent);
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .nav-link-custom {
            display: block;
            padding: 10px 15px;
            color: var(--text-body);
            text-decoration: none;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link-custom:hover {
            color: var(--primary);
            background: rgba(0,0,0,0.03);
            border-left-color: rgba(11, 28, 44, 0.3);
        }

        .nav-link-custom.active {
            color: var(--primary);
            font-weight: 700;
            border-left-color: var(--accent);
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* --- Main Content Card --- */
        .content-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 15px 40px rgba(11, 28, 44, 0.08);
            padding: 60px;
            border-top: 5px solid var(--primary);
        }

        .page-header {
            margin-bottom: 50px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-family: var(--font-head);
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--text-head);
            text-transform: uppercase;
        }

        /* --- Sections --- */
        section {
            margin-bottom: 50px;
            scroll-margin-top: 120px;
        }

        section h2 {
            font-family: var(--font-head);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
        }

        section h2 i {
            color: var(--accent);
            font-size: 1.1rem;
            margin-right: 15px;
            background: rgba(255, 159, 28, 0.1);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        /* --- Info & Contact Boxes --- */
        .info-box {
            background: #fff8e1; /* Very light amber */
            border-left: 4px solid var(--accent);
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }

        .contact-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 25px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }
        
        .contact-box:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        @media (max-width: 991px) {
            .policy-sidebar { display: none; }
            .content-card { padding: 30px; }
            .privacy-wrapper { padding-top: 30px; }
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#privacyNav" data-bs-offset="150">

    <?php include "header.php"; ?>

    <div class="container privacy-wrapper">
        <div class="row">
            <div class="col-lg-3">
                <nav id="privacyNav" class="policy-sidebar">
                    <div class="sidebar-header">Privacy Sections</div>
                    <a href="#collection" class="nav-link-custom">1. Data Collection</a>
                    <a href="#usage" class="nav-link-custom">2. Data Usage</a>
                    <a href="#security" class="nav-link-custom">3. Security</a>
                    <a href="#cookies" class="nav-link-custom">4. Cookies</a>
                    <a href="#rights" class="nav-link-custom">5. Your Rights</a>
                    <a href="#contact" class="nav-link-custom">6. Contact</a>
                </nav>
            </div>

            <div class="col-lg-9">
                <div class="content-card">
                    <div class="page-header">
                        <h1>Privacy Policy</h1>
                        <p class="mb-0 text-muted"><i class="far fa-calendar-alt me-2 text-warning"></i>Effective Date: <?= date('F d, Y') ?></p>
                    </div>

                    <section id="collection">
                        <h2><i class="fa-solid fa-database"></i> 1. Data Collection</h2>
                        <p>At JP Construction Works, transparency is our foundation. We collect personal information that you voluntarily provide to us, such as your name, email address, and phone number when you inquire about our construction services or register an account. We may also collect technical data like your IP address for security purposes.</p>
                    </section>

                    <section id="usage">
                        <h2><i class="fa-solid fa-chart-line"></i> 2. How We Use Your Information</h2>
                        <p>Your information is used to provide accurate construction quotes, personalize your project experience, and improve our website's functionality. We maintain a strict policy: <strong>We do not sell, rent, or trade your personal data to third-party marketers.</strong> Your data stays within our secure ecosystem.</p>
                    </section>

                    <section id="security">
                        <h2><i class="fa-solid fa-shield-halved"></i> 3. Data Security</h2>
                        <p>We use industry-standard encryption (SSL) and secure servers to protect your sensitive information. Access to your personal data is restricted to authorized personnel who need it to process your requests.</p>
                        
                        <div class="info-box">
                            <strong class="text-dark d-block mb-1"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Important Note:</strong>
                            <p class="small mb-0 text-muted">While we implement robust security measures, no method of transmission over the Internet is 100% secure. We encourage you to use strong passwords and keep your login credentials confidential.</p>
                        </div>
                    </section>

                    <section id="cookies">
                        <h2><i class="fa-solid fa-cookie-bite"></i> 4. Cookies & Tracking</h2>
                        <p>Our website uses "cookies"—small data files—to understand how visitors interact with our site. This helps us optimize site performance and load times. You can choose to disable cookies through your browser settings, though some site features may function differently.</p>
                    </section>

                    <section id="rights">
                        <h2><i class="fa-solid fa-user-check"></i> 5. Your Data Rights</h2>
                        <p>You have the right to request a copy of the personal data we hold about you, request corrections to any inaccuracies, or ask us to delete your account entirely. To exercise these rights, simply submit a formal request to our support team.</p>
                    </section>

                    <section id="contact">
                        <h2><i class="fa-solid fa-paper-plane"></i> 6. Contact Information</h2>
                        <p>For any privacy-related concerns or data requests, please do not hesitate to contact our Data Protection Officer:</p>
                        
                        <div class="mt-4">
                            <div class="contact-box">
                                <i class="fa-solid fa-envelope-open-text fs-2 text-primary me-4"></i>
                                <div>
                                    <span class="d-block small text-muted text-uppercase fw-bold mb-1">Send us an email</span>
                                    <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fs-5 fw-bold text-dark">jp.maurya@gmail.com</a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>