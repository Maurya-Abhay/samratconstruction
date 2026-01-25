<?php
require_once __DIR__ . '/admin/lib_common.php';
require_once __DIR__ . '/admin/database.php'; // Ensure DB connection is included

$alertMsg = '';
$alertType = '';

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
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO full_texts (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $name, $email, $mobile, $subject, $message);
                if ($stmt->execute()) {
                    $feedback = 'success';
                } else {
                    $feedback = 'error';
                }
                $stmt->close();
            } else {
                $feedback = 'error';
            }
        } else {
            $feedback = 'conn_error';
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . $feedback);
    exit();
}

// Feedback Handling
if (isset($_GET['feedback'])) {
    $f = $_GET['feedback'];
    if ($f === 'success') {
        $alertType = 'success';
        $alertMsg = 'Message Sent Successfully! We will contact you shortly.';
    } elseif ($f === 'invalid_email') {
        $alertType = 'warning';
        $alertMsg = 'Please enter a valid Email Address.';
    } elseif ($f === 'invalid_mobile') {
        $alertType = 'warning';
        $alertMsg = 'Please enter a valid 10-digit Mobile Number.';
    } else {
        $alertType = 'danger';
        $alertMsg = 'Something went wrong. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | JP Construction</title>
    <meta name="theme-color" content="#0b1c2c">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Oswald:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0b1c2c; /* Deep Navy */
            --accent: #ff9f1c;  /* Amber */
            --light-bg: #f4f7f6;
            --text-dark: #1a1a1a;
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--light-bg);
            color: var(--text-dark);
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); letter-spacing: 0.5px; }

        /* --- PAGE HEADER --- */
        .page-header {
            background: linear-gradient(rgba(11, 28, 44, 0.95), rgba(11, 28, 44, 0.8)), url('admin/assets/jp_hero.webp');
            background-size: cover;
            background-position: center;
            padding: 100px 0 140px;
            color: white;
            text-align: center;
            position: relative;
        }
        .page-header h1 { font-weight: 700; text-transform: uppercase; letter-spacing: 2px; }
        .page-header .breadcrumb { justify-content: center; opacity: 0.8; font-size: 0.9rem; margin-top: 15px; }
        .text-accent { color: var(--accent) !important; }

        /* --- MAIN WRAPPER --- */
        .main-wrapper {
            margin-top: -80px;
            margin-bottom: 80px;
            position: relative;
            z-index: 2;
        }

        .contact-card {
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 30px 60px -10px rgba(11, 28, 44, 0.15);
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
        }

        /* --- LEFT SIDE: INFO (DARK) --- */
        .info-side {
            background: var(--primary);
            color: white;
            padding: 4rem 3rem;
            position: relative;
        }
        .info-side::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px; pointer-events: none;
        }

        .contact-item {
            margin-bottom: 2.5rem;
            padding-left: 1.5rem;
            border-left: 3px solid rgba(255, 159, 28, 0.3);
            transition: 0.3s;
        }
        .contact-item:hover { border-left-color: var(--accent); }
        .contact-item h6 { color: var(--accent); margin-bottom: 5px; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; }
        .contact-item p { margin: 0; font-size: 1.1rem; font-weight: 500; }
        .contact-item i { display: none; } /* Hidden icon, using border style instead */

        /* --- RIGHT SIDE: FORM (MODERN) --- */
        .form-side {
            padding: 4rem 3rem;
            background: white;
        }

        /* Modern Input Styling (Floating Label) */
        .modern-group { position: relative; margin-bottom: 1.8rem; }
        
        .modern-control {
            width: 100%; padding: 10px 0;
            font-size: 1rem; color: #333;
            border: none; border-bottom: 2px solid #e0e0e0;
            outline: none; background: transparent;
            transition: all 0.3s; font-family: var(--font-body);
            border-radius: 0;
        }
        .modern-control:focus { border-bottom-color: var(--primary); }
        .modern-control:focus ~ label,
        .modern-control:not(:placeholder-shown) ~ label {
            top: -12px; font-size: 0.75rem; color: var(--accent); font-weight: 700;
        }
        .modern-group label {
            position: absolute; top: 10px; left: 0;
            font-size: 1rem; color: #999; pointer-events: none;
            transition: all 0.3s ease;
        }
        
        /* Select Dropdown Fix */
        select.modern-control { cursor: pointer; color: #333; }
        select.modern-control:invalid { color: #999; }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 40px;
            font-family: var(--font-head);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 1rem;
            width: 100%;
            transition: 0.4s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: var(--accent);
            color: var(--primary);
            box-shadow: 0 10px 20px rgba(255, 159, 28, 0.3);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 991px) {
            .info-side, .form-side { padding: 2.5rem; }
            .main-wrapper { margin-top: -40px; }
            .page-header { padding: 80px 0 100px; }
        }
    </style>
</head>
<body>

<?php include "header.php"; ?>

<section class="page-header">
    <div class="container">
        <h1 class="display-4">Contact <span class="text-accent">Us</span></h1>
        <p class="lead opacity-75 mb-0" style="font-family: var(--font-body);">We are ready to build your vision.</p>
    </div>
</section>

<div class="container main-wrapper">
    
    <?php if ($alertMsg): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm border-0 mb-4 rounded-0" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <strong>Note:</strong> <?= $alertMsg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="contact-card">
        
        <div class="col-lg-4 info-side">
            <h3 class="mb-4">Get In Touch</h3>
            <p class="opacity-75 mb-5">Reach out to us for quotes, consultations, or general inquiries. Our team is here to help.</p>

            <div class="contact-item">
                <h6>Office Location</h6>
                <p>841220, JP Construction Work, Nagra, Ekma, Saran</p>
            </div>

            <div class="contact-item">
                <h6>Phone Number</h6>
                <p>+91 00000 00000</p>
            </div>

            <div class="contact-item">
                <h6>Email Support</h6>
                <p style="word-break: break-all;">abhayprasad.maurya@gmail.com</p>
            </div>

            <div class="mt-5 pt-4 border-top border-secondary">
                <small class="text-accent text-uppercase ls-1">Follow Us</small>
                <div class="mt-3 d-flex gap-3">
                    <a href="#" class="text-white fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white fs-5"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>

        <div class="col-lg-8 form-side">
            <h3 class="text-dark mb-2">Send Message</h3>
            <p class="text-muted mb-5">Fill the form below and we will get back to you shortly.</p>

            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="modern-group">
                            <input type="text" name="name" id="name" class="modern-control" placeholder=" " required>
                            <label for="name">Full Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="modern-group">
                            <input type="tel" name="mobile" id="mobile" class="modern-control" placeholder=" " pattern="[0-9]{10}" required>
                            <label for="mobile">Mobile Number</label>
                        </div>
                    </div>
                </div>

                <div class="modern-group">
                    <input type="email" name="email" id="email" class="modern-control" placeholder=" " required>
                    <label for="email">Email Address</label>
                </div>

                <div class="modern-group">
                    <select name="subject" id="subject" class="modern-control" required>
                        <option value="" disabled selected></option>
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Residential Project">Residential Project</option>
                        <option value="Commercial Project">Commercial Project</option>
                        <option value="Renovation">Renovation Work</option>
                    </select>
                    <label for="subject">How can we help?</label>
                </div>

                <div class="modern-group">
                    <textarea name="message" id="message" class="modern-control" rows="3" placeholder=" " required></textarea>
                    <label for="message">Project Details...</label>
                </div>

                <button type="submit" class="btn-submit">
                    Submit Request <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </form>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>