<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }
// footer.php
?>
<style>
.footer-bar { background: rgba(255,255,255,0.98); border-radius: 32px 32px 0 0; box-shadow: 0 -4px 24px rgba(99,102,241,0.10); padding: 18px 0; font-family: 'Plus Jakarta Sans', sans-serif; text-align: center; }
.footer-text { color: #6366f1; font-weight: 600; font-size: 1rem; letter-spacing: 1px; }
.footer-time { color: #222; font-weight: 500; margin-left: 12px; }
</style>
<div class="footer-bar">
    <span class="footer-text">© 2026 JP Construction.</span>
    <span class="footer-time">| <?= date('h:i A') ?> IST</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveTime').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>