<?php 
// No PHP logic needed here other than the date function
?>

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
<footer class="bg-dark text-white py-4 mt-5 shadow-lg">
    <div class="container">
        
        <div class="row align-items-center border-bottom border-secondary-subtle pb-3 mb-3">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <small class="fw-light text-secondary">&copy; <?= date('Y') ?> <b>Samrat Construction Pvt. Ltd.</b></small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="badge bg-light text-dark fw-normal p-2">
                    <i class="bi bi-clock me-1"></i>
                    <span id="contactFooterTime"><?= date('d M Y, h:i A') ?></span>
                </span>
            </div>
        </div>
        
        <div class="row">
            
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <h6 class="text-secondary fw-bold text-uppercase mb-2">Contact Us</h6>
                <div class="small">
                    <div class="mb-1"><i class="bi bi-geo-alt me-2"></i> <span class="fw-light"><?= htmlspecialchars($footer_settings['office_address'] ?? 'Your Office Address Here') ?></span></div>
                    <div class="mb-1"><i class="bi bi-envelope me-2"></i> <a href="mailto:<?= htmlspecialchars($footer_settings['contact_email'] ?? 'info@samratconstruction.com') ?>" class="text-white text-decoration-none"><?= htmlspecialchars($footer_settings['contact_email'] ?? 'info@samratconstruction.com') ?></a></div>
                    <div><i class="bi bi-telephone me-2"></i> <a href="tel:<?= htmlspecialchars($footer_settings['contact_phone'] ?? '+911234567890') ?>" class="text-white text-decoration-none"><?= htmlspecialchars($footer_settings['contact_phone'] ?? '+91 12345 67890') ?></a></div>
                    <?php if (!empty($footer_settings['app_download_url'])): ?>
                    <div class="mt-2">
                        <a href="<?= htmlspecialchars($footer_settings['app_download_url']) ?>" class="btn btn-outline-info rounded-pill px-4" target="_blank">
                            <i class="bi bi-download"></i> Download App
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6 text-center text-md-end">
                <h6 class="text-secondary fw-bold text-uppercase mb-3">Follow Us</h6>
                <div class="social-links">
                    <a href="<?= htmlspecialchars($footer_settings['facebook_url'] ?? '#') ?>" target="_blank" class="text-white mx-2" style="font-size: 1.5rem;" title="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer_settings['twitter_url'] ?? '#') ?>" target="_blank" class="text-white mx-2" style="font-size: 1.5rem;" title="Twitter">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer_settings['youtube_url'] ?? '#') ?>" target="_blank" class="text-white mx-2" style="font-size: 1.5rem;" title="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>
                    <a href="<?= htmlspecialchars($footer_settings['linkedin_url'] ?? '#') ?>" target="_blank" class="text-white mx-2" style="font-size: 1.5rem;" title="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</footer>

<script>
    /**
     * Updates the footer time element every second for a real-time clock effect.
     */
    function updateContactFooterTime() {
        const now = new Date();
        
        const opt = { 
            day: '2-digit', 
            month: 'short', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true 
        };
        
        const el = document.getElementById('contactFooterTime');
        
        if (el) {
            // Using toLocaleString for better formatting, ensuring it includes seconds.
            // Using 'en-US' locale format for consistency (d M Y, h:i:s A)
            el.textContent = now.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) + 
                             ', ' + 
                             now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }

    updateContactFooterTime(); 
    setInterval(updateContactFooterTime, 1000); 
</script>

