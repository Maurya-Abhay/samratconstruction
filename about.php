<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>About Us | JP Construction</title>
    <meta name="description" content="Building legacies since 2015. Learn about JP Construction's mission, vision, and expert team.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Oswald:wght@300;400;600;700&family=Reenie+Beanie&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* Consistent Brand Colors */
            --primary: #0b1c2c; /* Deep Navy */
            --accent: #ff9f1c;  /* Construction Amber */
            --light: #f4f7f6;
            --text-dark: #1a1a1a;
            --text-gray: #6c757d;
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
            --font-sig: 'Reenie Beanie', cursive;
        }

        body {
            font-family: var(--font-body);
            color: var(--text-dark);
            background-color: #fff;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5 { font-family: var(--font-head); text-transform: uppercase; letter-spacing: 1px; }

        /* --- Global Components --- */
        .text-accent { color: var(--accent) !important; }
        .bg-primary-dark { background-color: var(--primary); }
        .line-accent { width: 60px; height: 4px; background: var(--accent); margin-bottom: 20px; display: inline-block; }
        
        .btn-outline-custom {
            border: 2px solid var(--primary);
            color: var(--primary);
            font-weight: 700;
            padding: 12px 30px;
            border-radius: 0;
            text-transform: uppercase;
            transition: 0.3s;
        }
        .btn-outline-custom:hover { background: var(--primary); color: white; }

        /* --- Hero Section (Parallax) --- */
        .page-hero {
            background: linear-gradient(rgba(11, 28, 44, 0.85), rgba(11, 28, 44, 0.7)), url('admin/assets/jp_hero.webp');
            background-attachment: fixed;
            background-position: center;
            background-size: cover;
            height: 60vh;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
        }
        .page-hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50px;
            background: linear-gradient(to top, #ffffff, transparent);
        }

        /* --- About Story Section --- */
        .about-story { padding: 80px 0; }
        .img-stack { position: relative; padding-bottom: 40px; }
        .img-main { width: 90%; border-radius: 4px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .img-secondary {
            position: absolute; bottom: 0; right: 0; width: 55%;
            border: 8px solid #fff; border-radius: 4px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .exp-badge {
            position: absolute; top: 30px; left: -20px;
            background: var(--accent); color: var(--primary);
            padding: 20px 25px; font-weight: bold; font-family: var(--font-head);
            box-shadow: 0 10px 20px rgba(255, 159, 28, 0.3);
            z-index: 10;
        }
        .exp-badge span { display: block; font-size: 2.5rem; line-height: 1; }
        
        .check-list li {
            margin-bottom: 15px; display: flex; align-items: center;
            font-weight: 600; color: var(--text-dark);
        }
        .check-list i {
            color: var(--accent); background: rgba(11, 28, 44, 0.05);
            width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; margin-right: 15px; font-size: 0.9rem;
        }
        
        .signature { font-family: var(--font-sig); font-size: 2.5rem; color: var(--text-gray); margin-top: 20px; }

        /* --- Stats Section --- */
        .stats-strip { background: var(--light); padding: 60px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        .stat-box h2 { font-size: 3rem; color: var(--primary); margin-bottom: 0; }
        .stat-box p { font-size: 0.85rem; letter-spacing: 2px; text-transform: uppercase; color: var(--text-gray); font-weight: 700; }

        /* --- Vision/Mission (Dark) --- */
        .vision-section { background: var(--primary); color: white; padding: 100px 0; position: relative; overflow: hidden; }
        .vision-bg-text {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            font-size: 15vw; opacity: 0.03; font-weight: 700; font-family: var(--font-head); white-space: nowrap; pointer-events: none;
        }
        .value-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 40px;
            transition: all 0.4s ease;
            height: 100%;
        }
        .value-card:hover { background: var(--accent); transform: translateY(-10px); }
        .value-card:hover * { color: var(--primary) !important; }
        .value-icon { font-size: 2.5rem; color: var(--accent); margin-bottom: 20px; transition: 0.3s; }
        
        /* --- CTA --- */
        .cta-strip { background: var(--accent); padding: 50px 0; }
    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <header class="page-hero">
        <div class="container" data-aos="fade-up">
            <span class="text-uppercase letter-spacing-2 fw-bold text-accent mb-2 d-block">Est. 2015</span>
            <h1 class="display-3 fw-bold text-white">More Than Just Builders</h1>
            <p class="lead opacity-75 mx-auto" style="max-width: 600px;">We are architects of the future, crafting spaces that define the skyline.</p>
        </div>
    </header>

    <section class="about-story">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right">
                    <div class="img-stack">
                        <div class="exp-badge">
                            <span>10+</span> Years Exp.
                        </div>
                        <img src="https://images.unsplash.com/photo-1541888946425-d81bb19240f5?auto=format&fit=crop&q=80&w=1000" alt="Construction Site" class="img-main">
                        <img src="https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&q=80&w=600" alt="Blueprint" class="img-secondary">
                    </div>
                </div>
                
                <div class="col-lg-6 ps-lg-5" data-aos="fade-left">
                    <span class="text-accent fw-bold text-uppercase ls-1">Who We Are</span>
                    <h2 class="display-5 fw-bold mb-4 text-dark">Building with Precision,<br>Delivering with Pride.</h2>
                    <div class="line-accent"></div>
                    
                    <p class="text-muted mb-4">
                        JP Construction was founded on a simple premise: <strong>Quality without compromise</strong>. From humble residential beginnings to complex commercial infrastructures, our journey has been defined by engineering excellence and unwavering integrity.
                    </p>
                    <p class="text-muted mb-4">
                        We don't just pour concrete; we build relationships. Our team of certified engineers and architects works tirelessly to ensure every project is sustainable, safe, and stunning.
                    </p>

                    <ul class="list-unstyled check-list">
                        <li><i class="bi bi-check-lg"></i> ISO Certified Safety Standards</li>
                        <li><i class="bi bi-check-lg"></i> Transparent Costing & Timelines</li>
                        <li><i class="bi bi-check-lg"></i> Sustainable & Eco-friendly Materials</li>
                    </ul>

                    <div class="signature">jitendra Prasad</div>
                    <small class="text-uppercase fw-bold text-muted">Founder & CEO</small>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-strip">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-box">
                        <h2>250+</h2>
                        <p>Projects</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-box">
                        <h2>50+</h2>
                        <p>Team Members</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-box">
                        <h2>12</h2>
                        <p>Districts Covered</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-box">
                        <h2>100%</h2>
                        <p>Satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="vision-section">
        <div class="vision-bg-text">VALUES</div>
        <div class="container position-relative z-1">
            <div class="text-center mb-5" data-aos="fade-up">
                <h5 class="text-accent">OUR PHILOSOPHY</h5>
                <h2 class="fw-bold">The Pillars We Stand On</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="value-card">
                        <i class="bi bi-bullseye value-icon"></i>
                        <h3 class="h4 mb-3">Our Mission</h3>
                        <p class="opacity-75">To deliver superior construction solutions that exceed client expectations through innovation, safety, and timely delivery.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="value-card">
                        <i class="bi bi-eye value-icon"></i>
                        <h3 class="h4 mb-3">Our Vision</h3>
                        <p class="opacity-75">To be the region's most trusted infrastructure partner, recognized for transforming landscapes and improving lives.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="value-card">
                        <i class="bi bi-gem value-icon"></i>
                        <h3 class="h4 mb-3">Core Values</h3>
                        <p class="opacity-75">Integrity in every deal, Quality in every brick, and Respect for every individual involved in the process.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-strip">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 text-center text-md-start mb-3 mb-md-0">
                    <h3 class="fw-bold text-primary mb-0">Have a vision in mind? Let's build it.</h3>
                </div>
                <div class="col-md-4 text-center text-md-end">
                    <a href="contact.php" class="btn btn-dark rounded-0 py-3 px-5 fw-bold">CONTACT US</a>
                </div>
            </div>
        </div>
    </section>

    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
    </script>
</body>
</html>