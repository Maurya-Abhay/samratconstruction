<?php
// contact.php - Ultra Premium Marketing Interface

// --- 1. Setup & Libraries ---
require_once __DIR__ . '/admin/lib_common.php';
require_once __DIR__ . '/admin/database.php';

// --- 2. Data Fetching (Services) ---
$services = [];
$result = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// --- 3. Form Submission Logic ---
$feedback = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(trim($_POST['mobile'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = 'invalid';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $feedback = 'invalid_mobile';
    } else {
        $stmt = $conn->prepare("INSERT INTO full_texts (name, email, mobile, subject, message) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $name, $email, $mobile, $subject, $message);
            if ($stmt->execute()) {
                // Email Logic (Brevo)
                $brevo_api_key = @file_get_contents(__DIR__ . '/admin/brevo_api_key.txt');
                $brevo_sender = @file_get_contents(__DIR__ . '/admin/brevo_sender.txt');
                $admin_email = trim($brevo_sender);
                
                $email_subject = "✨ New Inquiry: $subject";
                $email_body = '
                <div style="font-family: sans-serif; background: #f4f7fe; padding: 40px;">
                    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                        <h2 style="color: #2563eb; text-align: center;">New Lead Received</h2>
                        <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">
                        <p><strong>Name:</strong> '.$name.'</p>
                        <p><strong>Phone:</strong> '.$mobile.'</p>
                        <p><strong>Email:</strong> '.$email.'</p>
                        <p><strong>Subject:</strong> '.$subject.'</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #2563eb;">
                            '.nl2br($message).'
                        </div>
                    </div>
                </div>';

                $sent = send_brevo_email($admin_email, $email_subject, $email_body);
                $feedback = $sent ? 'success' : 'warning';
            } else {
                $feedback = 'fail:' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $feedback = 'fail:' . htmlspecialchars($conn->error);
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?feedback=' . urlencode($feedback));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#2563eb">
    <title>Samrat Construction | Build The Future</title>

    <!-- Icons & Manifest -->
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png">
    <link rel="manifest" href="/htdocs/manifest.json">

    <!-- Modern Fonts: Outfit (Headings) & Inter (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* --- ULTRA MODERN VARIABLES --- */
        :root {
            --primary: #2563eb;       /* Modern Blue */
            --secondary: #0f172a;     /* Deep Slate */
            --accent: #3b82f6;        /* Lighter Blue */
            --glass: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --blur: blur(20px);
            --font-head: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            --shadow-glow: 0 0 20px rgba(37, 99, 235, 0.3);
        }

        body {
            font-family: var(--font-body);
            background-color: #f8fafc;
            color: var(--secondary);
            overflow-x: hidden;
        }

        /* Aurora Background Animation */
        .aurora-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: #f8fafc;
            overflow: hidden;
        }
        .aurora-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 10s infinite ease-in-out;
        }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: #dbeafe; animation-delay: 0s; }
        .blob-2 { bottom: -10%; right: -10%; width: 600px; height: 600px; background: #e0e7ff; animation-delay: 2s; }
        .blob-3 { top: 40%; left: 40%; width: 400px; height: 400px; background: #f3e8ff; animation-delay: 4s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
        }

        /* --- TYPOGRAPHY --- */
        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); }
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- HERO SECTION --- */
        .hero-wrapper {
            position: relative;
            height: 100vh;
            min-height: 700px;
            width: 100%;
            overflow: hidden;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
            box-shadow: var(--shadow-lg);
        }
        
        .hero-video {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            filter: brightness(0.5);
            z-index: 0;
        }

        .hero-content {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            text-align: center;
            width: 100%;
            max-width: 1000px;
            color: white;
            padding: 20px;
        }

        
            /* Responsive Hero Image Height */
            .hero-img {
                width: 100%;
                height: 50vh;
                object-fit: cover;
            }
            @media (max-width: 768px) {
                /* Remove overlay text and fix hero layout */
                .hero-overlay-content {
                    display: none !important;
                }
                .carousel-item {
                    position: relative;
                }
                .hero-img {
                    display: block;
                    margin: 0 auto;
                }
                .hero-slider {
                    margin-bottom: 0;
                }
                .hero-img {
                    height: 30vh;
                }
                .hero-heading {
                    font-size: 2rem !important;
                    margin-bottom: 0.5rem !important;
                }
                .hero-btn {
                    padding: 12px 24px !important;
                    font-size: 1rem !important;
                    margin-top: 8px !important;
                    margin-bottom: 8px !important;
                }
                .container.hero-container {
                    margin-top: -0.5rem !important;
                    margin-bottom: 0 !important;
                    padding-bottom: 0 !important;
                }
                .hero-btn {
                    margin-bottom: 0 !important;
                }
                .hero-slider {
                    padding-bottom: 0 !important;
                }
                .stats-bar {
                    margin-top: 0 !important;
                }
            }
        .hero-tag {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .hero-heading {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .hero-btn {
            padding: 18px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            background: var(--primary);
            border: none;
            border-radius: 100px;
            box-shadow: var(--shadow-glow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 20px;
        }
        .hero-btn:hover {
            transform: translateY(-5px) scale(1.05);
            background: white;
            color: var(--primary);
        }

        /* --- STATS TICKER --- */
        .stats-bar {
            background: var(--secondary);
            padding: 30px 0;
            color: white;
            margin-top: -50px;
            position: relative;
            z-index: 5;
            width: 90%;
            margin-left: 5%;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
        }
        .stat-item { text-align: center; padding: 10px; }
        .stat-num { font-size: 2.5rem; font-weight: 700; color: var(--accent); }
        .stat-text { font-size: 0.9rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

        /* --- SERVICES (3D Cards) --- */
        .services-area { padding: 100px 0; }
        
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 3rem; font-weight: 800; margin-bottom: 15px; }
        .section-header p { font-size: 1.1rem; color: var(--text-light); max-width: 600px; margin: 0 auto; }

        .service-card-3d {
            background: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 24px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            transform-style: preserve-3d;
        }
        
        .service-img-container {
            height: 240px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }
        .service-img-container img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.6s ease;
        }
        .service-card-3d:hover .service-img-container img { transform: scale(1.1); }
        
        .service-content { padding: 25px 10px; }
        .service-price {
            position: absolute; top: 15px; right: 15px;
            background: rgba(255,255,255,0.95);
            padding: 6px 14px; border-radius: 50px;
            font-weight: 700; font-size: 0.85rem;
            color: var(--primary);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* --- CONTACT SECTION (Glass Panel) --- */
        .contact-section {
            padding: 100px 0;
            position: relative;
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-wrap: wrap;
        }

        .contact-left {
            flex: 1;
            padding: 60px;
            background: var(--secondary);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .contact-left::before {
            content: ''; position: absolute; top: -10%; left: -10%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
            opacity: 0.4;
        }

        .info-row {
            display: flex; align-items: center; margin-bottom: 30px; position: relative; z-index: 2;
        }
        .icon-circle {
            width: 60px; height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-right: 20px;
            color: #60a5fa; transition: 0.3s;
        }
        .info-row:hover .icon-circle { background: var(--primary); color: white; transform: scale(1.1); }

        .contact-right {
            flex: 1.2;
            padding: 60px;
            min-width: 350px;
        }

        /* --- MODERN INPUTS --- */
        .input-group-modern { position: relative; margin-bottom: 25px; }
        .input-modern {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid transparent;
            background: #f1f5f9;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            color: var(--secondary);
        }
        .input-modern:focus {
            outline: none;
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .input-modern::placeholder { color: #94a3b8; }

        .btn-send-modern {
            width: 100%;
            padding: 18px;
            font-size: 1.1rem;
            font-weight: 700;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 12px;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-send-modern:hover {
            background: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        /* --- FLOATING ACTION BUTTONS --- */
        .fab-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .fab-btn {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            text-decoration: none;
        }
        .fab-btn:hover { transform: scale(1.1); }
        .fab-wa { background: #25D366; }
        .fab-call { background: var(--primary); }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-heading { font-size: 2.5rem; }
            .hero-slider { height: 85vh; border-radius: 0 0 30px 30px; }
            .contact-left, .contact-right { padding: 30px 20px; }
            .stats-bar { flex-direction: column; gap: 20px; width: 94%; margin-left: 3%; }
            .glass-panel { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Aurora Background -->
    <div class="aurora-bg">
        <div class="aurora-blob blob-1"></div>
        <div class="aurora-blob blob-2"></div>
        <div class="aurora-blob blob-3"></div>
    </div>

    <?php include "header.php"; ?>

    <!-- 1. HERO SECTION -->
    <div id="heroCarousel" class="carousel slide carousel-fade hero-slider" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="img/slide1.jpg" class="hero-img" alt="Construction">
            </div>
            <div class="carousel-item">
                <img src="img/slide2.jpg" class="hero-img" alt="Interiors">
            </div>
        </div>
        <div class="container" style="position:relative; z-index:10; margin-top:-4rem;">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="hero-heading text-gradient mb-3" style="font-size:3rem; font-weight:800;">Crafting Premium Living Spaces</h1>
                    <a href="#contact" class="btn hero-btn mb-4">Start Your Project</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. STATS TICKER -->
    <div class="container" style="position:relative; z-index:10;">
        <div class="stats-bar" data-aos="fade-up" data-aos-offset="-50">
            <div class="stat-item">
                <div class="stat-num" data-target="150">0</div>
                <div class="stat-text">Projects Done</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-target="98">0</div>
                <div class="stat-text">Happy Clients</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-target="12">0</div>
                <div class="stat-text">Years Exp.</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-target="50">0</div>
                <div class="stat-text">Team Members</div>
            </div>
        </div>
    </div>

    <!-- 3. SERVICES GRID -->
    <section id="services" class="services-area">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="text-gradient">Our Expertise</h2>
                <p>We provide world-class construction services with a focus on quality, safety, and innovation.</p>
            </div>

            <div class="row g-4">
                <?php foreach ($services as $index => $service): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="service-card-3d" data-tilt>
                        <div class="service-img-container">
                            <img src="<?= htmlspecialchars($service['service_photo']) ?>" alt="Service">
                            <?php if (!empty($service['pricing'])): ?>
                                <div class="service-price">From <?= htmlspecialchars($service['pricing']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="service-content">
                            <h3 class="fw-bold text-dark"><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p class="text-muted small line-clamp-3"><?= htmlspecialchars($service['short_desc']) ?></p>
                            <a href="service-detail.php?id=<?= urlencode($service['id']) ?>" class="text-decoration-none fw-bold" style="color:var(--primary);">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 4. CONTACT SECTION -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="glass-panel" data-aos="zoom-in">
                <div class="row">
                    <!-- Left Info Modernized -->
                    <div class="col-lg-6 p-0">
                        <div class="contact-left" style="height:100%; display:flex; flex-direction:column; justify-content:center;">
                            <h3 class="fw-bold mb-2">Contact Us</h3>
                            <p class="mb-4 text-white-50">Let's discuss your next big project. We are here to help you build it.</p>
                            <div class="info-row mb-3">
                                <div class="icon-circle"><i class="bi bi-telephone-fill"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-white">Phone</h6>
                                    <div class="text-white-50 small">
                                        <?php 
                                            $contact_phone = get_setting('contact_phone', '+91 98765 43210');
                                            echo htmlspecialchars($contact_phone);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="info-row mb-3">
                                <div class="icon-circle"><i class="bi bi-envelope-fill"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-white">Email</h6>
                                    <div class="text-white-50 small">support@samratbuild.com</div>
                                </div>
                            </div>
                            <div class="info-row mb-4">
                                <div class="icon-circle"><i class="bi bi-geo-alt-fill"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-white">Office</h6>
                                    <div class="text-white-50 small">
                                        <?php
                                        require_once __DIR__ . '/admin/database.php';
                                        $conn = $conn ?? null;
                                        $footer_settings = [];
                                        $keys = ['office_address'];
                                        if ($conn) {
                                            $res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
                                            if ($res) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $footer_settings[$row['setting_key']] = $row['setting_value'];
                                                }
                                                $res->close();
                                            }
                                        }
                                        $defaults = ['office_address' => '123, Construction Plaza, Patna'];
                                        $footer_settings = array_merge($defaults, $footer_settings);
                                        echo htmlspecialchars($footer_settings['office_address']);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Google Map Embed -->
                            <div class="rounded shadow overflow-hidden" style="border:2px solid #2563eb;">
                                <iframe src="https://www.google.com/maps?q=Construction+Plaza,+Patna&output=embed" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                    <!-- Right Form Modernized -->
                    <div class="col-lg-6 p-0">
                        <div class="contact-right" style="height:100%; display:flex; flex-direction:column; justify-content:center;">
                            <h3 class="fw-bold mb-4">Send Message</h3>
                            <form method="POST" action="" autocomplete="off">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <input type="text" class="input-modern" name="name" placeholder="Your Name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <input type="tel" class="input-modern" name="mobile" placeholder="Phone Number" pattern="[0-9]{10}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group-modern">
                                            <input type="email" class="input-modern" name="email" placeholder="Email Address" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group-modern">
                                            <input type="text" class="input-modern" name="subject" placeholder="Subject" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="input-group-modern">
                                            <textarea class="input-modern" name="message" rows="4" placeholder="Describe your project..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn-send-modern">Send Message <i class="bi bi-paperplane ms-2"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Action Buttons -->
    <div class="fab-container">
        <?php
        require_once __DIR__ . '/admin/database.php';
        $conn = $conn ?? null;
        $footer_settings = [];
        $keys = ['contact_phone'];
        if ($conn) {
            $res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('" . implode("','", $keys) . "')");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $footer_settings[$row['setting_key']] = $row['setting_value'];
                }
                $res->close();
            }
        }
        $defaults = ['contact_phone' => '+919661329757'];
        $footer_settings = array_merge($defaults, $footer_settings);
        $contact_phone = $footer_settings['contact_phone'];
        $wa_number = preg_replace('/[^0-9]/', '', $contact_phone);
        if (strlen($wa_number) == 10) $wa_number = '91' . $wa_number;
        ?>
        <a href="https://wa.me/<?= $wa_number ?>" class="fab-btn fab-wa" target="_blank"><i class="bi bi-whatsapp"></i></a>
        <a href="tel:<?= htmlspecialchars($contact_phone) ?>" class="fab-btn fab-call"><i class="bi bi-telephone-fill"></i></a>
    </div>

    <?php include "footer.php"; ?>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Vanilla Tilt for 3D effect -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.7.0/vanilla-tilt.min.js"></script>

    <script>
        // Initialize Animations
        AOS.init({ duration: 1000, once: true, offset: 100 });
        
        // Initialize 3D Tilt
        VanillaTilt.init(document.querySelectorAll(".service-card-3d"), {
            max: 10, speed: 400, glare: true, "max-glare": 0.2
        });

        // Number Counter Animation
        const counters = document.querySelectorAll('.stat-num');
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            const increment = target / 100;
            const updateCount = () => {
                const c = +counter.innerText;
                if(c < target) {
                    counter.innerText = Math.ceil(c + increment);
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target + '+';
                }
            };
            updateCount();
        });

        // Feedback Alert
        const urlParams = new URLSearchParams(window.location.search);
        const feedback = urlParams.get('feedback');
        if (feedback) {
            let config = { title: 'Error', text: 'Something went wrong.', icon: 'error' };
            if(feedback === 'success') config = { title: 'Message Sent!', text: 'We will contact you shortly.', icon: 'success' };
            else if(feedback === 'warning') config = { title: 'Saved', text: 'Message saved, but email failed.', icon: 'info' };
            else if(feedback === 'invalid') config = { title: 'Invalid Email', text: 'Check email address.', icon: 'error' };
            
            Swal.fire({
                title: config.title, text: config.text, icon: config.icon,
                confirmButtonColor: '#2563eb', background: '#f8fafc', color: '#1e293b'
            }).then(() => { window.history.replaceState({}, document.title, window.location.pathname); });
        }
    </script>

</body>
</html>