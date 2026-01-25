<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Terms & Conditions | JP Construction</title>
    
    <meta name="description" content="Read the terms and conditions for using JP Construction's website and services.">
    <meta name="theme-color" content="#0b1c2c">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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

        /* --- Header Offset --- */
        .legal-wrapper {
            padding: 60px 0 100px 0;
        }

        /* --- Sidebar Navigation (Sticky) --- */
        .terms-sidebar {
            position: sticky;
            top: 110px; /* Adjust based on navbar height */
        }

        .sidebar-title {
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
        .legal-card {
            background: #ffffff;
            border-radius: 8px; /* Sharp industrial corners */
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

        .update-badge {
            display: inline-block;
            background: var(--bg-light);
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--text-body);
            border: 1px solid #e0e0e0;
        }

        /* --- Sections --- */
        section {
            margin-bottom: 50px;
            scroll-margin-top: 120px; /* Offset for anchor scrolling */
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
            font-size: 1.2rem;
            margin-right: 15px;
            background: rgba(255, 159, 28, 0.1);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        /* --- Contact Box --- */
        .contact-box {
            background: #f8fafc;
            border-left: 4px solid var(--accent);
            padding: 25px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .contact-box h6 {
            color: var(--primary);
            font-family: var(--font-head);
            margin-bottom: 5px;
        }

        .contact-box a {
            color: var(--text-body);
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .contact-box a:hover { color: var(--accent); }

        /* Responsive */
        @media (max-width: 991px) {
            .terms-sidebar { display: none; }
            .legal-card { padding: 30px; }
            .legal-wrapper { padding-top: 30px; }
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#legalNav" data-bs-offset="150">

    <?php include "header.php"; ?>

    <div class="container legal-wrapper">
        <div class="row">
            <div class="col-lg-3">
                <nav id="legalNav" class="terms-sidebar">
                    <div class="sidebar-title">Table of Contents</div>
                    <a href="#acceptance" class="nav-link-custom">1. Acceptance of Terms</a>
                    <a href="#responsibility" class="nav-link-custom">2. User Duty</a>
                    <a href="#property" class="nav-link-custom">3. Intellectual Property</a>
                    <a href="#liability" class="nav-link-custom">4. Liability</a>
                    <a href="#external" class="nav-link-custom">5. Third Party Links</a>
                    <a href="#governing" class="nav-link-custom">6. Governing Law</a>
                    <a href="#contact" class="nav-link-custom">7. Contact Us</a>
                </nav>
            </div>

            <div class="col-lg-9">
                <div class="legal-card">
                    <div class="page-header">
                        <h1>Terms & Conditions</h1>
                        <div class="mt-2">
                            <span class="update-badge"><i class="far fa-clock me-2"></i>Last Updated: <?= date('F d, Y') ?></span>
                        </div>
                    </div>

                    <section id="acceptance">
                        <h2><i class="fa-solid fa-file-contract"></i> 1. Acceptance of Terms</h2>
                        <p>Welcome to JP Construction Works. By accessing or using this website, you agree to be bound by these Terms and Conditions. Our services are provided to you subject to your compliance with these rules. If you do not agree with any part of these terms, please cease use of this website immediately.</p>
                    </section>

                    <section id="responsibility">
                        <h2><i class="fa-solid fa-user-shield"></i> 2. User Responsibilities</h2>
                        <p>You agree to use this website only for lawful purposes. You are strictly prohibited from using the site to infringe on the rights of others, transmit harmful content (viruses, malware, or spyware), or perform any activity that could damage the security, integrity, or availability of this platform.</p>
                    </section>

                    <section id="property">
                        <h2><i class="fa-solid fa-copyright"></i> 3. Intellectual Property</h2>
                        <p>All content hosted on this website, including but not limited to text, architectural drawings, project photos, logos, and software code, is the exclusive property of <strong>JP Construction Works</strong>. You may not reuse, reproduce, modify, or distribute any content without explicit prior written consent from our management.</p>
                    </section>

                    <section id="liability">
                        <h2><i class="fa-solid fa-hard-hat"></i> 4. Limitation of Liability</h2>
                        <p>While we strive for excellence and accuracy, we do not guarantee that the website will be entirely error-free or uninterrupted. JP Construction Works shall not be liable for any direct, indirect, incidental, or consequential damages arising from the use or inability to use the site, including data loss or business interruption.</p>
                    </section>

                    <section id="external">
                        <h2><i class="fa-solid fa-link"></i> 5. External Links</h2>
                        <p>This site may contain links to third-party websites (such as social media profiles or partner portals). We are not responsible for the content, privacy policies, or practices of those external websites. Accessing them is at your own sole risk.</p>
                    </section>

                    <section id="governing">
                        <h2><i class="fa-solid fa-gavel"></i> 6. Governing Law</h2>
                        <p>These terms constitute a binding legal agreement between you and JP Construction Works. They are governed by the laws of <strong>Bihar, India</strong>. Any legal disputes arising hereunder shall be resolved exclusively by the local courts of Saran/Bihar, India.</p>
                    </section>

                    <section id="contact">
                        <h2><i class="fa-solid fa-envelope-open-text"></i> 7. Contact Us</h2>
                        <p>If you have any questions regarding these Terms or need clarification on any legal aspect of our services, please feel free to reach out to our team.</p>
                        
                        <div class="contact-box">
                            <i class="fa-solid fa-headset fs-2 text-muted"></i>
                            <div>
                                <h6>Legal & Support Inquiry</h6>
                                <a href="mailto:abhayprasad.maurya@gmail.com">jp.maurya@gmail.com</a>
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