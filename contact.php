<?php
require_once __DIR__ . '/admin/lib_common.php';

$feedback = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(trim($_POST['mobile'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contacts.php?feedback=invalid_email");
        exit;
    }
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        header("Location: contacts.php?feedback=invalid_mobile");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO full_texts (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        header("Location: contacts.php?feedback=fail:DB_Error");
        exit;
    }
    
    $stmt->bind_param("sssss", $name, $email, $mobile, $subject, $message);
    
    if (!$stmt->execute()) {
        header("Location: contacts.php?feedback=fail:" . urlencode($stmt->error));
        $stmt->close();
        exit;
    }
    $stmt->close();

    $brevo_api_key = @file_get_contents(__DIR__ . '/admin/brevo_api_key.txt');
    $brevo_sender = @file_get_contents(__DIR__ . '/admin/brevo_sender.txt');
    $admin_email = trim($brevo_sender);
    $email_subject = "New Message: $subject";
    
    $email_body = '<div style="background:#f7f9fc;padding:32px 18px;border-radius:16px;font-family:Poppins,sans-serif;color:#212529;max-width:600px;margin:auto;box-shadow:0 8px 32px #0d6efd22;">
        <div style="text-align:center;margin-bottom:18px;">
            <img src="https://smrtbuild.gion.com/admin/assets/smrticon.png" alt="SamratbuildLogo" style="width:55px;height:55px;border-radius:10px;box-shadow:0 3px 10px #0d6efd22;">
            <h2 style="color:#0d6efd;margin:12px 0 0 0;font-size:1.8rem;font-weight:700;">New Contact Inquiry</h2>
        </div>
        <div style="background:#fff;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <table cellpadding="8" cellspacing="0" width="100%" style="font-size:1rem;">
                <tr><td width="30%" style="font-weight:600;color:#6c757d;">Name:</td><td style="color:#212529;">' . htmlspecialchars($name) . '</td></tr>
                <tr><td style="font-weight:600;color:#6c757d;">Email:</td><td style="color:#212529;">' . htmlspecialchars($email) . '</td></tr>
                <tr><td style="font-weight:600;color:#6c757d;">Mobile:</td><td style="color:#212529;">' . htmlspecialchars($mobile) . '</td></tr>
                <tr><td style="font-weight:600;color:#6c757d;">Subject:</td><td style="color:#212529;">' . htmlspecialchars($subject) . '</td></tr>
            </table>
            <hr style="margin:15px 0;border-top:1px solid #eee;">
            <p style="margin:0 0 8px 0;font-weight:600;color:#0d6efd;">Message:</p>
            <p style="margin:0;white-space:pre-wrap;color:#212529;">' . htmlspecialchars($message) . '</p>
        </div>
        <div style="text-align:center;color:#999;font-size:0.85rem;margin-top:20px;">This message was sent via Samratbuild Contact Form.</div>
    </div>';

    $sent = send_brevo_email($admin_email, $email_subject, $email_body);
    
    $redirect_status = $sent ? 'success' : 'warning';
    header("Location: contacts.php?feedback=$redirect_status");
    exit;
}

// Fetch general settings for contact details (assuming 'lib_common.php' connects to DB and fetches settings)
$settings = [];
if ($conn) {
    $res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('office_address', 'contact_email', 'contact_phone')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

$popupMsg = '';
if (isset($_GET['feedback'])) {
    if ($_GET['feedback'] === 'success') {
        $popupMsg = '✅ Your message was sent successfully! We will reply soon.';
    } elseif ($_GET['feedback'] === 'warning') {
        $popupMsg = '⚠️ Message saved, but email failed to send. Please try again later.';
    } elseif ($_GET['feedback'] === 'invalid_email') {
        $popupMsg = '❌ Invalid email address.';
    } elseif ($_GET['feedback'] === 'invalid_mobile') {
        $popupMsg = '❌ Invalid mobile number (must be 10 digits).';
    } elseif (strpos($_GET['feedback'], 'fail:') === 0) {
        $popupMsg = '❌ Database Error: Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="/abhay/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta charset="UTF-8">
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png">
    <title>Contact Us - Samratbuild</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --gradient-start: #0d6efd;
            --gradient-end: #6a11cb;
            --background-color: #f4f7f9;
        }
        body {
            background-color: var(--background-color);
            font-family: 'Poppins', sans-serif;
        }
        .contact-container {
            max-width: 1100px;
            margin: auto;
            display: flex;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .contact-info {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: #fff;
            padding: 40px;
            border-radius: 20px 0 0 20px;
        }
        .contact-info h3 {
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .info-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            padding: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }
        .info-text {
            flex-grow: 1;
        }
        .info-text strong {
            display: block;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        /* Contact Form Styling */
        .contact-form {
            padding: 40px 40px 40px 30px;
            flex-grow: 1;
        }
        .contact-form h2 {
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--heading-color);
            font-size: 2.2rem;
        }
        .input-group-text {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px 0 0 8px;
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }
        .form-control {
            border-radius: 0 8px 8px 0;
            border: 1px solid #dee2e6;
            border-left: none;
            font-size: 1rem;
            padding: 0.75rem 1rem;
        }
        textarea.form-control {
            border-left: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: var(--primary-color);
        }
        .btn-send {
            background: linear-gradient(90deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 35px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
            transition: all 0.3s ease;
        }
        .btn-send:hover {
            opacity: 0.9;
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.6);
            transform: translateY(-2px);
        }

        @media (max-width: 992px) {
            .contact-container {
                flex-direction: column;
                border-radius: 12px;
            }
            .contact-info {
                border-radius: 12px 12px 0 0;
                padding: 30px;
            }
            .contact-form {
                padding: 30px 20px;
            }
        }
        
    </style>
</head>
<body>

<?php include "header.php"; ?>

<div class="container py-5">
    <div class="contact-container shadow-lg">
        
        <div class="col-lg-4 contact-info">
            <h3>Contact Information</h3>
            <p class="mb-4">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>

            <div class="info-item">
                <span class="info-icon"><i class="bi bi-geo-alt-fill"></i></span>
                <div class="info-text">
                    <strong>Address:</strong>
                    <span><?= htmlspecialchars($settings['office_address'] ?? 'Loading...') ?></span>
                </div>
            </div>

            <div class="info-item">
                <span class="info-icon"><i class="bi bi-envelope-fill"></i></span>
                <div class="info-text">
                    <strong>Email:</strong>
                    <span><?= htmlspecialchars($settings['contact_email'] ?? 'info@company.com') ?></span>
                </div>
            </div>

            <div class="info-item">
                <span class="info-icon"><i class="bi bi-telephone-fill"></i></span>
                <div class="info-text">
                    <strong>Phone:</strong>
                    <span><?= htmlspecialchars($settings['contact_phone'] ?? '+91 000 000 0000') ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-8 contact-form">
            <h2 class="text-center">Send Us a Message</h2>

            <form method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Your Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label">Your Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="email@domain.com" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="mobile" class="form-label">Mobile Number (10 digits)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="9876543210" pattern="[0-9]{10}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject / Service Inquiry</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius: 8px 0 0 8px;"><i class="fas fa-tag"></i></span>
                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Project Inquiry, Support, etc." required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="message" class="form-label">Message Details</label>
                    <textarea class="form-control" id="message" name="message" rows="5" placeholder="Type your detailed message here..." required></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-send px-5">
                        <i class="fas fa-paper-plane me-2"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    <?php if (!empty($popupMsg)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            alert("<?= addslashes($popupMsg) ?>");
            // Clean up URL parameter to avoid re-alerting on refresh
            if (history.pushState) {
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                history.pushState({path: newUrl}, '', newUrl);
            }
        });
    <?php endif; ?>
    
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/abhay/service-worker.js');
        });
    }
</script>

</body>
</html>