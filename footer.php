<?php
// --- Dynamic Footer Data from site_settings ---
require_once __DIR__ . '/admin/database.php';

// Database connection logic is assumed to be handled by `database.php`
$conn = $conn ?? null; // Ensure $conn is defined if it fails to connect

$footer_settings = [];
$keys = [
    'office_address', 'contact_email', 'contact_phone', 'logo_url',
    'facebook_url', 'youtube_url', 'linkedin_url', 'twitter_url', 'app_download_url'
];

if ($conn) {
    $res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $footer_settings[$row['setting_key']] = $row['setting_value'];
        }
        $res->close();
    }
}
// Set default values if DB connection failed or settings are missing
$defaults = [
    'logo_url' => 'admin/assets/111.png',
    'office_address' => 'Nagra, Saran, Bihar',
    'contact_phone' => '+919661329757',
    'contact_email' => 'abhayprasad.maurya@gmail.com',
    'twitter_url' => '#',
    'facebook_url' => '#',
    'linkedin_url' => 'https://www.linkedin.com/in/abhay-prasad-84b46a297',
    'youtube_url' => '#',
    'app_download_url' => ''
];
$footer_settings = array_merge($defaults, $footer_settings);
?>

<style>
    :root {
        --footer-bg: #0A192F; /* Deep Navy Blue */
        --primary-accent: #64FFDA; /* Aqua Green */
        --text-color: #CCD6F6; /* Light Grayish Blue */
        --subtle-text: #8892B0; /* Slate Gray */
        --link-hover-color: #64FFDA;
    }
    
    .footer-modern {
        background-color: var(--footer-bg) !important;
        color: var(--text-color) !important;
        padding-top: 80px;
        padding-bottom: 30px;
        border-top: 4px solid var(--primary-accent);
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.5);
    }

    /* Heading Style */
    .footer-modern .footer-heading {
        color: var(--primary-accent) !important;
        padding-bottom: 10px;
        margin-bottom: 25px;
        font-weight: 700;
        font-size: 1.2rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }
    
    /* Text and Paragraph Style */
    .footer-modern p {
        color: var(--subtle-text);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    /* Contact Info Icons and Text */
    .footer-modern .contact-info i {
        color: var(--primary-accent);
        font-size: 1.1rem;
        min-width: 20px;
        text-align: center;
    }
    .footer-modern .contact-info a {
        color: var(--text-color);
        transition: color 0.3s ease;
        text-decoration: none;
    }
    .footer-modern .contact-info a:hover {
        color: var(--link-hover-color);
    }
    
    /* Link List Styling */
    .footer-modern .link-list a {
        color: var(--subtle-text) !important;
        transition: color 0.3s ease, padding-left 0.2s ease;
        display: block;
        padding: 5px 0;
        font-size: 0.95rem;
        text-decoration: none;
    }
    
    .footer-modern .link-list a i {
        color: var(--primary-accent);
        margin-right: 8px;
        font-size: 0.9rem;
    }
    
    .footer-modern .link-list a:hover {
        color: var(--link-hover-color) !important;
        padding-left: 5px;
    }

    /* Social Icons Styling */
    .footer-modern .social-anim {
        background: transparent !important;
        border: 1px solid var(--subtle-text) !important;
        color: var(--subtle-text) !important;
        border-radius: 50% !important; /* Make them round */
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .footer-modern .social-anim:hover {
        border-color: var(--primary-accent) !important;
        color: var(--primary-accent) !important;
        transform: translateY(-4px) scale(1.1);
        background-color: rgba(100, 255, 218, 0.1) !important;
    }
    
    /* Logo Styling */
    .logo-img {
        max-width: 100%;
        max-height: 50px;
        object-fit: contain;
        filter: brightness(0) invert(1); /* Invert color for dark background */
    }
    
    /* CTA Button */
    .btn-cta-app {
        background-color: var(--primary-accent);
        color: var(--footer-bg);
        font-weight: 700;
        border: none;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(100, 255, 218, 0.4);
    }
    .btn-cta-app:hover {
        background-color: #38C7A0; /* Slightly darker hover */
        transform: scale(1.05);
        color: var(--footer-bg);
        box-shadow: 0 6px 20px rgba(100, 255, 218, 0.6);
    }
    
    /* Copyright Section */
    .footer-modern .copyright-section {
        border-top: 1px solid var(--subtle-text);
    }
    .footer-modern .copyright-section a {
        color: var(--primary-accent);
        text-decoration: none;
        font-weight: 600;
    }
</style>

<footer class="footer-modern container-fluid px-4 px-md-5 animate__animated animate__fadeInUp">

    <div class="row g-5">

        <div class="col-lg-5 pe-lg-5">
            <a href="index" class="navbar-brand mb-4 d-inline-block">
                <img src="<?= htmlspecialchars($footer_settings['logo_url']) ?>" alt="Company Logo" class="logo-img">
            </a>
            
            <p class="mb-4">
                Building dreams with precision, quality, and trust — crafting safe, sustainable, and stunning spaces that stand strong today and inspire tomorrow’s generations.
            </p>

            <div class="contact-info mb-5">
                <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i><?= htmlspecialchars($footer_settings['office_address']) ?></p>
                <p class="mb-2"><i class="fa fa-phone-alt me-3"></i><a href="tel:<?= htmlspecialchars($footer_settings['contact_phone']) ?>"><?= htmlspecialchars($footer_settings['contact_phone']) ?></a></p>
                <p class="mb-0"><i class="fa fa-envelope me-3"></i><a href="mailto:<?= htmlspecialchars($footer_settings['contact_email']) ?>"><?= htmlspecialchars($footer_settings['contact_email']) ?></a></p>
            </div>
            
            <h6 class="footer-heading">Connect</h6>
            <div class="d-flex social-wrapper">
                <a class="social-anim me-3" href="<?= htmlspecialchars($footer_settings['twitter_url']) ?>" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a class="social-anim me-3" href="<?= htmlspecialchars($footer_settings['facebook_url']) ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a class="social-anim me-3" href="<?= htmlspecialchars($footer_settings['linkedin_url']) ?>" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a class="social-anim me-3" href="<?= htmlspecialchars($footer_settings['youtube_url']) ?>" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>

        <div class="col-lg-7 ps-lg-5">
            <div class="row g-5">

                <div class="col-sm-6">
                    <h5 class="footer-heading">Quick Links</h5>
                    <div class="d-flex flex-column link-list">
                        <a href="/"><i class="fa fa-angle-right"></i>Home</a>
                        <a href="about"><i class="fa fa-angle-right"></i>About Us</a>
                        <a href="services"><i class="fa fa-angle-right"></i>Our Services</a>
                        <a href="team"><i class="fa fa-angle-right"></i>Meet the Team</a>
                        <a href="contacts"><i class="fa fa-angle-right"></i>Contact Us</a>
                        <a href="/smrt/documentation.php" target="_blank"><i class="fa fa-book"></i>Documentation</a>
                    </div>
                </div>

                <div class="col-sm-6">
                    <h5 class="footer-heading">Developer Links</h5>
                    <div class="d-flex flex-column link-list">
                        <a href="https://www.linkedin.com/in/abhay-prasad-84b46a297" target="_blank"><i class="fa fa-angle-right"></i>Developer LinkedIn</a>
                        <a href="https://github.com/Maurya-Abhay" target="_blank"><i class="fa fa-angle-right"></i>GitHub Repository</a>
                        <a href="https://abhayprasad.netlify.app/" target="_blank"><i class="fa fa-angle-right"></i>My Portfolio</a>
                        <a href="https://leetcode.com/Maurya-Abhay" target="_blank"><i class="fa fa-angle-right"></i>LeetCode Profile</a>
                        <a href="#"><i class="fa fa-angle-right"></i>Privacy Policy</a>
                    </div>
                    
                    <?php if (!empty($footer_settings['app_download_url'])): ?>
                    <div class="mt-4 pt-2">
                        <a href="<?= htmlspecialchars($footer_settings['app_download_url']) ?>" class="btn btn-cta-app rounded-pill px-4 py-2" target="_blank">
                            <i class="bi bi-download me-2"></i> Download Our App
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="container-fluid copyright-section mt-5 pt-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center px-2 px-md-5">
            <p class="mb-2 mb-md-0 text-center text-md-start">
                &copy; <?= date('Y') ?> <a href="https://abhaypr.vercel.app/">Abhay Prasad</a>. All Rights Reserved.
            </p>
            <p class="mb-0 text-center text-md-end text-white-50">
                Developed for <span style="color: var(--primary-accent); font-weight: 600;">Samrat Construction Private Limited</span>
            </p>
        </div>
    </div>

</footer>