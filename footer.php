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
        padding-top: 50px; /* Reduced Padding */
        padding-bottom: 20px;
        border-top: 4px solid var(--primary-accent);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .footer-modern .footer-heading {
        color: var(--primary-accent) !important;
        margin-bottom: 15px;
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    
    .footer-modern p {
        color: var(--subtle-text);
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .footer-modern .contact-info i {
        color: var(--primary-accent);
        min-width: 20px;
    }

    .footer-modern .contact-info a {
        color: var(--text-color);
        text-decoration: none;
    }
    
    .footer-modern .link-list a {
        color: var(--subtle-text) !important;
        transition: all 0.3s ease;
        display: block;
        padding: 3px 0;
        font-size: 0.9rem;
        text-decoration: none;
    }
    
    .footer-modern .link-list a:hover {
        color: var(--link-hover-color) !important;
        padding-left: 5px;
    }

    .footer-modern .social-anim {
        color: var(--subtle-text) !important;
        font-size: 1.2rem;
        margin-right: 15px;
        transition: color 0.3s ease;
    }

    .footer-modern .social-anim:hover {
        color: var(--primary-accent) !important;
    }
    
    /* Logo Fix - Removed Invert */
    .logo-img {
        max-width: 180px;
        height: auto;
        object-fit: contain;
        /* filter hata diya gaya hai taaki logo saaf dikhe */
    }
    
    .footer-modern .copyright-section {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid rgba(136, 146, 176, 0.1);
        font-size: 0.85rem;
    }
</style>

<footer class="footer-modern container-fluid px-4 px-md-5">
    <div class="row">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <a href="index" class="mb-3 d-inline-block">
                <img src="admin/assets/jp_construction_logo.webp" alt="JP Construction Logo" class="logo-img">
            </a>
            <p class="pe-lg-5">
                Building dreams with precision, quality, and trust. Crafting safe, sustainable, and stunning spaces that stand strong for tomorrow’s generations.
            </p>
            <div class="d-flex mt-3">
                <a class="social-anim" href="#"><i class="fab fa-facebook-f"></i></a>
                <a class="social-anim" href="#"><i class="fab fa-twitter"></i></a>
                <a class="social-anim" href="#"><i class="fab fa-linkedin-in"></i></a>
                <a class="social-anim" href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
            <h5 class="footer-heading">Quick Links</h5>
            <div class="link-list">
                <a href="index.php"><i class="fa fa-angle-right me-2"></i>Home</a>
                <a href="about.php"><i class="fa fa-angle-right me-2"></i>About Us</a>
                <a href="services-static.php"><i class="fa fa-angle-right me-2"></i>Services</a>
                <a href="contact.php"><i class="fa fa-angle-right me-2"></i>Contact</a>
                <a href="terms.php"><i class="fa fa-angle-right me-2"></i>Terms</a>
                <a href="privacy.php"><i class="fa fa-angle-right me-2"></i>Privacy</a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <h5 class="footer-heading">Get In Touch</h5>
            <div class="contact-info">
                <p class="mb-2"><i class="fa fa-map-marker-alt me-2"></i> Nagra, Saran, Bihar</p>
                <p class="mb-2"><i class="fa fa-phone-alt me-2"></i> <a href="tel:+910000000000">+91 0000000000</a></p>
                <p class="mb-0"><i class="fa fa-envelope me-2"></i> <a href="mailto:jp.maurya@gmail.com">abhayprasad.maurya@gmail.com</a></p>
            </div>
        </div>
    </div>

    <div class="copyright-section">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?= date('Y') ?> <b>JP Construction Works</b>. All Rights Reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">Dev by <a href="https://abhayprasad.netlify.app/" target="_blank" style="color: var(--primary-accent); text-decoration: none;">Abhay Prasad</a></p>
            </div>
        </div>
    </div>
</footer>