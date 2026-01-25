<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>About Us | JP Construction</title>
    <meta name="description" content="Learn about JP Construction, our experienced team, our mission, and our commitment to quality construction and interior design services in India.">
    <meta name="keywords" content="About JP Construction, construction company, interior design, team, mission, India, building experts">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpconstruction.in/about.php">
    <meta name="theme-color" content="#0d6efd">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="About Us | JP Construction">
    <meta property="og:description" content="Learn about JP Construction, our experienced team, our mission, and our commitment to quality construction and interior design services in India.">
    <meta property="og:url" content="https://jpconstruction.in/about.php">
    <meta property="og:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us | JP Construction">
    <meta name="twitter:description" content="Learn about JP Construction, our experienced team, our mission, and our commitment to quality construction and interior design services in India.">
    <meta name="twitter:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-purple: #8245ec;
            --brand-dark: #1a1a1a;
            --text-gray: #636e72;
            --soft-bg: #f8faff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fff;
            color: var(--brand-dark);
        }

        /* --- Hero Section --- */
        .hero-about {
            background: linear-gradient(rgba(26, 26, 26, 0.8), rgba(26, 26, 26, 0.8)), 
                        url('admin/assets/jp_hero.webp') no-repeat center center/cover;
            height: 550px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .hero-about h1 {
            font-weight: 800;
            font-size: 3.5rem;
            letter-spacing: -1px;
        }

        /* --- Content Styling --- */
        .section-padding { padding: 100px 0; }

        .about-image-wrapper {
            position: relative;
            padding: 20px;
        }

        .about-image-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 80%;
            height: 80%;
            border: 10px solid var(--brand-purple);
            z-index: -1;
            border-radius: 20px;
            opacity: 0.15;
        }

        .main-img {
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
            transition: transform 0.4s ease;
        }

        .main-img:hover { transform: translateY(-10px); }

        .experience-badge {
            position: absolute;
            bottom: -20px;
            right: 0;
            background: var(--brand-purple);
            color: #fff;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 30px rgba(130, 69, 236, 0.3);
        }

        /* --- Feature Cards --- */
        .feature-box {
            background: var(--soft-bg);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-box:hover {
            background: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.06);
            border-color: var(--brand-purple);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 1.5rem;
            color: var(--brand-purple);
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(130, 69, 236, 0.1);
        }

        /* --- Stats Bar --- */
        .stats-wrapper {
            background: var(--brand-dark);
            border-radius: 30px;
            padding: 60px 20px;
            margin-top: -80px;
            position: relative;
            z-index: 10;
        }

        .stat-item h2 {
            font-weight: 800;
            color: var(--brand-purple);
            font-size: 2.8rem;
            margin-bottom: 5px;
        }

        .stat-item p {
            color: #a0a0a0;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 2px;
            margin-bottom: 0;
        }

        .highlight-check {
            list-style: none;
            padding: 0;
        }

        .highlight-check li {
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .highlight-check li i {
            color: #00b894;
            margin-right: 12px;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .hero-about h1 { font-size: 2.2rem; }
            .section-padding { padding: 60px 0; }
        }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <div class="hero-about">
        <div class="container">
            <span class="badge bg-primary px-3 py-2 rounded-pill mb-3">WHO WE ARE</span>
            <h1>Architects of the Future</h1>
            <p class="lead text-white-50">Transforming visions into concrete reality since 2015.</p>
        </div>
    </div>

    <div class="container">
        <div class="stats-wrapper text-center">
            <div class="row g-4">
                <div class="col-6 col-md-3 stat-item">
                    <h2>10+</h2>
                    <p>Years in Industry</p>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <h2>250+</h2>
                    <p>Projects Delivered</p>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <h2>500+</h2>
                    <p>Experts Team</p>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <h2>99%</h2>
                    <p>Happy Clients</p>
                </div>
            </div>
        </div>
    </div>

    <section class="section-padding">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="about-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=1000" class="img-fluid main-img" alt="Work Site">
                        <div class="experience-badge d-none d-md-block">
                            <h3 class="mb-0 fw-bold">10+</h3>
                            <small>Years Of Journey</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h6 class="text-primary fw-bold text-uppercase mb-3">About JP Construction</h6>
                    <h2 class="display-5 fw-bold mb-4">Crafting Structures That Stand the Test of Time</h2>
                    <p class="text-muted mb-4">
                        At JP Construction Works, we don't just build walls; we build legacies. Our approach combines traditional craftsmanship with modern technology to ensure every project is a masterpiece of safety and design.
                    </p>
                    
                    <ul class="highlight-check mb-5">
                        <li><i class="bi bi-patch-check-fill"></i> Real Time Certified Standards</li>
                        <li><i class="bi bi-patch-check-fill"></i> Eco-Friendly Sustainable Materials</li>
                        <li><i class="bi bi-patch-check-fill"></i> Real-time Project Tracking for Clients</li>
                    </ul>

                    <div class="d-flex gap-3">
                        <a href="contact.php" class="btn btn-primary btn-lg rounded-pill px-4 py-3">Start a Project</a>
                        <a href="services-static.php" class="btn btn-outline-dark btn-lg rounded-pill px-4 py-3">Our Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding bg-light">
        <div class="container text-center mb-5">
            <h2 class="fw-bold">Our Core Foundation</h2>
            <p class="text-muted">The principles that guide every brick we lay.</p>
        </div>
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <h4>Our Mission</h4>
                        <p class="text-muted small">To deliver high-quality, cost-effective construction projects on schedule while maintaining the highest level of safety.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fa-solid fa-eye"></i></div>
                        <h4>Our Vision</h4>
                        <p class="text-muted small">To be the most trusted construction partner in India, known for innovation, integrity, and sustainable building solutions.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box">
                        <div class="feature-icon"><i class="fa-solid fa-gem"></i></div>
                        <h4>Our Values</h4>
                        <p class="text-muted small">Safety first, absolute transparency, uncompromising quality, and respect for every worker and environment.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>