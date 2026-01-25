<style>
    :root {
        --footer-bg: #0b1c2c;       /* Deep Navy */
        --footer-accent: #ff9f1c;   /* Amber */
        --footer-text: #e2e8f0;     /* Off White */
        --footer-muted: #94a3b8;    /* Slate Gray */
        
        --font-head: 'Oswald', sans-serif;
        --font-body: 'Manrope', sans-serif;
    }
    
    .footer-industrial {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        font-family: var(--font-body);
        position: relative;
        overflow: hidden;
        border-top: 4px solid var(--footer-accent);
        padding-top: 60px;
    }

    /* --- Background Pattern (Blueprint Style) --- */
    .footer-bg-grid {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background-image: 
            linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        z-index: 0;
        pointer-events: none;
    }

    /* Content Wrapper to sit above background */
    .footer-content {
        position: relative;
        z-index: 1;
    }

    .footer-heading {
        font-family: var(--font-head);
        color: #fff;
        font-size: 1.25rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 25px;
        position: relative;
        display: inline-block;
    }
    
    /* Underline Effect for Headings */
    .footer-heading::after {
        content: '';
        display: block;
        width: 40px;
        height: 3px;
        background: var(--footer-accent);
        margin-top: 5px;
    }
    
    .footer-desc {
        color: var(--footer-muted);
        line-height: 1.7;
        font-size: 0.95rem;
    }

    /* --- Links Styling --- */
    .footer-link-list a {
        color: var(--footer-muted) !important;
        text-decoration: none;
        display: block;
        padding: 6px 0;
        transition: all 0.3s ease;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .footer-link-list a i {
        font-size: 0.8rem;
        margin-right: 8px;
        color: var(--footer-accent);
        opacity: 0.7;
        transition: 0.3s;
    }

    .footer-link-list a:hover {
        color: #fff !important;
        transform: translateX(5px);
    }
    .footer-link-list a:hover i {
        opacity: 1;
    }

    /* --- Contact Section --- */
    .contact-item {
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
        color: var(--footer-muted);
    }
    .contact-item i {
        color: var(--footer-accent);
        font-size: 1.1rem;
        margin-right: 15px;
        margin-top: 3px;
    }
    .contact-item a {
        color: var(--footer-muted);
        text-decoration: none;
        transition: 0.3s;
    }
    .contact-item a:hover { color: var(--footer-accent); }

    /* --- Social Icons --- */
    .social-btn {
        width: 40px; height: 40px;
        background: rgba(255,255,255,0.05);
        display: inline-flex;
        align-items: center; justify-content: center;
        border-radius: 4px; /* Industrial Sharp Edges */
        color: #fff;
        margin-right: 10px;
        transition: 0.3s;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .social-btn:hover {
        background: var(--footer-accent);
        color: var(--footer-bg);
        border-color: var(--footer-accent);
        transform: translateY(-3px);
    }

    /* --- Copyright --- */
    .copyright-area {
        background: rgba(0,0,0,0.2);
        padding: 20px 0;
        margin-top: 50px;
        border-top: 1px solid rgba(255,255,255,0.05);
        font-size: 0.9rem;
        color: var(--footer-muted);
    }
    .dev-link {
        color: var(--footer-accent);
        text-decoration: none;
        font-weight: 700;
        font-family: var(--font-head);
        letter-spacing: 0.5px;
    }
    .dev-link:hover { text-decoration: underline; }

    .logo-img {
        max-width: 160px;
        margin-bottom: 20px;
        opacity: 0.9;
    }
</style>

<footer class="footer-industrial">
    <div class="footer-bg-grid"></div>

    <div class="container footer-content px-4 px-md-3">
        <div class="row gy-5">
            <div class="col-lg-5 col-md-12">
                <a href="index.php" class="d-block">
                    <img src="admin/assets/jp_construction_logo.webp" alt="JP Construction" class="logo-img">
                </a>
                <p class="footer-desc pe-lg-5">
                    Building dreams with precision and structural integrity. We are committed to delivering safe, sustainable, and high-quality construction solutions that stand the test of time.
                </p>
                <div class="mt-4">
                    <a class="social-btn" href="#"><i class="fab fa-facebook-f"></i></a>
                    <a class="social-btn" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="social-btn" href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a class="social-btn" href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Quick Links</h5>
                <div class="footer-link-list">
                    <a href="index.php"><i class="fa fa-chevron-right"></i>Home</a>
                    <a href="about.php"><i class="fa fa-chevron-right"></i>About Us</a>
                    <a href="services-static.php"><i class="fa fa-chevron-right"></i>Services</a>
                    <a href="contact.php"><i class="fa fa-chevron-right"></i>Contact</a>
                    <a href="terms.php"><i class="fa fa-chevron-right"></i>Terms & Conditions</a>
                    <a href="privacy.php"><i class="fa fa-chevron-right"></i>Privacy Policy</a>
                    <a href="documentation.php"><i class="fa fa-chevron-right"></i>Documentation</a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Get In Touch</h5>
                <div class="mt-3">
                    <div class="contact-item">
                        <i class="fa fa-map-marker-alt"></i>
                        <div>
                            <strong class="d-block text-white mb-1">Head Office:</strong>
                            Nagra, Saran, Bihar - 841442
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa fa-phone-alt"></i>
                        <div>
                            <strong class="d-block text-white mb-1">Phone:</strong>
                            <a href="tel:+910000000000">+91 00000 00000</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa fa-envelope"></i>
                        <div>
                            <strong class="d-block text-white mb-1">Email:</strong>
                            <a href="mailto:abhayprasad.maurya@gmail.com">abhayprasad.maurya@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="copyright-area footer-content">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    &copy; <?= date('Y') ?> <strong class="text-white">JP Construction Works</strong>. All Rights Reserved.
                </div>
                <div class="col-md-6 text-center text-md-end">
                    Designed by <a href="https://abhayprasad.netlify.app/" target="_blank" class="dev-link">Abhay Prasad</a>
                </div>
            </div>
        </div>
    </div>
</footer>