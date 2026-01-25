
<?php
require_once __DIR__ . '/admin/lib_common.php';
$alertMsg = '';
$status = '';

// --- Form Submission Handler ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(trim($_POST['mobile'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = 'invalid_email';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $feedback = 'invalid_mobile';
    } else {
        // Database Insert
        $stmt = $conn->prepare("INSERT INTO full_texts (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $name, $email, $mobile, $subject, $message);
            if ($stmt->execute()) {
                $feedback = 'success';
            } else {
                $feedback = 'error';
            }
        } else {
            $feedback = 'error';
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . $feedback);
    exit();
}

if (isset($_GET['feedback'])) {
    $f = $_GET['feedback'];
    $status = ($f === 'success') ? 'success' : 'danger';
    $alertMsg = ($f === 'success') ? 'Message Sent! We will call you soon.' : 'Error sending message.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Contact Us | JP Construction</title>
    <meta name="description" content="Contact JP Construction for expert advice, project quotes, and all your building and interior design needs. Reach out to our team today!">
    <meta name="keywords" content="Contact JP Construction, construction, interior design, project quote, building services, India">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpconstruction.in/contact.php">
    <meta name="theme-color" content="#0d6efd">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Contact Us | JP Construction">
    <meta property="og:description" content="Contact JP Construction for expert advice, project quotes, and all your building and interior design needs. Reach out to our team today!">
    <meta property="og:url" content="https://jpconstruction.in/contact.php">
    <meta property="og:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us | JP Construction">
    <meta name="twitter:description" content="Contact JP Construction for expert advice, project quotes, and all your building and interior design needs. Reach out to our team today!">
    <meta name="twitter:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --dark: #0f172a;
            --light-bg: #f8fafc;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
        }

        /* Hero Section */
        .contact-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 80px 0 120px;
            color: white;
            text-align: center;
        }

        /* Container Alignment Fix */
        .main-wrapper {
            margin-top: -80px;
            margin-bottom: 80px;
        }

        .contact-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            border: none;
        }

        /* Form Side */
        .form-side {
            padding: 50px;
        }

        /* Info Side */
        .info-side {
            background: var(--primary);
            color: white;
            padding: 50px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Fixed Inputs */
        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background-color: #fcfdfe;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
            background-color: #fff;
        }

        /* Info Items Alignment */
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        }

        /* Responsive Fixes */
        @media (max-width: 991px) {
            .form-side, .info-side { padding: 30px; }
            .main-wrapper { margin-top: -50px; padding: 0 15px; }
        }
    </style>
</head>
<body>

<?php include "header.php"; ?>

<section class="contact-header">
    <div class="container">
        <h1 class="display-4 fw-800">Contact Our Experts</h1>
        <p class="lead opacity-75">Let’s discuss your next big construction project.</p>
    </div>
</section>

<div class="container main-wrapper">
    <?php if ($alertMsg): ?>
        <div class="alert alert-<?= $status ?> alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $alertMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="contact-card">
        <div class="row p-3">
            <div class="col-lg-7 form-side">
                <h3 class="fw-800 mb-4">Send us a Message</h3>
                <form action="contact.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">FULL NAME</label>
                            <input type="text" name="name" class="form-control" placeholder="Abhay Maurya" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PHONE NUMBER</label>
                            <input type="tel" name="mobile" class="form-control" placeholder="10 Digit Number" pattern="[0-9]{10}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="form-control" placeholder="name@company.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">HOW CAN WE HELP?</label>
                        <select name="subject" class="form-select">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Residential Project">Residential Project</option>
                            <option value="Commercial Project">Commercial Project</option>
                            <option value="Renovation">Renovation Work</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">PROJECT DETAILS</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Tell us more about your project..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        SEND MESSAGE <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </form>
            </div>

            <div class="col-lg-5 info-side">
                <h3 class="fw-bold mb-5">Contact Details</h3>
                
                <div class="contact-item">
                    <div class="icon-box"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <h6 class="mb-1 fw-bold">Office Address</h6>
                        <p class="m-0 small opacity-90">841220, JP Construction Work, Nagra, Ekma, Saran</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="icon-box"><i class="bi bi-telephone"></i></div>
                    <div>
                        <h6 class="mb-1 fw-bold">Call Anytime</h6>
                        <p class="m-0 small opacity-90">+91 00000 000000</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="icon-box"><i class="bi bi-envelope"></i></div>
                    <div>
                        <h6 class="mb-1 fw-bold">Email Support</h6>
                        <p class="m-0 small opacity-90">abhayprasad.maurya@gmail.com</p>
                    </div>
                </div>

                <div class="mt-auto pt-5">
                    <p class="small opacity-75 mb-3 fw-bold">CONNECT WITH US</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white fs-4"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white fs-4"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>