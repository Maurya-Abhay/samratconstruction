<?php
// Modern Help page for Samrat Construction Private Limited
include 'topheader.php';
include 'sidenavbar.php';
?>
<style>
.help-container {
    padding-top: 30px;
    padding-bottom: 50px;
}
.help-section {
    border-left: 4px solid #1976d2;
    padding-left: 18px;
    margin-bottom: 32px;
}
.help-feature {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 18px 22px;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(25,118,210,0.07);
}
</style>
<div class="container-fluid help-container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5 p-md-5">
                    <div class="text-center mb-4">
                        <img src="../admin/assets/smrticon.png" alt="Logo" style="height:60px;">
                        <h2 class="mt-2 mb-0 fw-bold text-primary">Samrat Construction Private Limited</h2>
                        <div class="small text-muted">Help & User Guide</div>
                    </div>
                    <h3 class="mb-4 text-primary"><i class="bi bi-question-circle me-2"></i>How to Use This Website</h3>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">1. Login & Security</h4>
                        <div class="help-feature">
                            <b>Admin, Worker, Contact, Attendence</b> panels have separate login pages. Use your registered email and password. Admin panel supports <b>2FA/OTP</b> for extra security.
                        </div>
                        <div class="help-feature">
                            <b>Forgot Password?</b> Use the 'Forgot Password' link on any panel to receive an OTP on your email and reset your password securely.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">2. Attendance & Biometrics</h4>
                        <div class="help-feature">
                            <b>Mark Attendance:</b> Workers can mark attendance using facial recognition (biometric) or QR code, as enabled by admin.
                        </div>
                        <div class="help-feature">
                            <b>Attendance History:</b> View your attendance history and download reports from your panel.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">3. Payroll & Payments</h4>
                        <div class="help-feature">
                            <b>View Payslips:</b> Admin and workers can view/download payslips from the dashboard.
                        </div>
                        <div class="help-feature">
                            <b>UPI Payments:</b> Use UPI QR code for fast, secure payments. Admin can update UPI details in settings.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">4. Data & Reports</h4>
                        <div class="help-feature">
                            <b>Export Data:</b> Export user, attendance, and payment data as CSV from the admin panel.
                        </div>
                        <div class="help-feature">
                            <b>Audit Logs:</b> Admin can review security and activity logs for transparency.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">5. Security Features</h4>
                        <div class="help-feature">
                            <b>Emergency Lock:</b> Admin can activate emergency lock to temporarily restrict access for security reasons.
                        </div>
                        <div class="help-feature">
                            <b>Blocked/Whitelisted IPs:</b> Admin can block or whitelist IPs for extra protection.
                        </div>
                        <div class="help-feature">
                            <b>Session Timeout:</b> Admin and panels have configurable session timeouts for safety.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">6. Progressive Web App (PWA)</h4>
                        <div class="help-feature">
                            <b>Install as App:</b> You can install this website as an app on your device. Look for the 'Install' or 'Add to Home Screen' option in your browser.
                        </div>
                        <div class="help-feature">
                            <b>Offline Access:</b> Some features work offline thanks to PWA technology.
                        </div>
                    </div>
                    <div class="help-section">
                        <h4 class="fw-bold text-dark mb-2">7. Support</h4>
                        <div class="help-feature">
                            <b>Contact Support:</b> For any issues, email <a href="mailto:support@abhay.com">support@abhay.com</a> or use the Help page.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'downfooter.php'; ?>
