<?php
// Define Services Data
$services = [
    'Construction & Development' => [
        ['icon' => 'bi-building-up', 'title' => 'Residential Construction', 'desc' => 'Complete home and apartment construction using quality materials and modern architectural plans.'],
        ['icon' => 'bi-house-door-fill', 'title' => 'Independent Houses', 'desc' => 'Custom-built independent villas and bungalows tailored exactly to your unique design requirements.'],
        ['icon' => 'bi-bank-fill', 'title' => 'Commercial Development', 'desc' => 'Design and execution of robust commercial buildings, retail spaces, and office complexes.'],
        ['icon' => 'bi-hospital-fill', 'title' => 'Healthcare Facilities', 'desc' => 'Specialized construction adhering to healthcare standards, focusing on functional and safe environments.'],
        ['icon' => 'bi-building-fill-gear', 'title' => 'Industrial & Warehousing', 'desc' => 'Building factories, industrial sheds, and logistic warehouses with structural durability.'],
    ],
    'Specialized & Finishing' => [
        ['icon' => 'bi-tools', 'title' => 'Renovation & Remodeling', 'desc' => 'Transforming existing spaces into modern, functional areas through comprehensive remodeling.'],
        ['icon' => 'bi-door-open-fill', 'title' => 'Interior Design', 'desc' => 'Aesthetic and functional interior solutions, including modular kitchens and space planning.'],
        ['icon' => 'bi-paint-bucket-fill', 'title' => 'Premium Finishing', 'desc' => 'High-quality interior and exterior painting, textured finishes, and protective coatings.'],
        ['icon' => 'bi-droplet-half', 'title' => 'Advanced Waterproofing', 'desc' => 'Permanent solutions for leakage in roofs and basements using cutting-edge technology.'],
        ['icon' => 'bi-bricks', 'title' => 'Structural Repairs', 'desc' => 'Assessment and strengthening of old structures to restore integrity and safety.'],
    ],
    'Management & Consultancy' => [
        ['icon' => 'bi-calendar-event-fill', 'title' => 'Project Consultancy', 'desc' => 'End-to-end project management, detailed estimation, and expert consultancy.'],
        ['icon' => 'bi-people-fill', 'title' => 'Workforce Management', 'desc' => 'Providing skilled and certified workforce, ensuring efficient and safe site management.'],
        ['icon' => 'bi-truck-flatbed', 'title' => 'Material Logistics', 'desc' => 'Timely supply of verified, high-quality construction materials directly to the project site.'],
        ['icon' => 'bi-lightning-charge-fill', 'title' => 'Electrical & Plumbing', 'desc' => 'Installation and maintenance of safe and reliable electrical and plumbing systems.'],
        ['icon' => 'bi-tree-fill', 'title' => 'Landscaping Solutions', 'desc' => 'Designing and developing beautiful exterior spaces, including gardens and paving.'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services | JP Construction</title>
    <meta name="theme-color" content="#0b1c2c">
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;700&family=Oswald:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* Consistent Theme: Dark Navy & Amber */
            --primary: #0b1c2c;
            --accent: #ff9f1c;
            --light-bg: #f4f7f6;
            --text-dark: #1a1a1a;
            
            --font-head: 'Oswald', sans-serif;
            --font-body: 'Manrope', sans-serif;
            
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
            --card-hover: 0 20px 40px rgba(11, 28, 44, 0.12);
        }

        body { 
            background: #fdfdfd; 
            font-family: var(--font-body); 
            color: var(--text-dark);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: var(--font-head); letter-spacing: 0.5px; text-transform: uppercase; }

        /* --- Blueprint Pattern Background --- */
        .bg-blueprint {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background-image: 
                linear-gradient(rgba(11, 28, 44, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(11, 28, 44, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* --- Hero Section --- */
        .service-hero {
            background: var(--primary);
            padding: 120px 0 100px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .service-hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 5px;
            background: repeating-linear-gradient(45deg, var(--accent), var(--accent) 10px, transparent 10px, transparent 20px);
        }

        .service-hero h1 { font-size: 3.5rem; font-weight: 700; }
        .service-hero .lead { font-family: var(--font-body); opacity: 0.8; font-weight: 300; max-width: 700px; margin: 0 auto; letter-spacing: 0.5px; }

        /* --- Section Titles --- */
        .category-header {
            margin-top: 80px; margin-bottom: 40px;
            display: flex; align-items: center; gap: 15px;
        }
        .category-header h2 { color: var(--primary); font-size: 2rem; font-weight: 700; margin: 0; }
        .category-line { flex-grow: 1; height: 2px; background: rgba(11, 28, 44, 0.1); position: relative; }
        .category-line::after {
            content: ''; position: absolute; top: -2px; left: 0; width: 50px; height: 6px; background: var(--accent);
        }

        /* --- Modern Service Card --- */
        .service-card-v3 {
            background: white;
            padding: 40px 30px;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            height: 100%;
            overflow: hidden;
            border-bottom: 3px solid transparent;
        }

        .service-card-v3:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-hover);
            border-bottom-color: var(--accent);
        }

        .icon-wrapper {
            width: 60px; height: 60px;
            background: rgba(11, 28, 44, 0.03);
            color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 25px;
            transition: 0.3s;
            position: relative;
        }
        
        .service-card-v3:hover .icon-wrapper {
            background: var(--accent);
            color: var(--primary);
        }

        .service-card-v3 h4 { font-size: 1.3rem; margin-bottom: 15px; font-weight: 600; color: var(--primary); }
        .service-card-v3 p { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; }
        
        .card-link {
            color: var(--primary); font-weight: 700; text-decoration: none;
            font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .card-link:hover { color: var(--accent); gap: 10px; transition: 0.3s; }

        /* --- CTA Section --- */
        .cta-box {
            background: var(--primary);
            color: white;
            padding: 80px 20px;
            margin-top: 100px;
            text-align: center;
            position: relative;
        }
        .cta-box::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: url('admin/assets/pattern.png'); opacity: 0.05;
        }
        .btn-cta {
            background: var(--accent); color: var(--primary);
            padding: 15px 40px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            border: none; transition: 0.3s;
            display: inline-block; margin-top: 20px;
        }
        .btn-cta:hover { background: white; color: var(--primary); transform: translateY(-3px); }

        @media (max-width: 768px) {
            .service-hero h1 { font-size: 2.5rem; }
            .service-hero { padding: 100px 0 60px; }
            .category-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .category-line { width: 100%; }
        }
    </style>
</head>
<body>

<div class="bg-blueprint"></div>

<?php include "header.php"; ?>

<header class="service-hero">
    <div class="container" data-aos="fade-up">
        <span class="text-accent fw-bold ls-2 text-uppercase d-block mb-2">JP Construction</span>
        <h1>Excellence In <span class="text-accent">Building</span></h1>
        <p class="lead">From foundational structures to premium finishing. We deliver quality at every stage of construction.</p>
    </div>
</header>

<main class="container mb-5">
    
    <?php 
    $delay = 0;
    foreach ($services as $category => $list): 
    ?>
        <div class="category-block" data-aos="fade-up">
            <div class="category-header">
                <h2><?= $category ?></h2>
                <div class="category-line"></div>
            </div>

            <div class="row g-4">
                <?php foreach ($list as $item): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                    <div class="service-card-v3">
                        <div class="icon-wrapper">
                            <i class="bi <?= $item['icon'] ?>"></i>
                        </div>
                        <h4><?= $item['title'] ?></h4>
                        <p><?= $item['desc'] ?></p>
                        <a href="contact.php?service=<?= urlencode($item['title']) ?>" class="card-link stretched-link">
                            Get Quote <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php 
                    $delay += 50; 
                    if($delay > 300) $delay = 0;
                endforeach; 
                ?>
            </div>
        </div>
    <?php endforeach; ?>

</main>

<section class="cta-box" data-aos="zoom-in">
    <div class="container position-relative z-1">
        <h2 class="display-5 mb-3">Ready to Start Your Project?</h2>
        <p class="opacity-75 mb-4 mx-auto" style="max-width: 600px; font-family: var(--font-body);">
            Whether it's a new build, renovation, or interior design, our expert team is ready to bring your vision to life.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="contact.php" class="btn-cta">Free Consultation</a>
            <a href="tel:+910000000000" class="btn btn-outline-light px-4 py-3 text-uppercase fw-bold ls-1 rounded-0">
                <i class="bi bi-telephone me-2"></i> Call Us
            </a>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 800,
    once: true,
    offset: 100
  });
</script>
</body>
</html>