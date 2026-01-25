<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Privacy Policy | JP Construction</title>
    <meta name="description" content="Read JP Construction's privacy policy to understand how we collect, use, and protect your personal information when you use our website and services.">
    <meta name="keywords" content="JP Construction privacy, privacy policy, data protection, user privacy, India">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpconstruction.in/privacy.php">
    <meta name="theme-color" content="#0d6efd">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Privacy Policy | JP Construction">
    <meta property="og:description" content="Read JP Construction's privacy policy to understand how we collect, use, and protect your personal information when you use our website and services.">
    <meta property="og:url" content="https://jpconstruction.in/privacy.php">
    <meta property="og:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Privacy Policy | JP Construction">
    <meta name="twitter:description" content="Read JP Construction's privacy policy to understand how we collect, use, and protect your personal information when you use our website and services.">
    <meta name="twitter:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #8245ec;
            --text-dark: #2d3436;
            --text-muted: #636e72;
            --bg-light: #f8faff;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            scroll-behavior: smooth;
        }

        .privacy-container {
            margin-top: 40px;
            margin-bottom: 80px;
        }

        /* Side Navigation */
        .policy-nav {
            position: sticky;
            top: 100px;
            border-left: 2px solid #e0e0e0;
            padding-left: 20px;
        }

        .policy-nav a {
            display: block;
            padding: 8px 0;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .policy-nav a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }

        /* Main Content Card */
        .content-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            padding: 50px;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .page-header {
            margin-bottom: 40px;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 20px;
        }

        .page-header h1 {
            font-weight: 800;
            font-size: 2.2rem;
            color: #1a1a1a;
        }

        section {
            margin-bottom: 35px;
        }

        section h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            color: #1a1a1a;
        }

        section h2 i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.1rem;
            width: 25px;
            text-align: center;
        }

        section p {
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        /* Highlighted Info Box */
        .info-box {
            background: #f4f0ff;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid var(--primary-color);
            margin-top: 20px;
        }

        @media (max-width: 991px) {
            .policy-nav { display: none; }
            .content-card { padding: 30px; }
            .page-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <div class="container privacy-container">
        <div class="row">
            <div class="col-lg-3">
                <div class="policy-nav">
                    <h6 class="text-uppercase fw-bold small mb-3">Privacy Sections</h6>
                    <a href="#collection">1. Data Collection</a>
                    <a href="#usage">2. Data Usage</a>
                    <a href="#security">3. Security</a>
                    <a href="#cookies">4. Cookies</a>
                    <a href="#rights">5. Your Rights</a>
                    <a href="#contact">6. Contact</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="content-card">
                    <div class="page-header">
                        <h1>Privacy Policy</h1>
                        <p class="mb-0">Effective Date: <?= date('F d, Y') ?></p>
                    </div>

                    <section id="collection">
                        <h2><i class="fa-solid fa-database"></i> 1. Data Collection</h2>
                        <p>We collect personal information that you voluntarily provide to us, such as your name, email address, and phone number when you inquire about our construction services or register an account. We may also collect technical data like your IP address for security purposes.</p>
                    </section>

                    <section id="usage">
                        <h2><i class="fa-solid fa-chart-line"></i> 2. How We Use Your Information</h2>
                        <p>Your information is used to provide construction quotes, personalize your experience, and improve our website's functionality. We maintain a strict policy: <strong>We do not sell or rent your personal data to third-party marketers.</strong></p>
                    </section>

                    <section id="security">
                        <h2><i class="fa-solid fa-shield-halved"></i> 3. Data Security</h2>
                        <p>We use industry-standard encryption (SSL) and secure servers to protect your sensitive information. Access to your personal data is limited only to authorized employees who need it to process your requests.</p>
                        <div class="info-box">
                            <small class="text-primary fw-bold"><i class="fa-solid fa-circle-info me-1"></i> Note:</small>
                            <p class="small mb-0 mt-1">While we take every precaution, no internet transmission is 100% secure. We encourage you to use strong passwords for your account.</p>
                        </div>
                    </section>

                    <section id="cookies">
                        <h2><i class="fa-solid fa-cookie-bite"></i> 4. Cookies & Tracking</h2>
                        <p>Our website uses small files called cookies to understand how visitors interact with our site. This helps us optimize performance. You can disable cookies in your browser settings at any time.</p>
                    </section>

                    <section id="rights">
                        <h2><i class="fa-solid fa-user-check"></i> 5. Your Data Rights</h2>
                        <p>You have the right to request a copy of the data we hold about you, or ask us to delete your account entirely. To exercise these rights, simply email us with your request.</p>
                    </section>

                    <section id="contact">
                        <h2><i class="fa-solid fa-paper-plane"></i> 6. Contact Information</h2>
                        <p>For any privacy-related concerns or data requests, please contact our Data Protection Officer:</p>
                        
                        <div class="mt-4">
                            <div class="d-flex align-items-center p-3 border rounded-3 bg-light">
                                <i class="fa-solid fa-envelope-open-text fs-3 text-primary me-3"></i>
                                <div>
                                    <span class="d-block small text-muted">Send us an email</span>
                                    <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fw-bold text-dark">jp.maurya@gmail.com</a>
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