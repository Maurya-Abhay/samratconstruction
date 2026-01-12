<?php

$services = [
    'Construction & Development' => [
        ['icon' => 'bi-building-up', 'title' => 'Residential Building Construction', 'desc' => 'Complete home and apartment construction using quality materials and modern architectural plans.'],
        ['icon' => 'bi-house-door-fill', 'title' => 'Independent House Construction', 'desc' => 'Custom-built independent villas and bungalows tailored exactly to your unique design requirements.'],
        ['icon' => 'bi-bank-fill', 'title' => 'Commercial Complex Development', 'desc' => 'Design and execution of robust commercial buildings, retail spaces, and office complexes.'],
        ['icon' => 'bi-hospital-fill', 'title' => 'Hospital & Healthcare Facilities', 'desc' => 'Specialized construction adhering to healthcare standards, focusing on functional and safe environments.'],
        ['icon' => 'bi-building-fill-gear', 'title' => 'Industrial & Warehouse Construction', 'desc' => 'Building factories, industrial sheds, and logistic warehouses with structural durability and efficiency.'],
    ],
    'Specialized & Finishing Works' => [
        ['icon' => 'bi-tools', 'title' => 'Renovation & Remodeling Services', 'desc' => 'Transforming existing spaces into modern, functional areas through comprehensive renovation and remodeling.'],
        ['icon' => 'bi-door-open-fill', 'title' => 'Interior Design & Fitouts', 'desc' => 'Aesthetic and functional interior solutions, including modular kitchens, office fitouts, and space planning.'],
        ['icon' => 'bi-paint-bucket-fill', 'title' => 'Painting & Premium Finishing', 'desc' => 'High-quality interior and exterior painting, textured finishes, and protective coatings for longevity.'],
        ['icon' => 'bi-droplet-half', 'title' => 'Advanced Waterproofing Solutions', 'desc' => 'Permanent solutions for leakage in roofs, basements, and wet areas using cutting-edge waterproofing technology.'],
        ['icon' => 'bi-bricks', 'title' => 'Structural Repairs & Strengthening', 'desc' => 'Assessment, strengthening, and repair of old or damaged structures to restore integrity and safety.'],
    ],
    'Management & Consultancy' => [
        ['icon' => 'bi-calendar-event-fill', 'title' => 'Project Planning & Consultancy', 'desc' => 'End-to-end project management, detailed estimation, legal approvals, and expert consultancy.'],
        ['icon' => 'bi-people-fill', 'title' => 'Labour & Workforce Management', 'desc' => 'Providing skilled and certified workforce, ensuring efficient and safe site management.'],
        ['icon' => 'bi-truck-flatbed', 'title' => 'Material Supply & Logistics', 'desc' => 'Timely supply of verified, high-quality construction materials directly to the project site.'],
        ['icon' => 'bi-lightning-charge-fill', 'title' => 'Electrical & Plumbing Systems', 'desc' => 'Installation, upgrade, and maintenance of safe and reliable electrical and plumbing systems.'],
        ['icon' => 'bi-tree-fill', 'title' => 'Landscaping & Exterior Development', 'desc' => 'Designing and developing beautiful exterior spaces, including gardens, patios, and paving.'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Services - SamratPro</title>
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --heading-color: #212529;
            --background-light: #f7f9fc;
            --card-shadow-start: 0 10px 30px rgba(0, 123, 255, 0.1);
            --card-shadow-hover: 0 15px 45px rgba(0, 0, 0, 0.15);
        }

        body { 
            background: var(--background-light); 
            font-family: 'Poppins', sans-serif; 
            color: var(--heading-color);
        }

        /* --- Header Section Styling --- */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: #fff;
            padding: 80px 0;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .header-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .header-subtitle {
            font-size: 1.2rem;
            font-weight: 400;
            opacity: 0.9;
        }

        /* --- Service Card Styling --- */
        .service-card { 
            border-radius: 15px; 
            box-shadow: var(--card-shadow-start); 
            background: #fff; 
            transition: box-shadow 0.3s, transform 0.3s, border 0.3s; 
            border: 1px solid transparent; /* Added for smoother hover */
        }
        .service-card:hover { 
            box-shadow: var(--card-shadow-hover); 
            transform: translateY(-5px); 
            border-color: var(--primary-color);
        }

        /* Icon Styling */
        .service-icon-box {
            display: inline-flex;
            width: 70px;
            height: 70px;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #e3f2fd; /* Light Primary Color */
            margin-bottom: 15px;
            border: 2px solid var(--primary-color);
        }
        .service-icon { 
            font-size: 2.2rem; 
            color: var(--primary-color); 
        }

        /* Text Styling */
        .service-title { 
            font-weight: 700; 
            color: var(--heading-color); 
            font-size: 1.25rem;
            margin-bottom: 8px;
        }
        .service-desc { 
            color: var(--secondary-color); 
            font-size: 0.95rem; 
            line-height: 1.5;
        }

        /* --- Section Separator Styling --- */
        .category-heading {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--heading-color);
            margin-top: 50px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
            padding-left: 20px;
        }
        .category-heading i {
            color: var(--primary-color);
            margin-right: 10px;
        }
    </style>
</head>
<body>

<?php include "header.php"; ?>

<div class="page-header">
    <div class="container text-center">
        <h1 class="header-title">Our Spectrum of Services</h1>
        <p class="header-subtitle">SamratPro: Delivering integrated, innovative, and high-quality construction solutions across diverse sectors.</p>
        <a href="#services-list" class="btn btn-light btn-lg mt-4 shadow-sm fw-bold">Explore Our Expertise <i class="bi bi-arrow-down-circle-fill ms-2"></i></a>
    </div>
</div>

<div class="container py-5" id="services-list">
    
    <?php foreach ($services as $category => $service_list): ?>
    
        <h2 class="category-heading">
            <?php 
                $icon = '';
                if ($category == 'Construction & Development') $icon = 'bi-hammer';
                else if ($category == 'Specialized & Finishing Works') $icon = 'bi-magic';
                else if ($category == 'Management & Consultancy') $icon = 'bi-kanban-fill';
            ?>
            <i class="bi <?= $icon ?>"></i> <?= $category ?>
        </h2>
        <p class="text-secondary mb-4 ps-3 border-start border-3" style="border-color: #dee2e6 !important;">
            <?= 
                $category == 'Construction & Development' ? 'Core building services, from residential towers to large-scale industrial infrastructure.' : 
                ($category == 'Specialized & Finishing Works' ? 'Detail-oriented services covering renovation, interiors, and protective structural coatings.' : 
                'Expert management, planning, and logistical support for smooth project execution.')
            ?>
        </p>

        <div class="row g-4 mb-5">
            <?php foreach ($service_list as $service): ?>
            <div class="col-lg-4 col-md-6">
                <a href="service_details.php?id=<?= urlencode(strtolower(str_replace(' ', '-', $service['title']))) ?>" class="text-decoration-none">
                    <div class="service-card p-4 text-start h-100">
                        <div class="service-icon-box">
                            <i class="bi <?= htmlspecialchars($service['icon']) ?> service-icon"></i>
                        </div>
                        <div class="service-title"><?= htmlspecialchars($service['title']) ?></div>
                        <div class="service-desc mt-2"><?= htmlspecialchars($service['desc']) ?></div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <hr class="my-5">
    <?php endforeach; ?>
    
    <div class="text-center py-4 bg-white rounded-3 shadow-sm p-5 mt-5">
        <h3 class="fw-bold mb-3" style="color: var(--heading-color);">Ready to Start Your Project?</h3>
        <p class="lead text-secondary mb-4">Contact our experts today for a free consultation and project estimation.</p>
        <a href="contact.php" class="btn btn-primary btn-lg px-5 shadow-lg fw-bold" style="background: linear-gradient(90deg, #007bff, #0056b3); border: none;">
            Get a Free Quote <i class="bi bi-chat-text-fill ms-2"></i>
        </a>
    </div>

</div>

<?php include "footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>