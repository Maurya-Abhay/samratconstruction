<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- PWA Manifest -->
    <link rel="manifest" href="/htdocs/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Content Security Policy kept, though general advice is to avoid upgrade-insecure-requests unless needed -->
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png" />
    
    <!-- Corrected Title to reflect Privacy Policy content -->
    <title>Privacy Policy - Construction Company</title>
    
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <style>
        /* Define custom color variables for consistency */
        :root {
            --bs-primary-dark: #0056b3; /* Darker shade of Bootstrap Primary Blue */
            --bs-secondary-text: #495057;
            --bs-section-bg: #ffffff;
            --bs-body-bg: #f5f7fa; /* Light, modern background */
        }

        body {
            background-color: var(--bs-body-bg);
            font-family: 'Inter', sans-serif;
        }

        /* Modernize the main content container */
        .content-card {
            background-color: var(--bs-section-bg);
            border-radius: 1rem; /* More rounded corners */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Deeper, softer shadow */
            padding: 2.5rem !important; /* Increase padding for breathability */
            max-width: 900px; /* Limit width on very large screens */
            margin: 0 auto;
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
    </style>
</head>
<body>

<?php include "header.php"; ?>

<div class="container my-5">
    <div class="content-card">
        <h1 class="page-title">Website Privacy Policy</h1>

        <section>
            <h2>1. Data Collection</h2>
            <p>We may collect personal information such as your name, email address, phone number, and other identification details when you voluntarily interact with our website or services, for purposes including service inquiry and account registration.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>2. How We Use Your Information</h2>
            <p>Your data is used solely to deliver the services you requested, personalize the user experience, respond effectively to inquiries, and comply with all applicable legal obligations. We assure you that we never sell, trade, or rent your personal data to third parties.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>3. Data Security and Protection</h2>
            <p>We implement industry-standard security measures to safeguard your data from unauthorized access, alteration, disclosure, or destruction. This includes the use of encryption, secure server architecture, and stringent internal access control protocols. However, it is important to note that no method of transmission over the Internet is 100% secure.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>4. Cookies & Tracking Technologies</h2>
            <p>We use cookies and similar tracking technologies to enhance your browsing experience, analyze site traffic, and understand user behavior. You maintain control and can manage cookie settings directly through your browser's configuration options. Please note that disabling cookies may affect certain site functionalities.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>5. Your Data Rights</h2>
            <p>You have the right to request access to, correction of, or deletion of your personal data held by us. Furthermore, you may withdraw consent or object to specific processing activities. To exercise these rights, please contact us promptly at: <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fw-bold text-primary">abhayprasad.maurya@gmail.com</a>.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>6. Third-Party Services and Links</h2>
            <p>We may utilize third-party tools (such as web analytics, security services like CAPTCHA, or external hosting providers) that may process anonymous data on our behalf. These third parties have their own independent privacy policies which govern their use of the data collected.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>7. Changes to This Policy</h2>
            <p>We reserve the right to update this Privacy Policy periodically to reflect changes in our data practices or regulatory requirements. Any modifications will be posted prominently on this page, and the "Effective Date" at the top of the policy will be updated.</p>
        </section>
        <hr class="section-divider">

        <section>
            <h2>8. Contact Information</h2>
            <p>For any questions or concerns regarding this Privacy Policy or our data handling practices, please do not hesitate to reach out to us:</p>
            <p>Email: <a href="mailto:abhayprasad.maurya@gmail.com" class="text-decoration-none fw-bold text-primary">abhayprasad.maurya@gmail.com</a></p>
        </section>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Optional reCAPTCHA script if forms use it -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

</body>
</html>