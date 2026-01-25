</div> 
<style>
    /* --- Modern Footer Theme --- */
    :root {
        --footer-bg-start: #2c3e50; /* Deep Navy Blue */
        --footer-bg-end: #1a252f; /* Darker Blue */
        --accent-teal: #1abc9c; /* Bright Teal for links/borders */
        --text-light: #ecf0f1;
        --text-muted-dark: #b3c0c7;
    }

    footer {
        /* Using a subtle dark gradient for depth */
        background: linear-gradient(135deg, var(--footer-bg-start) 0%, var(--footer-bg-end) 100%) !important;
        color: var(--text-light);            padding: 2rem 0; /* More vertical padding */
        margin-top: 3rem !important; /* Increased margin above footer */
        box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.3); /* Stronger top shadow */
    }
    .mt-auto {
        margin-top: 25px !important;
    }
    
    /* Heading and Logo Style */
    footer h6 {
        color: var(--accent-teal) !important; /* Teal accent for headings */
        border-bottom: 2px solid var(--accent-teal); /* Thicker accent border */
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Link Styling */
    footer a {
        color: var(--text-light) !important;
        text-decoration: none;
        transition: color 0.3s ease, transform 0.2s ease;
        display: inline-block;
        margin-bottom: 0.3rem; /* Small space between links */
        padding-left: 0.1rem; /* Space for subtle hover effect */
    }
    
    footer a:hover {
        color: var(--accent-teal) !important; /* Teal on hover */
        transform: translateX(5px); /* Slide effect on hover */
    }
    
    /* Contact Info */
    footer .small, footer p.small {
        font-size: 0.9rem !important;
        color: var(--text-muted-dark);
    }

    /* Social Icon Styling */
    footer .social-icons-wrapper {
        display: flex;
        gap: 15px; /* Space between icons */
        margin-top: 15px;
        /* Center on mobile, align left on desktop */
        justify-content: center;
    }

    footer .social-icon {
        font-size: 1.6rem;
        transition: color 0.3s ease, transform 0.3s ease;
        color: var(--text-muted-dark) !important; /* Muted color */
    }

    footer .social-icon:hover {
        color: var(--accent-teal) !important;
        transform: scale(1.1);
    }

    /* Copyright & Time Display */
    footer .copyright-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        align-items: flex-end; /* Align right on desktop */
    }

    footer .copyright-info small {
        color: var(--text-muted-dark) !important;
        font-size: 0.85rem;
    }

    /* Spinner for loading state */
    footer .spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Mobile responsiveness adjustments */
    @media (max-width: 768px) {
        footer .col-md-4 {
            text-align: center !important;
            margin-bottom: 2rem;
        }
        
        footer .copyright-info {
            align-items: center; /* Center align on mobile */
            text-align: center;
        }
        
        footer .copyright-info > div {
            text-align: center;
        }
        
        /* Center quick links on mobile */
        footer .row > div[class*="col-6"] {
            display: inline-block;
            text-align: left;
            padding-left: 20px; /* Indent for list look */
        }

        /* Center the social icons on mobile */
        footer .social-icons-wrapper {
             justify-content: center;
        }
    }
</style>


<?php
// --- Dynamic Footer Data from site_settings ---
require_once __DIR__ . '/../admin/database.php';
$footer_settings = [];
$keys = [
    'office_address', 'contact_email', 'contact_phone', 'logo_url',
    'facebook_url', 'youtube_url', 'linkedin_url', 'twitter_url', 'app_download_url'
];
$res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
while ($row = $res->fetch_assoc()) {
    $footer_settings[$row['setting_key']] = $row['setting_value'];
}
?>
<footer class="mt-auto">
    <div class="container">
        <div class="row">
            
            <div class="col-md-4">
                <h6 class="mb-2">
                    <i class="bi bi-building me-2"></i>JP Construction
                </h6>
                <p class="mb-1 small">
                    <i class="bi bi-geo-alt-fill me-1"></i><a href="https://maps.google.com/?q=[Your%20Office%20Address%20Here]" target="_blank" class="text-white"><?= htmlspecialchars($footer_settings['office_address'] ?? 'Nagra, Saran, Bihar') ?></a>
                </p>
                <p class="mb-1 small">
                    <i class="bi bi-envelope me-1"></i><a href="mailto:info@samratconstruction.com" class="text-white"><?= htmlspecialchars($footer_settings['contact_email'] ?? 'abhayprasad.maurya@gmail.com') ?></a>
                </p>
                <p class="mb-1 small">
                    <i class="bi bi-telephone me-1"></i><a href="tel:+911234567890" class="text-white"><?= htmlspecialchars($footer_settings['contact_phone'] ?? '+91 0000000000') ?></a>
                </p>
                <?php if (!empty($footer_settings['app_download_url'])): ?>
                <a href="<?= htmlspecialchars($footer_settings['app_download_url']) ?>" class="btn btn-outline-info rounded-pill px-4 mt-2" target="_blank">
                    <i class="bi bi-download"></i> Download App
                </a>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <h6 class="mb-2">Quick Links</h6>
                <div class="row">
                    <div class="col-6">
                        <a href="dashboard.php"><small><i class="bi bi-house me-1"></i>Dashboard</small></a><br>
                        <a href="attendance.php"><small><i class="bi bi-person-check me-1"></i>Attendance</small></a><br>
                        <a href="profile.php"><small><i class="bi bi-person me-1"></i>Profile</small></a><br>
                        <a href="leave.php"><small><i class="bi bi-calendar2-week me-1"></i>Leave Request</small></a>
                    </div>
                    <div class="col-6">
                        <a href="payment.php"><small><i class="bi bi-wallet2 me-1"></i>Payments</small></a><br>
                        <a href="notices.php"><small><i class="bi bi-megaphone me-1"></i>Notices</small></a><br>
                        <a href="holidays.php"><small><i class="bi bi-calendar-event me-1"></i>Holidays</small></a><br>
                        <a href="../privacy.php"><small><i class="bi bi-shield-lock me-1"></i>Privacy Policy</small></a>
                    </div>
                    
                </div>
            </div>

            <div class="col-md-4">
                <div class="copyright-info">
                    <h6 class="mb-2 mt-4">Follow Us</h6>
                <div class="social-icons-wrapper">
                    <a class="social-icon" href="<?= htmlspecialchars($footer_settings['twitter_url'] ?? '#') ?>" target="_blank" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a class="social-icon" href="<?= htmlspecialchars($footer_settings['facebook_url'] ?? '#') ?>" target="_blank" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a class="social-icon" href="<?= htmlspecialchars($footer_settings['linkedin_url'] ?? '#') ?>" target="_blank" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a class="social-icon" href="<?= htmlspecialchars($footer_settings['youtube_url'] ?? '#') ?>" target="_blank" title="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
                    <div>
                        <small class="fw-bold d-block text-white">&copy; <?= date('Y') ?> JP Construction</small>
                        <small class="text-muted">
                            <span id="footerLiveTime"></span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Real-time clock update in footer
    function updateFooterTime() {
        const now = new Date();
        const options = { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric',
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit', /* Added seconds for a "live" feel */
            hour12: true 
        };
        const timeString = now.toLocaleString('en-US', options);
        const timeElement = document.getElementById('footerLiveTime');
        
        if (timeElement) {
            // Update text content with icon
            timeElement.innerHTML = `<i class="bi bi-clock me-1"></i>Last Update: ${timeString}`;
        }
    }
    
    // Initial update and then update every second
    updateFooterTime();
    setInterval(updateFooterTime, 1000); // Update every second
    
    // Add loading state to footer links
    document.querySelectorAll('footer a').forEach(link => {
        link.addEventListener('click', function() {
            // Check if it's an external link or hash link
            if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                const linkText = this.textContent.trim();
                // Ensure we don't apply spinner to social icons which don't have detailed text
                if (linkText.length > 0 && !this.classList.contains('social-icon')) {
                    this.innerHTML = '<i class="bi bi-arrow-repeat spinner me-1"></i>' + linkText;
                }
            }
        });
    });
</script>
</body>
</html>