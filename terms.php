<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- PWA Manifest and Theme Color -->
    <link rel="manifest" href="/htdocs/manifest.json">
    <meta name="theme-color" content="#0d6efd">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Removed redundant/old CSP, relying on modern browser security practices -->
    
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png" />

    <title>Terms & Conditions - Construction Company</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Font Awesome & Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        /* Define a custom primary color variable for consistency */
        :root {
            --bs-primary-dark: #0056b3; /* Darker shade of Bootstrap Primary Blue */
            --bs-secondary-text: #495057; /* Slightly darker than default text */
            --bs-section-bg: #ffffff;
            --bs-body-bg: #f5f7fa; /* Light, modern background */
        }

        body {
            background-color: var(--bs-body-bg);
            font-family: 'Inter', sans-serif; /* A modern font preference */
        }

        /* Modernize the main content container */
        .content-card {
            background-color: var(--bs-section-bg);
            border-radius: 1rem; /* More rounded corners */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Deeper, softer shadow */
            padding: 2.5rem !important; /* Increase padding */
            transition: transform 0.3s ease;
        }

        /* Style the main title with a clean gradient */
        .page-title {
            font-weight: 700;
            font-size: 2.5rem;
            text-align: center;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem !important;
            
            /* Modern Gradient Effect */
            background: linear-gradient(45deg, var(--bs-primary), var(--bs-primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent; /* Fallback for browsers */
        }

        /* Style for section headings */
        h2 {
            color: var(--bs-primary-dark);
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 3px solid rgba(0, 123, 255, 0.1); /* Subtle underline */
            padding-bottom: 0.25rem;
            margin-bottom: 1rem;
        }

        p {
            line-height: 1.7;
            color: var(--bs-secondary-text);
            margin-bottom: 1.25rem;
        }

        /* Subtle separator line */
        .section-divider {
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0));
            margin: 2rem 0;
        }

        /* Footer styling */
        footer {
            text-align: center;
            font-size: 0.9em;
            padding: 1.5em;
            color: #777;
        }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <div class="container my-5">
        <!-- Replaced default classes with the custom 'content-card' -->
        <div class="content-card">
            <h1 class="page-title">Website Terms & Conditions</h1>

            <!-- Section 1 -->
            <section>
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing or using this website, you agree to be bound by these Terms and Conditions. If you do not agree, please cease use of this website immediately.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 2 -->
            <section>
                <h2>2. User Responsibilities</h2>
                <p>You agree to use the website only for lawful purposes. You are strictly prohibited from using the site to infringe on the rights of others, transmit harmful content, or perform any activity that could damage or impair the functionality, security, or integrity of this site or its associated services.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 3 -->
            <section>
                <h2>3. Intellectual Property</h2>
                <p>All content including text, graphics, logos, images, and software is the property of Samrat Construction and is protected by international copyright and trademark laws. You may not reuse, modify, or distribute any content without explicit prior written consent from Samrat Construction.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 4 -->
            <section>
                <h2>4. Limitation of Liability</h2>
                <p>We do not guarantee that the website will be entirely error-free or uninterrupted. We are not liable for any direct, indirect, incidental, consequential, or punitive damages arising from the use or inability to use the site or its content, even if we have been advised of the possibility of such damages.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 5 -->
            <section>
                <h2>5. External Links</h2>
                <p>This site may contain links to third-party websites for your convenience. We are not responsible for the content, privacy policies, or practices of those external websites and do not implicitly endorse them. Accessing any linked site is at your own risk.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 6 -->
            <section>
                <h2>6. Termination</h2>
                <p>We reserve the right to suspend or terminate your access to the website or any of its services at any time, without prior notice, for any reason, including any breach of these terms.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 7 -->
            <section>
                <h2>7. Changes to Terms</h2>
                <p>We reserve the right to modify these terms at any time. Any changes will be posted on this page, and your continued use of the website after such modifications constitutes your acceptance of the updated terms. You are encouraged to review this page periodically.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 8 -->
            <section>
                <h2>8. Governing Law</h2>
                <p>These terms and conditions are governed by and shall be interpreted in accordance with the laws of Bihar, India, without regard to its conflict of law principles. Any legal disputes arising hereunder shall be resolved exclusively by the local courts of Bihar, India.</p>
            </section>
            <hr class="section-divider">

            <!-- Section 9 -->
            <section>
                <h2>9. Contact Us</h2>
                <p>If you have any questions or concerns regarding these Terms and Conditions, please contact us:</p>
                <p>Email: 
                    <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fw-bold text-primary">abhayprasad.maurya@gmail.com</a>
                </p>
            </section>
        </div>
    </div>

    <?php include "footer.php"; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Optional reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

</body>
</html>