<?php
// service-detail.php - Next-Gen Industrial Design
session_start();
include "admin/database.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$serviceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Service Data
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $serviceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div style='height:100vh; display:flex; align-items:center; justify-content:center; flex-direction:column; font-family:sans-serif; background:#f4f7f6; color:#0b1c2c;'>
            <h1 style='font-size:3rem; margin-bottom:10px;'>404</h1>
            <h3 style='margin-bottom:20px; text-transform:uppercase;'>Service Not Found</h3>
            <a href='services.php' style='padding:12px 30px; background:#ff9f1c; color:#0b1c2c; text-decoration:none; font-weight:bold; text-transform:uppercase; letter-spacing:1px;'>Return to Services</a>
          </div>";
    exit;
}

$service = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($service['service_name']) ?> | JP Construction</title>
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0b1c2c;       /* Deep Navy */
            --accent: #ff9f1c;        /* Amber */
            --bg-light: #f4f7f6;      /* Industrial Light Gray */
            --text-body: #4a5568;     /* Slate Gray */
            --text-head: #0b1c2c;     /* Dark Navy */
            --card-shadow: 0 20px 50px rgba(11, 28, 44, 0.1);
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-light);
            color: var(--text-body);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); color: var(--text-head); text-transform: uppercase; }

        /* --- HERO HEADER --- */
        .service-hero {
            position: relative;
            height: 60vh;
            min-height: 500px;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            margin-bottom: -120px; /* Overlap Effect */
            padding-bottom: 80px;
        }

        .hero-bg-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(11, 28, 44, 0.8), rgba(11, 28, 44, 0.9));
            z-index: 1;
        }

        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            z-index: 0;
            filter: grayscale(100%) contrast(1.2);
            opacity: 0.4;
            transform: scale(1.1);
        }

        .hero-content { 
            z-index: 2; 
            position: relative; 
            max-width: 900px; 
            padding: 20px; 
            color: white;
        }

        .hero-badge {
            background: var(--accent);
            color: var(--primary);
            padding: 5px 15px;
            font-family: var(--font-head);
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .breadcrumb-item a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.9rem; }
        .breadcrumb-item.active { color: var(--accent); }
        .breadcrumb-item+.breadcrumb-item::before { color: rgba(255,255,255,0.4); }

        /* --- MAIN CONTENT LAYOUT --- */
        .content-wrapper {
            position: relative;
            z-index: 10;
            padding-bottom: 80px;
        }

        /* Left Column Styles */
        .main-card {
            background: #ffffff;
            box-shadow: var(--card-shadow);
            padding: 0;
            margin-bottom: 40px;
            border-top: 5px solid var(--accent);
        }

        .service-image-box {
            height: 400px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .service-main-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-image-box:hover .service-main-img { transform: scale(1.05); }

        .card-body-custom { padding: 50px; }

        .section-heading {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .section-heading::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 30px;
            background: var(--accent);
            margin-right: 15px;
        }

        .desc-text {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--text-body);
            margin-bottom: 2rem;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
        }

        .feature-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }

        .feature-item:hover {
            border-left-color: var(--accent);
            background: #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transform: translateX(5px);
        }

        .feature-icon {
            color: var(--accent);
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .feature-text { font-weight: 600; font-family: var(--font-body); color: var(--primary); }

        /* FAQ Accordion */
        .accordion-item { border: none; margin-bottom: 10px; background: transparent; }
        .accordion-button {
            background: #f8fafc;
            color: var(--primary);
            font-family: var(--font-head);
            font-weight: 500;
            letter-spacing: 0.5px;
            padding: 18px 25px;
            box-shadow: none !important;
            border: 1px solid #eee;
        }
        .accordion-button:not(.collapsed) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .accordion-button::after { filter: grayscale(100%); }
        .accordion-button:not(.collapsed)::after { filter: invert(1); }
        .accordion-body { padding: 25px; background: #fff; border: 1px solid #eee; border-top: none; }

        /* --- SIDEBAR --- */
        .sidebar-sticky { position: sticky; top: 120px; z-index: 5; }

        .sidebar-widget {
            background: #ffffff;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid #eee;
        }

        .price-label { font-size: 0.85rem; text-transform: uppercase; color: #888; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 5px; }
        .price-value { font-size: 2.2rem; color: var(--primary); font-family: var(--font-head); font-weight: 700; }

        .tags-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
        .tag-badge {
            background: transparent;
            border: 1px solid #ddd;
            color: #666;
            padding: 6px 12px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            transition: 0.3s;
        }
        .tag-badge:hover { border-color: var(--primary); color: var(--primary); }

        /* Testimonial Widget */
        .testimonial-widget {
            background: var(--primary);
            color: white;
            padding: 35px;
            position: relative;
        }
        .quote-icon { font-size: 3rem; color: var(--accent); opacity: 0.3; position: absolute; top: 20px; right: 20px; }
        .testimonial-text { font-style: italic; opacity: 0.9; margin-bottom: 15px; position: relative; z-index: 2; font-family: var(--font-body); }
        .client-label { color: var(--accent); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

        /* CTA Button */
        .cta-btn {
            background: var(--accent);
            color: var(--primary);
            width: 100%;
            padding: 18px;
            text-align: center;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            text-decoration: none;
            font-family: var(--font-head);
        }
        .cta-btn:hover {
            background: #e68a00;
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 159, 28, 0.3);
        }

        @media (max-width: 991px) {
            .hero-title { font-size: 2.5rem; }
            .card-body-custom { padding: 30px; }
            .service-hero { height: auto; padding: 100px 0 150px 0; }
        }
    </style>
</head>

<body>

<?php include "header.php"; ?>

<div class="service-hero">
    <div class="hero-bg-overlay"></div>
    <?php if (!empty($service['service_photo'])): ?>
        <img src="admin/uploads/<?= htmlspecialchars($service['service_photo']) ?>" class="hero-bg-img" alt="Background">
    <?php endif; ?>
    
    <div class="hero-content" data-aos="fade-up">
        <span class="hero-badge">Professional Service</span>
        <h1 class="hero-title"><?= htmlspecialchars($service['service_name']) ?></h1>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="index.php">HOME</a></li>
                <li class="breadcrumb-item"><a href="services.php">SERVICES</a></li>
                <li class="breadcrumb-item active" aria-current="page">DETAILS</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container content-wrapper">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="main-card">
                
                <?php if (!empty($service['service_photo'])): ?>
                <div class="service-image-box">
                    <img src="admin/uploads/<?= htmlspecialchars($service['service_photo']) ?>" alt="<?= htmlspecialchars($service['service_name']) ?>" class="service-main-img">
                </div>
                <?php endif; ?>

                <div class="card-body-custom">
                    
                    <div class="mb-5" data-aos="fade-up">
                        <h3 class="section-heading">Overview</h3>
                        <?php if (!empty($service['short_desc'])): ?>
                            <p class="lead fw-bold text-dark mb-4" style="font-family: var(--font-body);"><?= nl2br(htmlspecialchars($service['short_desc'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($service['long_desc'])): ?>
                            <div class="desc-text">
                                <?= $service['long_desc'] /* Trusted HTML */ ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($service['features'])): ?>
                    <div class="mb-5" data-aos="fade-up">
                        <h3 class="section-heading">Key Features</h3>
                        <div class="features-grid">
                            <?php 
                            $features = explode("\n", $service['features']);
                            foreach ($features as $feature): 
                                if(trim($feature)):
                                    $cleanFeature = trim(str_replace(['-','•','✔️'], '', $feature));
                            ?>
                                <div class="feature-item">
                                    <i class="fa-solid fa-check-double feature-icon"></i>
                                    <span class="feature-text"><?= htmlspecialchars($cleanFeature) ?></span>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($service['faq'])): ?>
                    <div data-aos="fade-up">
                        <h3 class="section-heading">Frequently Asked Questions</h3>
                        <div class="accordion" id="faqAccordion">
                            <?php 
                            $faqParts = preg_split('/(?=Q:)/', $service['faq']);
                            $faqIndex = 0;
                            foreach ($faqParts as $part) {
                                $part = trim($part);
                                if (empty($part)) continue;
                                if (preg_match('/Q:\s*(.*?)\s*A:\s*(.*)/s', $part, $matches)) {
                                    $question = $matches[1];
                                    $answer = $matches[2];
                                    $faqIndex++;
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $faqIndex ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $faqIndex ?>">
                                        <?= htmlspecialchars($question) ?>
                                    </button>
                                </h2>
                                <div id="collapse<?= $faqIndex ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?= nl2br(htmlspecialchars($answer)) ?>
                                    </div>
                                </div>
                            </div>
                            <?php }} ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sidebar-sticky">
                
                <div class="sidebar-widget" data-aos="fade-left">
                    <?php if (!empty($service['pricing'])): ?>
                        <div class="mb-4 pb-4 border-bottom">
                            <span class="price-label">Starting From</span>
                            <div class="price-value"><?= htmlspecialchars($service['pricing']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($service['tags'])): ?>
                        <div class="mb-4">
                            <span class="price-label mb-2">Related Tags</span>
                            <div class="tags-container">
                                <?php foreach (explode(',', $service['tags']) as $tag): if (trim($tag)): ?>
                                    <span class="tag-badge"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($service['cta_label']) && !empty($service['cta_link'])): ?>
                        <a href="<?= htmlspecialchars($service['cta_link']) ?>" class="cta-btn">
                            <?= htmlspecialchars($service['cta_label']) ?> <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="contact.php" class="cta-btn">
                            Get a Quote <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($service['testimonial'])): ?>
                <div class="testimonial-widget" data-aos="fade-left" data-aos-delay="100">
                    <i class="fa-solid fa-quote-right quote-icon"></i>
                    <p class="testimonial-text">"<?= nl2br(htmlspecialchars($service['testimonial'])) ?>"</p>
                    <div class="client-label">— Happy Client</div>
                </div>
                <?php endif; ?>

                <div class="sidebar-widget mt-4 text-center">
                    <h4 style="font-size:1.2rem; margin-bottom:15px;">Need Help?</h4>
                    <p class="small text-muted mb-3">Not sure if this service is right for you? Call us directly.</p>
                    <a href="tel:+911234567890" class="text-decoration-none fw-bold fs-5 text-dark">
                        <i class="fa-solid fa-phone text-warning me-2"></i> +91 9919 4400 95
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 50
    });
</script>

</body>
</html>