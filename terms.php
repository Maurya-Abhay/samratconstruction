<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Terms & Conditions | JP Construction</title>
    <meta name="description" content="Read the terms and conditions for using JP Construction's website and services. Understand your rights and responsibilities as a user.">
    <meta name="keywords" content="JP Construction terms, terms and conditions, website policy, user agreement, India">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpconstruction.in/terms.php">
    <meta name="theme-color" content="#0d6efd">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Terms & Conditions | JP Construction">
    <meta property="og:description" content="Read the terms and conditions for using JP Construction's website and services. Understand your rights and responsibilities as a user.">
    <meta property="og:url" content="https://jpconstruction.in/terms.php">
    <meta property="og:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Terms & Conditions | JP Construction">
    <meta name="twitter:description" content="Read the terms and conditions for using JP Construction's website and services. Understand your rights and responsibilities as a user.">
    <meta name="twitter:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
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

        /* Header Offset for Sticky Navbar */
        .legal-container {
            margin-top: 40px;
            margin-bottom: 80px;
        }

        /* Sidebar Nav */
        .terms-nav {
            position: sticky;
            top: 100px;
            border-left: 2px solid #e0e0e0;
            padding-left: 20px;
        }

        .terms-nav a {
            display: block;
            padding: 8px 0;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .terms-nav a:hover, .terms-nav a.active {
            color: var(--primary-color);
            font-weight: 600;
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

        .page-header p {
            color: var(--text-muted);
            font-size: 1rem;
        }

        section {
            margin-bottom: 40px;
        }

        section h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            color: #1a1a1a;
        }

        section h2 i {
            margin-right: 12px;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        section p {
            line-height: 1.8;
            color: var(--text-muted);
            margin-bottom: 0;
        }

        /* Contact Box */
        .contact-box {
            background: #f4f0ff;
            border-radius: 15px;
            padding: 25px;
            border: 1px dashed var(--primary-color);
            margin-top: 30px;
        }

        @media (max-width: 991px) {
            .terms-nav { display: none; }
            .content-card { padding: 30px; }
        }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <div class="container legal-container">
        <div class="row">
            <div class="col-lg-3">
                <div class="terms-nav">
                    <h6 class="text-uppercase fw-bold small mb-3">Sections</h6>
                    <a href="#acceptance">1. Acceptance</a>
                    <a href="#responsibility">2. User Duty</a>
                    <a href="#property">3. Intellectual Property</a>
                    <a href="#liability">4. Liability</a>
                    <a href="#external">5. Third Party Links</a>
                    <a href="#governing">6. Governing Law</a>
                    <a href="#contact">7. Contact Us</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="content-card">
                    <div class="page-header">
                        <h1>Terms & Conditions</h1>
                        <p>Last Updated: <?= date('F d, Y') ?></p>
                    </div>

                    <section id="acceptance">
                        <h2><i class="fa-solid fa-file-contract"></i> 1. Acceptance of Terms</h2>
                        <p>By accessing or using this website, you agree to be bound by these Terms and Conditions. Our services are provided to you subject to your compliance with these rules. If you do not agree, please cease use of this website immediately.</p>
                    </section>

                    <section id="responsibility">
                        <h2><i class="fa-solid fa-user-shield"></i> 2. User Responsibilities</h2>
                        <p>You agree to use the website only for lawful purposes. You are strictly prohibited from using the site to infringe on the rights of others, transmit harmful content (viruses/malware), or perform any activity that could damage the security of this platform.</p>
                    </section>

                    <section id="property">
                        <h2><i class="fa-solid fa-copyright"></i> 3. Intellectual Property</h2>
                        <p>All content including text, graphics, logos, images, and software is the property of <strong>JP Construction Works</strong>. You may not reuse, modify, or distribute any content without explicit prior written consent from our management.</p>
                    </section>

                    <section id="liability">
                        <h2><i class="fa-solid fa-handshake-slash"></i> 4. Limitation of Liability</h2>
                        <p>While we strive for excellence, we do not guarantee that the website will be entirely error-free. We are not liable for any direct or indirect damages arising from the use or inability to use the site, including data loss or business interruption.</p>
                    </section>

                    <section id="external">
                        <h2><i class="fa-solid fa-link"></i> 5. External Links</h2>
                        <p>This site may contain links to third-party websites (like GitHub or LinkedIn). We are not responsible for the content or privacy policies of those external websites. Accessing them is at your own risk.</p>
                    </section>

                    <section id="governing">
                        <h2><i class="fa-solid fa-gavel"></i> 6. Governing Law</h2>
                        <p>These terms are governed by the laws of <strong>Bihar, India</strong>. Any legal disputes arising hereunder shall be resolved exclusively by the local courts of Saran/Bihar, India.</p>
                    </section>

                    <section id="contact">
                        <h2><i class="fa-solid fa-envelope-open-text"></i> 7. Contact Us</h2>
                        <p>If you have any questions regarding these Terms, feel free to reach out to our legal team.</p>
                        
                        <div class="contact-box">
                            <div class="d-flex align-items-center">
                                <div class="icon-circle me-3">
                                    <i class="fa-solid fa-headset text-primary fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold">Support Email</h6>
                                    <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fw-bold text-primary">jp.maurya@gmail.com</a>
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