<?php
// service-detail.php - Modern Service Detail Page

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
    echo "<div style='height:100vh; display:flex; align-items:center; justify-content:center; flex-direction:column; font-family:sans-serif; background:#f8fafc;'>
            <h2 style='color:#1e293b; margin-bottom:20px;'>Service Not Found</h2>
            <a href='services.php' style='padding:10px 20px; background:#2563eb; color:white; text-decoration:none; border-radius:50px; font-weight:bold;'>Back to Services</a>
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
    <title><?= htmlspecialchars($service['service_name']) ?> | Samrat Construction</title>
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png">

    <!-- Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --secondary: #0f172a;
            --accent: #3b82f6;
            --light-bg: #f8fafc;
            --text-main: #334155;
            --text-light: #64748b;
            --card-shadow: 0 20px 40px -5px rgba(0,0,0,0.1);
            --font-head: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--light-bg);
            color: var(--text-main);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); }

        /* --- HERO HEADER --- */
        .service-hero {
            position: relative;
            height: 50vh;
            min-height: 400px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
            margin-bottom: -100px; /* Overlap effect */
            padding-bottom: 50px;
        }

        .hero-bg-img {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover;
            opacity: 0.3;
            filter: blur(3px);
        }

        .hero-content { z-index: 2; position: relative; max-width: 800px; padding: 20px; }
        
        .service-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* --- MAIN CONTENT CARD --- */
        .content-wrapper {
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            padding: 0;
            margin-bottom: 50px;
        }

        /* Image Gallery Section */
        .service-gallery {
            height: 450px;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid #eee;
        }
        .service-main-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1s ease;
        }
        .service-gallery:hover .service-main-img { transform: scale(1.05); }

        /* Content Layout */
        .card-content { padding: 4rem; }
        
        .section-heading {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            border-left: 5px solid var(--primary);
            padding-left: 15px;
        }

        .text-body-lg { font-size: 1.1rem; line-height: 1.8; color: var(--text-main); margin-bottom: 2rem; }

        /* --- FEATURES GRID --- */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
        }

        .feature-item {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }
        .feature-item:hover {
            background: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transform: translateY(-3px);
        }
        .check-icon {
            width: 32px; height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .feature-text { font-weight: 600; color: var(--secondary); }

        /* --- PRICING & TAGS --- */
        .meta-box {
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 3rem;
            border: 1px solid #e2e8f0;
        }
        
        .price-display {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .tag-badge {
            background: white;
            border: 1px solid #cbd5e1;
            color: var(--text-light);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-block;
        }

        /* --- TESTIMONIAL --- */
        .testimonial-card {
            background: #0f172a;
            color: white;
            padding: 40px;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
        }
        .quote-icon {
            position: absolute;
            top: 20px; right: 30px;
            font-size: 5rem;
            color: rgba(255,255,255,0.1);
        }
        .testimonial-text {
            font-size: 1.2rem;
            font-style: italic;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }

        /* --- FAQ ACCORDION --- */
        .accordion-item {
            border: none;
            margin-bottom: 15px;
            background: transparent;
        }
        .accordion-button {
            background: white;
            border-radius: 12px !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            font-weight: 600;
            color: var(--secondary);
            padding: 20px;
            border: 1px solid #f1f5f9;
        }
        .accordion-button:not(.collapsed) {
            background: #eff6ff;
            color: var(--primary);
            box-shadow: none;
        }
        .accordion-body {
            padding: 20px;
            color: var(--text-light);
            background: white;
            border-radius: 0 0 12px 12px;
            margin-top: -10px;
            padding-top: 30px;
        }

        /* --- CTA BUTTON --- */
        .cta-btn-lg {
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            color: white;
            padding: 18px 40px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            border: none;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            transition: all 0.3s;
            display: inline-flex; align-items: center; gap: 10px;
        }
        .cta-btn-lg:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.4);
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .card-content { padding: 2rem; }
            .service-gallery { height: 300px; }
            .meta-box { padding: 20px; }
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<?php include "header.php"; ?>

<!-- 1. HERO SECTION -->
<div class="service-hero">
    <?php if (!empty($service['service_photo'])): ?>
        <img src="admin/uploads/<?= htmlspecialchars($service['service_photo']) ?>" class="hero-bg-img" alt="Background">
    <?php endif; ?>
    
    <div class="hero-content" data-aos="fade-up">
        <span class="service-badge">Premium Service</span>
        <h1 class="hero-title"><?= htmlspecialchars($service['service_name']) ?></h1>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none opacity-75">Home</a></li>
                <li class="breadcrumb-item"><a href="services.php" class="text-white text-decoration-none opacity-75">Services</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Details</li>
            </ol>
        </nav>
    </div>
</div>

<!-- 2. MAIN CONTENT -->
<div class="container content-wrapper">
    <div class="main-card">
        
        <!-- Featured Image Gallery -->
        <?php if (!empty($service['service_photo'])): ?>
        <div class="service-gallery">
            <img src="admin/uploads/<?= htmlspecialchars($service['service_photo']) ?>" alt="Service Image" class="service-main-img">
        </div>
        <?php endif; ?>

        <div class="card-content">
            
            <div class="row g-5">
                <!-- Left Column: Details -->
                <div class="col-lg-8">
                    
                    <!-- Description -->
                    <div class="mb-5" data-aos="fade-up">
                        <h3 class="section-heading">Overview</h3>
                        <?php if (!empty($service['short_desc'])): ?>
                            <p class="lead text-dark fw-medium mb-4"><?= nl2br(htmlspecialchars($service['short_desc'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($service['long_desc'])): ?>
                            <div class="text-body-lg">
                                <?= $service['long_desc'] /* Trusted HTML */ ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Features Grid -->
                    <?php if (!empty($service['features'])): ?>
                    <div class="mb-5" data-aos="fade-up">
                        <h3 class="section-heading">Key Features</h3>
                        <div class="features-grid">
                            <?php 
                            $features = explode("\n", $service['features']);
                            foreach ($features as $feature): 
                                if(trim($feature)):
                                    // Clean up any bullet points user might have added manually
                                    $cleanFeature = trim(str_replace(['-','•','✔️'], '', $feature));
                            ?>
                                <div class="feature-item">
                                    <div class="check-icon"><i class="bi bi-check-lg"></i></div>
                                    <span class="feature-text"><?= htmlspecialchars($cleanFeature) ?></span>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- FAQ Accordion -->
                    <?php if (!empty($service['faq'])): ?>
                    <div class="mb-5" data-aos="fade-up">
                        <h3 class="section-heading">Frequently Asked Questions</h3>
                        <div class="accordion" id="faqAccordion">
                            <?php 
                            $faqRaw = $service['faq'];
                            $faqParts = preg_split('/(?=Q:)/', $faqRaw);
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

                <!-- Right Column: Sidebar -->
                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 100px; z-index: 5;">
                        
                        <!-- Pricing & Meta -->
                        <div class="meta-box" data-aos="fade-left">
                            <?php if (!empty($service['pricing'])): ?>
                                <div class="mb-4">
                                    <label class="text-uppercase small text-muted fw-bold ls-1">Starting Price</label>
                                    <div class="price-display"><?= htmlspecialchars($service['pricing']) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($service['tags'])): ?>
                                <div>
                                    <label class="text-uppercase small text-muted fw-bold ls-1 d-block mb-2">Tags</label>
                                    <div>
                                        <?php foreach (explode(',', $service['tags']) as $tag): if (trim($tag)): ?>
                                            <span class="tag-badge"><?= htmlspecialchars(trim($tag)) ?></span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Testimonial -->
                        <?php if (!empty($service['testimonial'])): ?>
                        <div class="testimonial-card" data-aos="fade-left" data-aos-delay="100">
                            <i class="bi bi-quote quote-icon"></i>
                            <div class="testimonial-text">
                                "<?= nl2br(htmlspecialchars($service['testimonial'])) ?>"
                            </div>
                            <div class="mt-3 text-white-50 small fw-bold text-uppercase ls-1">— Client Feedback</div>
                        </div>
                        <?php endif; ?>

                        <!-- CTA Button -->
                        <?php if (!empty($service['cta_label']) && !empty($service['cta_link'])): ?>
                        <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                            <a href="<?= htmlspecialchars($service['cta_link']) ?>" class="cta-btn-lg w-100 justify-content-center">
                                <?= htmlspecialchars($service['cta_label']) ?> <i class="bi bi-arrow-right-circle"></i>
                            </a>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
</script>

</body>
</html>