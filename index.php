<?php
// --- 1. CONFIGURATION & SETUP ---
require_once __DIR__ . '/admin/lib_common.php';
require_once __DIR__ . '/admin/database.php';

// Initialize Variables
$services = [];
$site_settings = [
    'contact_phone' => '+91 00000 00000',
    'office_address' => '841220, JP Construction Work, Nagra, Ekma, Saran',
    'contact_email' => 'abhayprasad.maurya@gmail.com'
];
$feedback = "";

// --- 2. DATA FETCHING (Consolidated) ---
if ($conn) {
    // A. Fetch Services
    $svc_query = $conn->query("SELECT * FROM services ORDER BY created_at DESC LIMIT 6");
    if ($svc_query) {
        while ($row = $svc_query->fetch_assoc()) {
            $services[] = $row;
        }
    }

    // B. Fetch Site Settings (Phone, Address, etc.)
    $keys = "'contact_phone', 'office_address', 'contact_email'";
    $set_query = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($keys)");
    if ($set_query) {
        while ($row = $set_query->fetch_assoc()) {
            if (!empty($row['setting_value'])) {
                $site_settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
}

// --- 3. FORM SUBMISSION HANDLER ---
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
                
                // Email Notification (Brevo)
                $brevo_key_path = __DIR__ . '/admin/brevo_api_key.txt';
                $brevo_sender_path = __DIR__ . '/admin/brevo_sender.txt';

                if (file_exists($brevo_key_path) && file_exists($brevo_sender_path)) {
                    $admin_email = trim(file_get_contents($brevo_sender_path));
                    
                    $email_subject = "🚀 New Lead: $subject";
                    $email_body = "
                        <h3>New Website Inquiry</h3>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Phone:</strong> $mobile</p>
                        <p><strong>Email:</strong> $email</p>
                        <hr>
                        <p>" . nl2br($message) . "</p>
                    ";
                    
                    // Assuming send_brevo_email function exists in lib_common.php
                    send_brevo_email($admin_email, $email_subject, $email_body);
                }
                $feedback = 'success';
            } else {
                $feedback = 'db_error';
            }
            $stmt->close();
        } else {
            $feedback = 'conn_error';
        }
    }
    // PRG Pattern to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?feedback=" . $feedback . "#contact");
    exit();
}

// Prepare WhatsApp Number
$wa_number = preg_replace('/[^0-9]/', '', $site_settings['contact_phone']);
if (strlen($wa_number) <= 10) $wa_number = '91' . $wa_number;
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
        <title>JP Construction | Premium Building Solutions</title>
        <meta name="description" content="Leading construction and interior design services.">
        <meta name="theme-color" content="#0d6efd">
        <!-- PWA Manifest & Service Worker now in header.php -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
        <style>
        :root {
            --font-head: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --primary: #0d6efd;
            --dark-blue: #0a2647;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--light-bg);
            color: #444;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); font-weight: 700; color: var(--dark-blue); }

        /* --- Hero Section --- */
        .hero-section {
            position: relative;
            height: 65vh;
            min-height: 550px;
            background: url('admin/assets/jp_hero.webp') no-repeat center center/cover; /* Fallback */
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, rgba(10, 38, 71, 0.85) 0%, rgba(13, 110, 253, 0.6) 100%);
            z-index: 1;
        }
        .hero-content { position: relative; z-index: 2; color: white; text-align: center; }

        /* --- Stats Card --- */
        .stats-wrapper {
        <meta charset="UTF-8">
            margin-top: -60px;
            position: relative;
            z-index: 10;
        }
        .stats-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            padding: 40px;
        }

        /* --- Services --- */
        .service-card {
            border: none;
            border-radius: 16px;
            transition: all 0.4s ease;
            background: white;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(13, 110, 253, 0.15);
        }
        .service-img-wrapper { height: 220px; overflow: hidden; }
        .service-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .service-card:hover .service-img-wrapper img { transform: scale(1.1); }

        /* --- Contact Form --- */
        .contact-box {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .contact-info-panel {
            background: var(--dark-blue);
            color: white;
            padding: 60px;
            position: relative;
        }
        /* Abstract shape on contact panel */
        .contact-info-panel::after {
            content: ''; position: absolute; bottom: -50px; right: -50px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }

        .form-floating > .form-control:focus ~ label { color: var(--primary); }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
        
        /* --- Floating Buttons --- */
        .fab-container { position: fixed; bottom: 30px; right: 30px; z-index: 1000; display: flex; flex-direction: column; gap: 15px; }
        .fab-btn {
            width: 55px; height: 55px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.5rem; text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s;
        }
        .fab-btn:hover { transform: scale(1.1); color: white; }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <header class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container hero-content" data-aos="fade-up">
            <h1 class="display-3 fw-bold mb-3">Building Dreams <br><span class="text-info">Constructing Reality</span></h1>
            <p class="lead mb-4 opacity-75 mx-auto" style="max-width: 700px;">
                Award-winning residential and commercial construction services. We deliver precision, quality, and excellence in every brick we lay.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#contact" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-lg">Get a Quote</a>
                <a href="#services" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold">Our Work</a>
            </div>
        </div>
    </header>

    <div class="container stats-wrapper" data-aos="fade-up" data-aos-delay="100">
        <div class="stats-card">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6 border-end">
                    <h2 class="fw-bold text-primary mb-0"><span class="counter" data-target="250">0</span>+</h2>
                    <small class="text-uppercase fw-bold text-muted ls-1">Projects</small>
                </div>
                <div class="col-md-3 col-6 border-end-md">
                    <h2 class="fw-bold text-primary mb-0"><span class="counter" data-target="98">0</span>%</h2>
                    <small class="text-uppercase fw-bold text-muted ls-1">Satisfaction</small>
                </div>
                <div class="col-md-3 col-6 border-end">
                    <h2 class="fw-bold text-primary mb-0"><span class="counter" data-target="12">0</span>+</h2>
                    <small class="text-uppercase fw-bold text-muted ls-1">Years Exp</small>
                </div>
                <div class="col-md-3 col-6">
                    <h2 class="fw-bold text-primary mb-0"><span class="counter" data-target="50">0</span>+</h2>
                    <small class="text-uppercase fw-bold text-muted ls-1">Experts</small>
                </div>
            </div>
        </div>
    </div>

    <section id="services" class="py-2 mt-2">
        <div class="container py-4">
            <div class="text-center mb-5" data-aos="fade-up">
                <h6 class="text-primary fw-bold text-uppercase letter-spacing-2">What We Do</h6>
                <h2 class="display-5 fw-bold">Our Expertise</h2>
            </div>

            <div class="row g-4">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $index => $svc): ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        <div class="service-card h-100 d-flex flex-column">
                            <div class="service-img-wrapper position-relative">
                                                                <?php
                                                                    $img = htmlspecialchars($svc['service_photo'] ?? 'img/default-service.jpg');
                                                                    if ($img && strpos($img, 'uploads/') === false && $img !== 'img/default-service.jpg') {
                                                                        $img = 'admin/uploads/' . $img;
                                                                    }
                                                                    $img_url = $img;
                                                                    if (file_exists($img)) {
                                                                        $img_url .= '?v=' . filemtime($img);
                                                                    } else {
                                                                        $img_url .= '?v=' . time();
                                                                    }
                                                                ?>
                                                                <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($svc['service_name']) ?>">
                                <div class="position-absolute top-0 end-0 m-3 badge bg-light text-dark shadow-sm rounded-pill px-3 py-2">
                                    <?= htmlspecialchars($svc['pricing'] ?? 'Best Price') ?>
                                </div>
                            </div>
                            <div class="p-4 flex-grow-1 d-flex flex-column">
                                <h4 class="mb-3"><?= htmlspecialchars($svc['service_name']) ?></h4>
                                <p class="text-muted small mb-4 flex-grow-1">
                                    <?= htmlspecialchars(substr($svc['short_desc'], 0, 100)) ?>...
                                </p>
                                <a href="service-detail.php?id=<?= $svc['id'] ?>" class="btn btn-outline-primary rounded-pill w-100 fw-bold">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center p-5">
                        <p class="text-muted">Services are currently being updated.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="py-2 mb-3">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h6 class="text-primary fw-bold text-uppercase letter-spacing-2">Contact us make a project</h6>
                <h2 class="display-5 fw-bold">Contact Us</h2>
            </div>
            <div class="contact-box" data-aos="zoom-in">
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="contact-info-panel h-100 d-flex flex-column justify-content-center">
                            <h3 class="display-6 fw-bold mb-4">Let's build something great together.</h3>
                            <p class="mb-5 opacity-75">Reach out to us for a free consultation. Our engineers are ready to analyze your requirements.</p>
                            
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-white me-3">
                                    <i class="bi bi-telephone-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Call Us</small>
                                    <div class="fs-5 fw-bold"><?= htmlspecialchars($site_settings['contact_phone']) ?></div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-white me-3">
                                    <i class="bi bi-envelope-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Email Us</small>
                                    <div class="fs-5 fw-bold"><?= htmlspecialchars($site_settings['contact_email']) ?></div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-25 p-3 rounded-circle text-white me-3">
                                    <i class="bi bi-geo-alt-fill fs-4"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 text-uppercase fw-bold">Visit Us</small>
                                    <div class="small fw-light"><?= htmlspecialchars($site_settings['office_address']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 bg-white p-5">
                        <div class="p-lg-4">
                            <h3 class="fw-bold text-dark mb-2">Send a Message</h3>
                            <p class="text-muted mb-4">Fill out the form below and we will get back to you within 24 hours.</p>

                            <form action="" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                                            <label for="name">Your Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Phone" pattern="[0-9]{10}" required>
                                            <label for="mobile">Mobile Number</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                            <label for="email">Email Address</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                                            <label for="subject">Subject</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" placeholder="Leave a comment here" id="message" name="message" style="height: 150px" required></textarea>
                                            <label for="message">Project Details</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm" type="submit">
                                            Send Message <i class="bi bi-send ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="fab-container">
        <a href="https://wa.me/<?= $wa_number ?>" class="fab-btn" style="background: #25D366;" target="_blank">
            <i class="bi bi-whatsapp"></i>
        </a>
    </div>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Init Animations
        AOS.init({ duration: 800, once: true });

        // Counter Animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = target / 200;
            const updateCount = () => {
                const c = +counter.innerText;
                if (c < target) {
                    counter.innerText = Math.ceil(c + increment);
                    setTimeout(updateCount, 10);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });

        // Feedback Handling
        const urlParams = new URLSearchParams(window.location.search);
        const feedback = urlParams.get('feedback');
        
        if (feedback) {
            let config = { icon: 'error', title: 'Oops...', text: 'Something went wrong!' };
            
            switch(feedback) {
                case 'success':
                    config = { icon: 'success', title: 'Message Sent!', text: 'We will contact you shortly.', confirmButtonColor: '#0d6efd' };
                    break;
                case 'invalid_email':
                    config = { icon: 'warning', title: 'Invalid Email', text: 'Please enter a valid email address.' };
                    break;
                case 'invalid_mobile':
                    config = { icon: 'warning', title: 'Invalid Phone', text: 'Please enter a valid 10-digit number.' };
                    break;
                case 'db_error':
                    config.text = 'Database connection failed. Try again later.';
                    break;
            }

            Swal.fire(config).then(() => {
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>