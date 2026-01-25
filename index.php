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

// --- 2. DATA FETCHING ---
if ($conn) {
    // A. Fetch Services (Show all, ascending by id)
    $svc_query = $conn->query("SELECT * FROM services ORDER BY id ASC");
    if ($svc_query) {
        while ($row = $svc_query->fetch_assoc()) {
            $services[] = $row;
        }
    }

    // B. Fetch Site Settings
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
                
                // Email Notification (Brevo logic kept same as original)
                $brevo_key_path = __DIR__ . '/admin/brevo_api_key.txt';
                $brevo_sender_path = __DIR__ . '/admin/brevo_sender.txt';

                if (file_exists($brevo_key_path) && file_exists($brevo_sender_path)) {
                    $admin_email = trim(file_get_contents($brevo_sender_path));
                    $email_subject = "🚀 New Lead: $subject";
                    $email_body = "<h3>New Website Inquiry</h3><p><strong>Name:</strong> $name</p><p><strong>Phone:</strong> $mobile</p><p><strong>Email:</strong> $email</p><hr><p>" . nl2br($message) . "</p>";
                    
                    if(function_exists('send_brevo_email')) {
                        send_brevo_email($admin_email, $email_subject, $email_body);
                    }
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
    // PRG Pattern
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
    <title>JP Construction | Master Builders</title>
    <meta name="description" content="Premium construction and interior design services.">
    <meta name="theme-color" content="#0b1c2c">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Oswald:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* Palette: Premium Industrial */
            --primary: #0b1c2c; /* Deep Navy */
            --accent: #ff9f1c;  /* Construction Amber */
            --light: #f4f7f6;
            --text-dark: #1a1a1a;
            --text-gray: #6c757d;
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
            
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 20px 40px -5px rgba(11, 28, 44, 0.15);
        }

        body {
            font-family: var(--font-body);
            background-color: #fdfdfd;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- GLOBAL UTILS --- */
        .text-accent { color: var(--accent) !important; }
        .bg-accent { background-color: var(--accent) !important; }
        .btn-custom {
            background: var(--accent);
            color: var(--primary);
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 0; /* Sharp edges for construction feel */
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: 2px solid var(--accent);
        }
        .btn-custom:hover {
            background: transparent;
            color: var(--accent);
        }
        .section-title { margin-bottom: 3rem; position: relative; display: inline-block; }
        .section-title::after {
            content: ''; position: absolute; bottom: -10px; left: 0; width: 60px; height: 4px; background: var(--accent);
        }

        /* --- HERO SECTION (KEPT ORIGINAL) --- */
        .hero-section {
            position: relative;
            height: 90vh; /* Almost full screen */
            min-height: 600px;
            background: url('admin/assets/jp_hero.webp') no-repeat center center/cover;
            display: flex;
            align-items: center;
        }
        .hero-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(90deg, rgba(11, 28, 44, 0.95) 0%, rgba(11, 28, 44, 0.7) 50%, rgba(11, 28, 44, 0.2) 100%);
        }
        .hero-content { position: relative; z-index: 2; color: white; padding-top: 60px; }
        .hero-subtitle { font-family: var(--font-body); font-weight: 300; letter-spacing: 2px; color: var(--accent); display: block; margin-bottom: 15px; }
        
        .scroll-down {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
            color: white; animation: bounce 2s infinite; opacity: 0.7;
        }
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateX(-50%) translateY(0);} 40% {transform: translateX(-50%) translateY(-10px);} 60% {transform: translateX(-50%) translateY(-5px);} }

        /* --- STATS BAR (Original) --- */
        .stats-bar-wrapper { margin-top: -80px; position: relative; z-index: 10; }
        .stats-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            padding: 40px;
            border-bottom: 4px solid var(--accent);
        }
        .stat-item h2 { font-size: 2.5rem; color: var(--primary); margin-bottom: 0; }
        .stat-item p { font-size: 0.9rem; font-weight: 600; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; }

        /* --- SERVICES SECTION (Original) --- */
        .service-card {
            background: white; border: none; border-radius: 4px; overflow: hidden; height: 100%;
            box-shadow: var(--card-shadow); transition: all 0.4s ease; position: relative;
        }
        .service-card:hover { transform: translateY(-10px); box-shadow: var(--hover-shadow); }
        .service-img-box { height: 240px; overflow: hidden; position: relative; }
        .service-img-box img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .service-card:hover .service-img-box img { transform: scale(1.1); }
        .service-price-tag {
            position: absolute; bottom: 0; right: 0;
            background: var(--accent); color: var(--primary);
            padding: 5px 15px; font-weight: bold; font-family: var(--font-head);
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);
        }
        .service-body { padding: 25px; }
        .service-link { color: var(--primary); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; margin-top: auto; }
        .service-link:hover { color: var(--accent); }

        /* =========================================
           NEW MODERN CONTACT CSS (FIXED & NEXT GEN)
           ========================================= */
        .contact-modern-section {
            background: #fff;
            position: relative;
        }
        
        .contact-modern-card {
            background: #fff;
            box-shadow: 0 30px 60px -10px rgba(11, 28, 44, 0.15);
            overflow: hidden;
            display: flex;
            flex-wrap: wrap; /* Critical fix for layout */
            border-radius: 4px;
        }

        /* Left Side: Dark Info Panel */
        .contact-side-panel {
            background: var(--primary);
            color: #fff;
            padding: 3rem;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%; /* Ensures equal height */
        }
        
        /* Pattern Overlay on Left Panel */
        .contact-side-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }

        .contact-detail-box {
            margin-bottom: 2rem;
            padding-left: 1rem;
            border-left: 3px solid rgba(255, 159, 28, 0.3); /* Dimmed accent */
            transition: 0.3s;
        }
        .contact-detail-box:hover {
            border-left-color: var(--accent);
            background: rgba(255,255,255,0.03);
        }
        .contact-detail-box h6 { color: var(--accent); margin-bottom: 5px; font-size: 0.85rem; letter-spacing: 1px; }
        .contact-detail-box p { margin: 0; font-family: var(--font-body); font-weight: 500; font-size: 1.1rem; }
        
        /* Right Side: Modern Form */
        .contact-form-panel {
            padding: 3rem;
            background: #fff;
        }

        /* Modern Input Styling */
        .modern-input-group { position: relative; margin-bottom: 1.5rem; }
        
        .modern-input {
            width: 100%;
            padding: 12px 0;
            font-size: 1rem;
            color: #333;
            border: none;
            border-bottom: 2px solid #e0e0e0;
            outline: none;
            background: transparent;
            transition: all 0.3s;
            font-family: var(--font-body);
        }
        
        .modern-input:focus { border-bottom-color: var(--primary); }
        .modern-input:focus ~ label,
        .modern-input:not(:placeholder-shown) ~ label {
            top: -10px;
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 700;
        }

        .modern-input + label {
            position: absolute;
            top: 12px;
            left: 0;
            font-size: 1rem;
            color: #999;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .btn-modern-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 40px;
            font-family: var(--font-head);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            transition: 0.4s;
            display: block;
            width: 100%;
            margin-top: 20px;
        }
        .btn-modern-submit:hover {
            background: var(--accent);
            color: var(--primary);
            box-shadow: 0 10px 20px rgba(255, 159, 28, 0.3);
        }

        /* --- FAB --- */
        .fab-whatsapp {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; background: #25D366;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: white; font-size: 30px; box-shadow: 0 5px 20px rgba(37, 211, 102, 0.4);
            transition: transform 0.3s; z-index: 1000; text-decoration: none;
        }
        .fab-whatsapp:hover { transform: scale(1.1) rotate(15deg); color: white; }

        /* Responsive Fix for Contact */
        @media (max-width: 991px) {
            .contact-side-panel { padding: 2rem; min-height: auto; }
            .contact-form-panel { padding: 2rem; }
            .contact-bg-text { display: none; }
        }
        @media (max-width: 768px) {
            .hero-section { height: auto; padding: 100px 0; }
            .stats-bar-wrapper { margin-top: 0; transform: translateY(-30px); }
        }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <header class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="row">
                <div class="col-lg-8" data-aos="fade-right" data-aos-duration="1000">
                    <span class="hero-subtitle">JP CONSTRUCTION</span>
                    <h1 class="display-2 fw-bold mb-4">WE BUILD VISIONS <br><span class="text-accent">INTO REALITY</span></h1>
                    <p class="lead mb-5 opacity-75 fw-light" style="max-width: 600px; border-left: 3px solid var(--accent); padding-left: 20px;">
                        From groundbreaking architectural designs to premium interior finishes. We deliver excellence with precision engineering and superior craftsmanship.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#contact" class="btn btn-custom">Get A Quote</a>
                        <a href="#services" class="btn btn-outline-light rounded-0 px-4 py-3 fw-bold text-uppercase ls-1">View Projects</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-down">
            <i class="bi bi-chevron-double-down fs-3"></i>
        </div>
    </header>

    <div class="container stats-bar-wrapper" data-aos="fade-up" data-aos-delay="100">
        <div class="stats-container">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6 border-end">
                    <div class="stat-item"><h2 class="counter" data-target="250">0</h2><p>Projects Done</p></div>
                </div>
                <div class="col-md-3 col-6 border-end d-md-block d-none">
                    <div class="stat-item"><h2 class="counter" data-target="150">0</h2><p>Renovations</p></div>
                </div>
                <div class="col-md-3 col-6 border-end">
                    <div class="stat-item"><h2 class="counter" data-target="98">0</h2><p>Happy Clients</p></div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item"><h2 class="counter" data-target="12">0</h2><p>Years Experience</p></div>
                </div>
            </div>
        </div>
    </div>

    <section id="services" class="py-5 my-5">
        <div class="container">
            <div class="row align-items-end mb-5" data-aos="fade-up">
                <div class="col-md-8">
                    <h5 class="text-accent ls-1">OUR EXPERTISE</h5>
                    <h2 class="display-5 fw-bold section-title text-dark">Premium Services</h2>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="text-muted">Explore our wide range of construction and interior solutions designed for modern living.</p>
                </div>
            </div>

            <div class="row g-4">
                <?php if (!empty($services)): ?>
                    <?php $serial = 1; foreach ($services as $svc): ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        <div class="service-card h-100 d-flex flex-column">
                            <?php $serial++; ?>
                            <div class="service-img-box">
                                <?php
                                    $img = htmlspecialchars($svc['service_photo'] ?? 'img/default.jpg');
                                    if ($img && strpos($img, 'uploads/') === false && $img !== 'img/default.jpg') {
                                        $img = 'admin/uploads/' . $img;
                                    }
                                    $img_url = file_exists($img) ? $img : 'admin/assets/default_service.jpg';
                                ?>
                                <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($svc['service_name']) ?>">
                                <div class="service-price-tag"><?= htmlspecialchars($svc['pricing'] ?? 'Ask Price') ?></div>
                            </div>
                            <div class="service-body flex-grow-1 d-flex flex-column">
                                <h4 class="mb-3"><?= htmlspecialchars($svc['service_name']) ?></h4>
                                <p class="text-muted small mb-4 flex-grow-1">
                                    <?= htmlspecialchars(mb_strimwidth($svc['short_desc'], 0, 90, "...")) ?>
                                </p>
                                <a href="service-detail.php?id=<?= $svc['id'] ?>" class="service-link">
                                    READ MORE <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5"><div class="alert alert-light">Updating Service Catalog...</div></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="contact-modern-section py-5">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-xl-11">
                    
                    <div class="contact-modern-card row g-0" data-aos="fade-up">
                        
                        <div class="col-lg-4 col-md-5">
                            <div class="contact-side-panel h-100">
                                <div>
                                    <h3 class="mb-4">Get In Touch</h3>
                                    <p class="opacity-75 mb-5">Start your dream project today. We are ready to build it.</p>
                                </div>

                                <div class="contact-details">
                                    <div class="contact-detail-box">
                                        <h6>PHONE</h6>
                                        <p><?= htmlspecialchars($site_settings['contact_phone']) ?></p>
                                    </div>
                                    <div class="contact-detail-box">
                                        <h6>EMAIL</h6>
                                        <p style="font-size: 0.95rem; word-break: break-all;"><?= htmlspecialchars($site_settings['contact_email']) ?></p>
                                    </div>
                                    <div class="contact-detail-box">
                                        <h6>OFFICE</h6>
                                        <p style="font-size: 0.95rem;"><?= htmlspecialchars($site_settings['office_address']) ?></p>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-top border-secondary">
                                    <small class="text-accent text-uppercase ls-1">Follow Us</small>
                                    <div class="mt-2 d-flex gap-3">
                                        <a href="#" class="text-white"><i class="bi bi-facebook fs-5"></i></a>
                                        <a href="#" class="text-white"><i class="bi bi-instagram fs-5"></i></a>
                                        <a href="#" class="text-white"><i class="bi bi-linkedin fs-5"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 col-md-7">
                            <div class="contact-form-panel h-100">
                                <h3 class="text-dark mb-2">Send Message</h3>
                                <p class="text-muted mb-5">Fill the form below and we will get back to you within 24 hours.</p>

                                <form action="" method="POST">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="modern-input-group">
                                                <input type="text" class="modern-input" name="name" id="name" placeholder=" " required>
                                                <label for="name">Your Name</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="modern-input-group">
                                                <input type="tel" class="modern-input" name="mobile" id="mobile" placeholder=" " pattern="[0-9]{10}" required>
                                                <label for="mobile">Mobile Number</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="modern-input-group">
                                                <input type="email" class="modern-input" name="email" id="email" placeholder=" " required>
                                                <label for="email">Email Address</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="modern-input-group">
                                                <input type="text" class="modern-input" name="subject" id="subject" placeholder=" " required>
                                                <label for="subject">Project Subject</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="modern-input-group">
                                                <textarea class="modern-input" name="message" id="message" rows="3" placeholder=" " required></textarea>
                                                <label for="message">Tell us about your project...</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn-modern-submit">
                                                Submit Request <i class="bi bi-arrow-right-short"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <a href="https://wa.me/<?= $wa_number ?>" class="fab-whatsapp" target="_blank" title="Chat on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        AOS.init({ duration: 800, once: true, offset: 100 });

        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / 200;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 15);
                } else {
                    counter.innerText = target + "+";
                }
            };
            updateCount();
        });

        const urlParams = new URLSearchParams(window.location.search);
        const feedback = urlParams.get('feedback');
        
        if (feedback) {
            let config = { icon: 'error', title: 'Error', confirmButtonColor: '#0b1c2c' };
            if (feedback === 'success') {
                config = { icon: 'success', title: 'Request Sent!', text: 'We will contact you shortly.', confirmButtonColor: '#ff9f1c' };
            } else if (feedback === 'invalid_email') {
                config.text = 'Please enter a valid email address.';
            } else if (feedback === 'invalid_mobile') {
                config.text = 'Please enter a valid 10-digit mobile number.';
            } else {
                config.text = 'A technical error occurred. Please try again.';
            }
            Swal.fire(config).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>