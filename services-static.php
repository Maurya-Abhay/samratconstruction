<?php
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
    <link rel="icon" href="admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Our Services | JP Construction</title>
    <meta name="description" content="Explore the full range of construction, civil works, and interior design services offered by JP Construction. Quality, reliability, and expert solutions for every project.">
    <meta name="keywords" content="JP Construction services, construction, civil works, interior design, home renovation, commercial construction, India">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://jpconstruction.in/services-static.php">
    <meta name="theme-color" content="#0d6efd">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Our Services | JP Construction">
    <meta property="og:description" content="Explore the full range of construction, civil works, and interior design services offered by JP Construction. Quality, reliability, and expert solutions for every project.">
    <meta property="og:url" content="https://jpconstruction.in/services-static.php">
    <meta property="og:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Services | JP Construction">
    <meta name="twitter:description" content="Explore the full range of construction, civil works, and interior design services offered by JP Construction. Quality, reliability, and expert solutions for every project.">
    <meta name="twitter:image" content="https://jpconstruction.in/admin/assets/jp_construction_logo.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-purple: #8245ec;
            --brand-dark: #0f172a;
            --text-muted: #64748b;
            --soft-bg: #f8fafc;
        }

        body { 
            background: #fff; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--brand-dark);
        }

        /* --- Modern Header --- */
        .services-hero {
            background: linear-gradient(rgba(130, 69, 236, 0.9), rgba(15, 23, 42, 0.95)), 
                        url('https://images.unsplash.com/photo-1503387762-592dea58ef23?auto=format&fit=crop&q=80&w=1500');
            background-size: cover;
            background-position: center;
            padding: 120px 0 160px;
            color: #fff;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0% 100%);
        }

        .services-hero h1 {
            font-weight: 800;
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            margin-bottom: 20px;
        }

        /* --- Service Cards --- */
        .service-card { 
            border: 1px solid #f1f5f9;
            border-radius: 24px; 
            background: #fff; 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); 
            height: 100%;
            padding: 35px;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 20px 40px rgba(130, 69, 236, 0.12);
            border-color: var(--brand-purple);
        }

        .icon-wrapper {
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #f4f0ff;
            color: var(--brand-purple);
            font-size: 1.8rem;
            margin-bottom: 25px;
            transition: 0.3s;
        }

        .service-card:hover .icon-wrapper {
            background: var(--brand-purple);
            color: #fff;
            transform: rotateY(180deg);
        }

        .service-title { 
            font-weight: 700; 
            font-size: 1.25rem;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .service-desc { 
            color: var(--text-muted); 
            font-size: 0.95rem; 
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* --- Category Headings --- */
        .cat-tag {
            background: #f4f0ff;
            color: var(--brand-purple);
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 15px;
        }

        .category-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* --- CTA Section --- */
        .cta-box {
            background: var(--brand-dark);
            border-radius: 30px;
            padding: 60px;
            color: #fff;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .cta-box::after {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: var(--brand-purple);
            filter: blur(100px);
            opacity: 0.2;
        }

        @media (max-width: 768px) {
            .services-hero { padding: 80px 0 120px; }
            .cta-box { padding: 40px 20px; }
        }
    </style>
</head>
<body>

<?php include "header.php"; ?>

<section class="services-hero">
    <div class="container">
        <h1>Comprehensive Solutions</h1>
        <p class="lead opacity-75 mx-auto" style="max-width: 700px;">
            From architectural blueprints to the final touch of paint, JP Construction delivers excellence at every stage of the building process.
        </p>
    </div>
</section>

<div class="container" style="margin-top: -60px;">
    <?php foreach ($services as $category => $service_list): ?>
        
        <div class="mb-5 pb-4">
            <span class="cat-tag"><?= $category ?></span>
            <h2 class="category-title">
                <?php 
                    $icon = match($category) {
                        'Construction & Development' => 'bi-hammer',
                        'Specialized & Finishing' => 'bi-stars',
                        default => 'bi-gear-wide-connected'
                    };
                ?>
                <i class="bi <?= $icon ?> text-primary"></i> 
                Our <?= $category ?>
            </h2>

            <div class="row g-4">
                <?php foreach ($service_list as $service): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="icon-wrapper">
                            <i class="bi <?= $service['icon'] ?>"></i>
                        </div>
                        <h3 class="service-title"><?= $service['title'] ?></h3>
                        <p class="service-desc"><?= $service['desc'] ?></p>
                        
                        <a href="contact.php" class="text-primary text-decoration-none mt-3 d-inline-block fw-bold small">
                            Inquire Now <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endforeach; ?>

    <div class="cta-box text-center">
        <h2 class="fw-bold mb-3">Custom Requirement?</h2>
        <p class="lead opacity-75 mb-4">We specialize in tailoring our services to match your specific project needs and budget constraints.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="contact.php" class="btn btn-primary btn-lg rounded-pill px-5">Get a Custom Quote</a>
            <a href="tel:+91XXXXXXXXXX" class="btn btn-outline-light btn-lg rounded-pill px-5">Call Our Expert</a>
        </div>
    </div>
</div>

<div class="py-5"></div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>